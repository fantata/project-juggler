<?php

namespace App\Console\Commands;

use App\Models\DailyNote;
use App\Models\Issue;
use App\Models\Project;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendDailyBrief extends Command
{
    protected $signature   = 'brief:send {--dry-run : Print the brief to console instead of emailing}';
    protected $description = 'Compile a twice-daily AI brief from projects, email, GitHub, and FreeAgent, then email it.';

    // Fastmail IMAP config for both inboxes
    private array $inboxes = [
        'fantata' => [
            'host'     => 'imap.fastmail.com',
            'port'     => 993,
            'user'     => 'chris@fantata.com',
            'pass_env' => 'FASTMAIL_FANTATA_PASS',
        ],
        'dogface' => [
            'host'     => 'imap.fastmail.com',
            'port'     => 993,
            'user'     => 'chris@dogfaceimprov.com',
            'pass_env' => 'FASTMAIL_DOGFACE_PASS',
        ],
    ];

    public function handle(): int
    {
        $this->info('Compiling daily brief...');

        $context = [];

        $context['projects']  = $this->gatherProjectData();
        $context['emails']    = $this->gatherEmails();
        $context['github']    = $this->gatherGitHubActivity();
        $context['freeagent'] = $this->gatherFreeAgentData();
        $context['notes']     = $this->gatherDailyNotes();

        $brief = $this->generateBrief($context);

        if ($this->option('dry-run')) {
            $this->line($brief);
            return self::SUCCESS;
        }

        $this->sendEmail($brief);
        $this->info('Brief sent to ' . config('brief.recipient_email', env('ADMIN_EMAIL')));

        return self::SUCCESS;
    }

    // -------------------------------------------------------------------------
    // DATA GATHERING
    // -------------------------------------------------------------------------

    private function gatherProjectData(): string
    {
        $active = Project::whereNotIn('status', ['complete', 'killed'])
            ->orderByRaw('CASE WHEN priority IS NULL THEN 1 ELSE 0 END')
            ->orderBy('priority')
            ->get();

        $awaitingMoney = $active->where('money_status', 'awaiting');
        $blocked = $active->where('status', 'blocked');
        $openIssues = Issue::whereIn('status', ['open', 'in_progress'])
            ->whereHas('project', fn($q) => $q->whereNotIn('status', ['complete', 'killed']))
            ->with('project:id,name')
            ->orderBy('urgency', 'desc')
            ->get();

        $lines = ["## Projects\n"];

        $lines[] = "**Active:** {$active->count()} projects, {$openIssues->count()} open issues";

        if ($awaitingMoney->count()) {
            $total = $awaitingMoney->sum('money_value');
            $lines[] = "\n**Awaiting payment:** £" . number_format($total, 0);
            foreach ($awaitingMoney as $p) {
                $lines[] = "- {$p->name}: £" . number_format($p->money_value, 0);
            }
        }

        if ($blocked->count()) {
            $lines[] = "\n**Blocked:**";
            foreach ($blocked as $p) {
                $lines[] = "- {$p->name}: {$p->next_action}";
            }
        }

        if ($openIssues->count()) {
            $high = $openIssues->where('urgency', 'high');
            if ($high->count()) {
                $lines[] = "\n**High urgency issues:**";
                foreach ($high->take(5) as $i) {
                    $lines[] = "- [{$i->project->name}] {$i->title}";
                }
            }
        }

        $lines[] = "\n**All active projects (priority order):**";
        foreach ($active->take(12) as $p) {
            $flags = [];
            if ($p->money_status === 'awaiting') $flags[] = '£awaiting';
            if ($p->waiting_on_client) $flags[] = 'waiting-on-client';
            $flagStr = $flags ? ' [' . implode(', ', $flags) . ']' : '';
            $lines[] = "- **{$p->name}**{$flagStr}" . ($p->next_action ? " → {$p->next_action}" : '');
        }

        return implode("\n", $lines);
    }

    private function gatherEmails(): string
    {
        $lines = ["## Emails\n"];
        $totalFound = 0;

        foreach ($this->inboxes as $account => $config) {
            $pass = env($config['pass_env']);
            if (! $pass) {
                $lines[] = "({$account}: password not configured — skipping)";
                continue;
            }

            try {
                $emails = $this->fetchRecentEmails($config['host'], $config['port'], $config['user'], $pass, $account);
                if (empty($emails)) {
                    $lines[] = "**{$account}:** No unread emails.";
                } else {
                    $totalFound += count($emails);
                    $lines[] = "**{$account}:** " . count($emails) . " unread/recent:";
                    foreach ($emails as $email) {
                        $lines[] = "- From: {$email['from']} | Subject: {$email['subject']} | {$email['date']}";
                        if ($email['snippet']) {
                            $lines[] = "  Preview: {$email['snippet']}";
                        }
                    }
                }
            } catch (\Throwable $e) {
                $lines[] = "({$account}: IMAP error — {$e->getMessage()})";
                Log::warning("DailyBrief IMAP error for {$account}: " . $e->getMessage());
            }

            $lines[] = '';
        }

        if ($totalFound === 0) {
            $lines[] = "Inbox zero across both accounts.";
        }

        return implode("\n", $lines);
    }

    private function fetchRecentEmails(string $host, int $port, string $user, string $pass, string $account): array
    {
        // Connect via IMAP — requires php-imap extension
        $dsn = "{{$host}:{$port}/imap/ssl/novalidate-cert}INBOX";

        if (! function_exists('imap_open')) {
            Log::warning('DailyBrief: php-imap extension not available');
            return [];
        }

        $mbox = @imap_open($dsn, $user, $pass, 0, 1);
        if (! $mbox) {
            throw new \RuntimeException('IMAP connect failed: ' . imap_last_error());
        }

        try {
            // Search for unread emails in the last 3 days
            $since = date('d-M-Y', strtotime('-3 days'));
            $uids = imap_search($mbox, "UNSEEN SINCE {$since}");

            if (! $uids) {
                return [];
            }

            // Most recent first, cap at 15
            rsort($uids);
            $uids = array_slice($uids, 0, 15);

            $emails = [];
            foreach ($uids as $uid) {
                $header  = imap_headerinfo($mbox, $uid);
                $subject = isset($header->subject) ? imap_utf8($header->subject) : '(no subject)';
                $from    = '';
                if (isset($header->from[0])) {
                    $f    = $header->from[0];
                    $from = isset($f->personal) ? imap_utf8($f->personal) . " <{$f->mailbox}@{$f->host}>" : "{$f->mailbox}@{$f->host}";
                }
                $date    = isset($header->date) ? Carbon::parse($header->date)->diffForHumans() : '';

                // Grab a short plain-text snippet
                $snippet = '';
                $structure = imap_fetchstructure($mbox, $uid);
                if ($structure->type === 0) {
                    // Plain text message
                    $body    = imap_fetchbody($mbox, $uid, '1');
                    $encoding = $structure->encoding ?? 0;
                    $body    = $this->decodeBody($body, $encoding);
                    $snippet = mb_substr(strip_tags(quoted_printable_decode($body)), 0, 200);
                    $snippet = preg_replace('/\s+/', ' ', trim($snippet));
                }

                $emails[] = compact('from', 'subject', 'date', 'snippet');
            }

            return $emails;
        } finally {
            imap_close($mbox);
        }
    }

    private function decodeBody(string $body, int $encoding): string
    {
        return match ($encoding) {
            3 => base64_decode($body),
            4 => quoted_printable_decode($body),
            default => $body,
        };
    }

    private function gatherGitHubActivity(): string
    {
        $token = env('GITHUB_TOKEN');
        if (! $token) {
            return "## GitHub\n(GITHUB_TOKEN not configured)";
        }

        $lines  = ["## GitHub (last 7 days)\n"];
        $since  = Carbon::now()->subDays(7)->toIso8601String();
        $org    = 'fantata';
        $page   = 1;
        $byRepo = [];
        $cutoff = Carbon::now()->subDays(7);

        while ($page <= 5) {
            $response = Http::withToken($token)
                ->withHeaders(['Accept' => 'application/vnd.github.v3+json', 'X-GitHub-Api-Version' => '2022-11-28'])
                ->get("https://api.github.com/orgs/{$org}/events", ['per_page' => 100, 'page' => $page]);

            if (! $response->ok()) break;
            $events = $response->json();
            if (empty($events)) break;

            $done = false;
            foreach ($events as $event) {
                if ($event['type'] !== 'PushEvent') continue;
                $eventDate = Carbon::parse($event['created_at']);
                if ($eventDate->lt($cutoff)) { $done = true; break; }

                $repo    = str_replace("{$org}/", '', $event['repo']['name']);
                $commits = $event['payload']['commits'] ?? [];
                foreach ($commits as $commit) {
                    if (str_starts_with($commit['message'], 'Merge ')) continue;
                    $byRepo[$repo][] = [
                        'message' => strtok($commit['message'], "\n"),
                        'date'    => $eventDate->toDateString(),
                    ];
                }
            }

            if ($done || count($events) < 100) break;
            $page++;
        }

        if (empty($byRepo)) {
            $lines[] = "No push activity in the last 7 days.";
            return implode("\n", $lines);
        }

        // Map repos to project names
        $repoMap = Project::whereNotNull('github_repo')
            ->pluck('name', 'github_repo')
            ->mapWithKeys(fn($name, $repo) => [str_replace("{$org}/", '', $repo) => $name])
            ->toArray();

        arsort($byRepo);
        foreach ($byRepo as $repo => $commits) {
            $projectName = $repoMap[$repo] ?? null;
            $label       = $projectName ? "{$projectName} ({$repo})" : $repo;
            $lines[]     = "**{$label}** — " . count($commits) . " commits:";
            foreach (array_slice($commits, 0, 5) as $c) {
                $lines[] = "  - [{$c['date']}] {$c['message']}";
            }
        }

        return implode("\n", $lines);
    }

    private function gatherFreeAgentData(): string
    {
        // Hit both FreeAgent accounts (Fantata + Dogface) for unpaid invoices
        $lines = ["## Finances\n"];

        // Tokens can be supplied two ways:
        // 1. FREEAGENT_FANTATA_ACCESS_TOKEN / FREEAGENT_DOGFACE_ACCESS_TOKEN env vars (preferred on prod)
        // 2. Token files (local dev) — reads accessToken key (camelCase, matching freeagent-mcp format)
        $accounts = [
            'fantata' => [
                'token_env'  => 'FREEAGENT_FANTATA_ACCESS_TOKEN',
                'token_file' => env('FREEAGENT_FANTATA_TOKEN_FILE', '/Users/chris/Code/Projects/freeagent-mcp/.tokens-a.json'),
            ],
            'dogface' => [
                'token_env'  => 'FREEAGENT_DOGFACE_ACCESS_TOKEN',
                'token_file' => env('FREEAGENT_DOGFACE_TOKEN_FILE', '/Users/chris/Code/Projects/freeagent-mcp/.tokens-b.json'),
            ],
        ];

        foreach ($accounts as $name => $config) {
            // Prefer explicit env var, fall back to token file
            $accessToken = env($config['token_env']);
            if (! $accessToken) {
                $tokenFile = $config['token_file'];
                if (file_exists($tokenFile)) {
                    $tokens      = json_decode(file_get_contents($tokenFile), true);
                    $accessToken = $tokens['accessToken'] ?? $tokens['access_token'] ?? null;
                }
            }

            try {
                if (! $accessToken) {
                    $lines[] = "**{$name}:** no token configured — skipping";
                    continue;
                }

                $response = Http::withToken($accessToken)
                    ->withHeaders(['Accept' => 'application/json'])
                    ->get('https://api.freeagent.com/v2/invoices', [
                        'filter_by' => 'open',
                        'per_page'  => 20,
                        'sort'      => 'created_at',
                        'order'     => 'desc',
                    ]);

                if ($response->status() === 401) {
                    $lines[] = "**{$name}:** FreeAgent token expired — needs re-auth";
                    continue;
                }

                if (! $response->ok()) {
                    $lines[] = "**{$name}:** FreeAgent API error {$response->status()}";
                    continue;
                }

                $invoices = $response->json('invoices', []);
                if (empty($invoices)) {
                    $lines[] = "**{$name}:** No open invoices.";
                    continue;
                }

                $total = array_sum(array_column($invoices, 'due_value'));
                $lines[] = "**{$name}:** " . count($invoices) . " open invoice(s), total due: £" . number_format($total, 2);
                foreach (array_slice($invoices, 0, 5) as $inv) {
                    $due     = isset($inv['due_on']) ? Carbon::parse($inv['due_on'])->toDateString() : 'no due date';
                    $overdue = isset($inv['due_on']) && Carbon::parse($inv['due_on'])->isPast() ? ' ⚠️ OVERDUE' : '';
                    $lines[] = "  - {$inv['contact_name']} — £" . number_format($inv['due_value'], 2) . " (due {$due}){$overdue}";
                }
            } catch (\Throwable $e) {
                $lines[] = "**{$name}:** error — {$e->getMessage()}";
                Log::warning("DailyBrief FreeAgent error for {$name}: " . $e->getMessage());
            }

            $lines[] = '';
        }

        return implode("\n", $lines);
    }

    private function gatherDailyNotes(): string
    {
        $notes = DailyNote::where('created_at', '>=', Carbon::now()->subDays(3))
            ->orderByDesc('created_at')
            ->get();

        if ($notes->isEmpty()) {
            return "## Recent notes\nNo personal notes in the last 3 days.";
        }

        $lines = ["## Recent notes\n"];
        foreach ($notes as $note) {
            $energy  = $note->energy_level ? " [{$note->energy_level} energy]" : '';
            $lines[] = "- {$note->created_at->diffForHumans()}{$energy}: {$note->body}";
        }

        return implode("\n", $lines);
    }

    // -------------------------------------------------------------------------
    // AI SYNTHESIS
    // -------------------------------------------------------------------------

    private function generateBrief(array $context): string
    {
        $anthropicKey = env('ANTHROPIC_API_KEY');
        if (! $anthropicKey) {
            // Fallback: plain structured dump without AI synthesis
            return $this->plaintextFallback($context);
        }

        $hour    = now()->hour;
        $session = $hour < 13 ? 'morning' : 'afternoon';
        $date    = now()->format('l j F Y');

        $prompt = <<<PROMPT
You are Chris's AI PA. Today is {$date}, {$session} session.

Chris is a solo developer and improviser based in Norwich. He runs two businesses:
Fantata (web/app dev consultancy) and Dogface Improv (improv school + corporate training).
He has Long COVID which causes energy crashes and ADHD which makes reprioritisation after gaps difficult.

Below is a data snapshot from his systems. Your job is to produce a concise, actionable daily brief.

FORMAT RULES:
- Lead with the single most important thing he should do first, and why
- Flag anything genuinely urgent (overdue invoices, emails that need a reply today)
- Group emails: identify which need action vs which are FYI
- Note if energy from recent personal notes is low — suggest lighter tasks if so
- Keep it scannable — bullets, not paragraphs
- End with a "Today's 3 priorities" list
- Plain text only, no markdown headers (this will be an email)
- Be direct, equal-status, no sycophancy, mild deadpan humour fine

---

{$context['notes']}

---

{$context['projects']}

---

{$context['github']}

---

{$context['freeagent']}

---

{$context['emails']}

PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key'         => $anthropicKey,
                'anthropic-version' => '2023-06-01',
                'content-type'      => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model'      => 'claude-sonnet-4-20250514',
                'max_tokens' => 1500,
                'messages'   => [
                    ['role' => 'user', 'content' => $prompt],
                ],
            ]);

            if ($response->ok()) {
                return $response->json('content.0.text', $this->plaintextFallback($context));
            }

            Log::warning('DailyBrief Anthropic API error: ' . $response->status() . ' ' . $response->body());
        } catch (\Throwable $e) {
            Log::warning('DailyBrief Anthropic API exception: ' . $e->getMessage());
        }

        return $this->plaintextFallback($context);
    }

    private function plaintextFallback(array $context): string
    {
        return implode("\n\n---\n\n", $context);
    }

    // -------------------------------------------------------------------------
    // EMAIL DELIVERY
    // -------------------------------------------------------------------------

    private function sendEmail(string $brief): void
    {
        $session   = now()->hour < 13 ? 'Morning' : 'Afternoon';
        $date      = now()->format('D j M');
        $recipient = config('brief.recipient_email', env('ADMIN_EMAIL', 'chris@fantata.com'));

        Mail::raw($brief, function ($message) use ($recipient, $session, $date) {
            $message->to($recipient)
                ->subject("📋 {$session} Brief — {$date}")
                ->from(env('MAIL_FROM_ADDRESS', 'brief@fantata.com'), 'Project Juggler');
        });
    }
}
