<?php

namespace Tests\Feature;

use App\Mail\MorningSweep;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendMorningSweepTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        return Project::create(['name' => 'Panto', 'type' => 'personal', 'status' => 'active']);
    }

    public function test_it_emails_a_user_with_a_question_awaiting_them(): void
    {
        Mail::fake();
        $danny = User::factory()->create(['email' => 'danny@example.com']);
        Issue::create([
            'project_id' => $this->project()->id, 'title' => 'Book the 2pm slot?',
            'is_question' => true, 'assignee_id' => $danny->id,
        ]);

        $this->artisan('sweep:send')->assertSuccessful();

        Mail::assertSent(MorningSweep::class, fn ($mail) => $mail->hasTo('danny@example.com'));
    }

    public function test_it_includes_assigned_open_cards(): void
    {
        Mail::fake();
        $danny = User::factory()->create();
        Issue::create([
            'project_id' => $this->project()->id, 'title' => 'Build the set',
            'status' => 'open', 'assignee_id' => $danny->id,
        ]);

        $this->artisan('sweep:send')->assertSuccessful();

        Mail::assertSent(MorningSweep::class, fn ($mail) => $mail->assigned->count() === 1);
    }

    public function test_it_skips_someone_with_nothing(): void
    {
        Mail::fake();
        User::factory()->create();

        $this->artisan('sweep:send')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_dry_run_sends_nothing(): void
    {
        Mail::fake();
        $danny = User::factory()->create();
        Issue::create([
            'project_id' => $this->project()->id, 'title' => 'q',
            'is_question' => true, 'assignee_id' => $danny->id,
        ]);

        $this->artisan('sweep:send --dry-run')->assertSuccessful();

        Mail::assertNothingSent();
    }

    public function test_a_pending_question_is_not_double_listed_as_assigned(): void
    {
        Mail::fake();
        $danny = User::factory()->create();
        Issue::create([
            'project_id' => $this->project()->id, 'title' => 'Pending Q',
            'is_question' => true, 'assignee_id' => $danny->id, 'status' => 'open',
        ]);

        $this->artisan('sweep:send')->assertSuccessful();

        Mail::assertSent(MorningSweep::class, fn ($mail) => $mail->awaitingYou->count() === 1 && $mail->assigned->count() === 0);
    }
}
