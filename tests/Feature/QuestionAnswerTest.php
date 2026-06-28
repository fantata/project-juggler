<?php

namespace Tests\Feature;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class QuestionAnswerTest extends TestCase
{
    use RefreshDatabase;

    private function question(bool $isQuestion = true): Issue
    {
        $project = Project::create(['name' => 'Panto', 'type' => 'personal', 'status' => 'active']);

        return Issue::create([
            'project_id' => $project->id,
            'title' => 'Book the 2pm slot?',
            'is_question' => $isQuestion,
        ]);
    }

    public function test_a_signed_link_records_the_answer_without_login(): void
    {
        $issue = $this->question();
        $url = URL::signedRoute('questions.answer', ['issue' => $issue->id, 'answer' => 'yes']);

        $this->get($url)
            ->assertOk()
            ->assertSee('logged');

        $this->assertSame('yes', $issue->fresh()->answer);
        $this->assertNotNull($issue->fresh()->answered_at);
    }

    public function test_an_unsigned_link_is_rejected(): void
    {
        $issue = $this->question();

        $this->get(route('questions.answer', ['issue' => $issue->id, 'answer' => 'yes']))
            ->assertForbidden();

        $this->assertNull($issue->fresh()->answer);
    }

    public function test_an_invalid_answer_value_is_404(): void
    {
        $issue = $this->question();
        $url = URL::signedRoute('questions.answer', ['issue' => $issue->id, 'answer' => 'maybe']);

        $this->get($url)->assertNotFound();
        $this->assertNull($issue->fresh()->answer);
    }

    public function test_a_non_question_is_404(): void
    {
        $issue = $this->question(isQuestion: false);
        $url = URL::signedRoute('questions.answer', ['issue' => $issue->id, 'answer' => 'yes']);

        $this->get($url)->assertNotFound();
        $this->assertNull($issue->fresh()->answer);
    }
}
