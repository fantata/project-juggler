<?php

namespace App\Console\Commands;

use App\Models\EmailAccount;
use App\Models\EmailMessage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMail extends Command
{
    protected $signature = 'mail:sync {--account= : Sync only this account id}';

    protected $description = 'Fetch new mail from each account over IMAP into the local mailbox (read-only).';

    public function handle(): int
    {
        if (! function_exists('imap_open')) {
            $this->error('ext-imap is not loaded.');

            return self::FAILURE;
        }

        $accounts = EmailAccount::query()
            ->where('is_active', true)
            ->when($this->option('account'), fn ($q, $id) => $q->whereKey($id))
            ->get();

        if ($accounts->isEmpty()) {
            $this->info('No active mail accounts to sync.');

            return self::SUCCESS;
        }

        $total = 0;

        foreach ($accounts as $account) {
            try {
                $fetched = $this->syncAccount($account);
                $total += $fetched;
                $this->info("{$account->name}: {$fetched} new.");
            } catch (\Throwable $e) {
                $this->warn("{$account->name}: {$e->getMessage()}");
                Log::warning("mail:sync {$account->name} failed: {$e->getMessage()}");
            }
        }

        $this->info("Done — {$total} new message(s).");

        return self::SUCCESS;
    }

    private function syncAccount(EmailAccount $account): int
    {
        $dsn = "{{$account->imap_host}:{$account->imap_port}/imap/ssl}INBOX";

        // OP_READONLY so we never flip \Seen — this is a mirror, not a client move.
        $mbox = @imap_open($dsn, $account->imap_username, $account->password, OP_READONLY, 1);

        if ($mbox === false) {
            throw new \RuntimeException('IMAP connect failed: '.imap_last_error());
        }

        try {
            $start = (int) ($account->last_uid ?? 0) + 1;
            $overview = imap_fetch_overview($mbox, "{$start}:*", FT_UID) ?: [];

            $maxUid = (int) ($account->last_uid ?? 0);
            $count = 0;

            foreach ($overview as $ov) {
                $uid = (int) $ov->uid;

                // "$start:*" returns the last message even when none are newer.
                if ($uid < $start) {
                    continue;
                }

                [$text, $html] = $this->extractBodies($mbox, $uid);

                EmailMessage::updateOrCreate(
                    ['email_account_id' => $account->id, 'uid' => $uid],
                    [
                        'message_id' => $ov->message_id ?? null,
                        'in_reply_to' => $this->headerValue($mbox, $uid, 'in_reply_to'),
                        'from_name' => $this->decode($ov->from ?? '')['name'],
                        'from_email' => $this->decode($ov->from ?? '')['email'],
                        'to_email' => $this->decode($ov->to ?? '')['email'],
                        'subject' => isset($ov->subject) ? $this->decodeText($ov->subject) : '(no subject)',
                        'body_text' => $text,
                        'body_html' => $html,
                        'received_at' => isset($ov->date) ? $this->parseDate($ov->date) : null,
                        'is_read' => isset($ov->seen) && $ov->seen,
                    ]
                );

                $maxUid = max($maxUid, $uid);
                $count++;
            }

            $account->update(['last_uid' => $maxUid, 'last_synced_at' => now()]);

            return $count;
        } finally {
            imap_close($mbox);
        }
    }

    /**
     * Walk the MIME structure and pull the plain-text and HTML bodies.
     * FT_PEEK so reading the body doesn't mark the message \Seen.
     */
    private function extractBodies($mbox, int $uid): array
    {
        $structure = imap_fetchstructure($mbox, $uid, FT_UID);
        $text = null;
        $html = null;

        if (! isset($structure->parts)) {
            // Single-part message.
            $body = imap_fetchbody($mbox, $uid, '1', FT_UID | FT_PEEK);
            $decoded = $this->decodePart($body, $structure->encoding ?? 0);
            if (($structure->subtype ?? '') === 'HTML') {
                $html = $decoded;
            } else {
                $text = $decoded;
            }

            return [$text, $html];
        }

        $this->walkParts($mbox, $uid, $structure->parts, '', $text, $html);

        return [$text, $html];
    }

    private function walkParts($mbox, int $uid, array $parts, string $prefix, ?string &$text, ?string &$html): void
    {
        foreach ($parts as $index => $part) {
            $section = $prefix === '' ? (string) ($index + 1) : "{$prefix}.".($index + 1);

            if (isset($part->parts)) {
                $this->walkParts($mbox, $uid, $part->parts, $section, $text, $html);

                continue;
            }

            $subtype = strtoupper($part->subtype ?? '');
            $isAttachment = $this->isAttachment($part);

            if ($isAttachment) {
                continue;
            }

            if ($subtype === 'PLAIN' && $text === null) {
                $text = $this->decodePart(imap_fetchbody($mbox, $uid, $section, FT_UID | FT_PEEK), $part->encoding ?? 0);
            } elseif ($subtype === 'HTML' && $html === null) {
                $html = $this->decodePart(imap_fetchbody($mbox, $uid, $section, FT_UID | FT_PEEK), $part->encoding ?? 0);
            }
        }
    }

    private function isAttachment(object $part): bool
    {
        if (isset($part->disposition) && strtoupper($part->disposition) === 'ATTACHMENT') {
            return true;
        }

        foreach ($part->dparameters ?? [] as $p) {
            if (strtolower($p->attribute) === 'filename') {
                return true;
            }
        }

        return false;
    }

    private function decodePart(string $body, int $encoding): string
    {
        $decoded = match ($encoding) {
            3 => base64_decode($body),            // BASE64
            4 => quoted_printable_decode($body),  // QUOTED-PRINTABLE
            default => $body,
        };

        return trim($decoded);
    }

    /** Split a header address into a display name + email. */
    private function decode(string $raw): array
    {
        $raw = $this->decodeText($raw);

        if (preg_match('/^(.*)<([^>]+)>\s*$/', $raw, $m)) {
            return ['name' => trim(trim($m[1]), '"'), 'email' => strtolower(trim($m[2]))];
        }

        return ['name' => null, 'email' => strtolower(trim($raw)) ?: null];
    }

    private function decodeText(string $raw): string
    {
        $out = '';
        foreach (imap_mime_header_decode($raw) ?: [] as $part) {
            $out .= $part->text;
        }

        return $out !== '' ? $out : $raw;
    }

    private function headerValue($mbox, int $uid, string $key): ?string
    {
        $info = @imap_headerinfo($mbox, imap_msgno($mbox, $uid));

        return $info->{$key} ?? null;
    }

    private function parseDate(string $date): ?Carbon
    {
        try {
            return Carbon::parse($date);
        } catch (\Throwable) {
            return null;
        }
    }
}
