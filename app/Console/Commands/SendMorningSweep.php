<?php

namespace App\Console\Commands;

use App\Mail\MorningSweep;
use App\Models\Issue;
use App\Models\User;
use Illuminate\Console\Command;
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

            Mail::to($user->email)->send(new MorningSweep($user, $awaitingYou, $assigned));
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
}
