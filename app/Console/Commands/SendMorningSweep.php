<?php

namespace App\Console\Commands;

use App\Mail\MorningSweep;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

class SendMorningSweep extends Command
{
    protected $signature = 'sweep:send {--dry-run : Print what each person would get instead of emailing}';

    protected $description = 'Morning sweep: email each person what needs their attention on the shared boards.';

    public function handle(): int
    {
        $users = User::all();
        $sent = 0;

        foreach ($users as $user) {
            $awaitingYou = $this->questionsAwaiting($user);
            $assigned = $this->openAssigned($user);

            if ($awaitingYou->isEmpty() && $assigned->isEmpty()) {
                continue;
            }

            if ($this->option('dry-run')) {
                $this->line("— {$user->name}: {$awaitingYou->count()} awaiting answer, {$assigned->count()} on their plate");

                continue;
            }

            $summary = $this->aiSummary($user, $awaitingYou, $assigned);
            Mail::to($user->email)->send(new MorningSweep($user, $awaitingYou, $assigned, $summary));
            $sent++;
        }

        $this->info("Morning sweep: emailed {$sent} of {$users->count()}.");

        return self::SUCCESS;
    }

    /** Yes/no questions pointed at this person, still unanswered. */
    private function questionsAwaiting(User $user)
    {
        return Issue::with('project')
            ->where('assignee_id', $user->id)
            ->where('is_question', true)
            ->whereNull('answer')
            ->whereHas('project', fn ($q) => $q->active())
            ->orderByDesc('created_at')
            ->get();
    }

    /** Open cards on this person's plate (excludes the pending questions above). */
    private function openAssigned(User $user)
    {
        return Issue::with('project')
            ->where('assignee_id', $user->id)
            ->whereIn('status', ['open', 'in_progress'])
            ->where(fn ($q) => $q->where('is_question', false)->orWhereNotNull('answer'))
            ->whereHas('project', fn ($q) => $q->active())
            ->orderByDesc('updated_at')
            ->get();
    }

    /**
     * A one-line, warm AI opener for the email — best effort. Returns null when
     * there's no API key or the call fails, and the email simply omits it.
     */
    private function aiSummary(User $user, Collection $awaitingYou, Collection $assigned): ?string
    {
        $key = env('ANTHROPIC_API_KEY');

        if (! $key) {
            return null;
        }

        $lines = $awaitingYou->map(fn (Issue $q) => "- (awaiting your yes/no) {$q->title} [{$q->project->name}]")
            ->merge($assigned->map(fn (Issue $c) => '- '.$c->title." [{$c->project->name}]".($c->urgency->value === 'high' ? ' [high]' : '')))
            ->implode("\n");

        $prompt = <<<PROMPT
        You are a warm, brisk PA for {$user->name}, who runs improv shows with one teammate.
        Write ONE short, friendly sentence (max 20 words) to open their morning nudge email,
        reflecting what's on their plate below. No greeting, no sign-off — just the sentence.
        Warm, a little playful, never corporate.

        {$lines}
        PROMPT;

        try {
            $response = Http::withHeaders([
                'x-api-key' => $key,
                'anthropic-version' => '2023-06-01',
                'content-type' => 'application/json',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 100,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);

            if ($response->ok()) {
                return trim((string) $response->json('content.0.text')) ?: null;
            }

            Log::warning('MorningSweep Anthropic error: '.$response->status());
        } catch (\Throwable $e) {
            Log::warning('MorningSweep Anthropic exception: '.$e->getMessage());
        }

        return null;
    }
}
