<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\View\View;

class QuestionAnswerController extends Controller
{
    /**
     * Record a one-click yes/no answer from the emailed link. The route's
     * 'signed' middleware is the auth here — the signature ties the link to
     * this exact issue + answer, so no login is needed.
     */
    public function __invoke(Issue $issue, string $answer): View
    {
        abort_unless(in_array($answer, ['yes', 'no'], true), 404);
        abort_unless($issue->is_question, 404);

        $issue->update([
            'answer' => $answer,
            'answered_at' => now(),
        ]);
        $issue->project->update(['last_touched_at' => now()]);

        return view('questions.answered', [
            'issue' => $issue,
            'answer' => $answer,
        ]);
    }
}
