<?php

namespace App\Http\Controllers;

use App\Models\Issue;
use Illuminate\Support\Facades\URL;
use Illuminate\View\View;

class QuestionAnswerController extends Controller
{
    /**
     * Show a confirmation page for the emailed link. This is a GET, so it must
     * be side-effect free: email security scanners pre-fetch links, and a GET
     * that recorded the answer would let a scanner auto-answer (or, fetching
     * both links, set whichever ran last). The actual write happens on POST.
     */
    public function show(Issue $issue, string $answer): View
    {
        abort_unless(in_array($answer, ['yes', 'no'], true), 404);
        abort_unless($issue->is_question, 404);

        return view('questions.confirm', [
            'issue' => $issue,
            'answer' => $answer,
            'commitUrl' => URL::signedRoute('questions.answer.commit', [
                'issue' => $issue->id,
                'answer' => $answer,
            ]),
        ]);
    }

    /**
     * Record the answer. Reached only by the human pressing Confirm — a POST,
     * so it carries a CSRF token and can't be triggered by a link pre-fetch.
     * The 'signed' middleware still validates the signature.
     */
    public function store(Issue $issue, string $answer): View
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
