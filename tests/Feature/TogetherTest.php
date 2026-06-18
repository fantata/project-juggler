<?php

namespace Tests\Feature;

use App\Livewire\Messenger;
use App\Livewire\Together;
use App\Models\Issue;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class TogetherTest extends TestCase
{
    use RefreshDatabase;

    public function test_together_requires_authentication(): void
    {
        $this->get('/together')->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_together(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/together')
            ->assertOk()
            ->assertSeeLivewire(Together::class);
    }

    public function test_adding_an_item_creates_an_issue_on_the_shared_project(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Together::class)
            ->set('title', 'Book the dentist')
            ->set('note', 'The one on Magdalen Street')
            ->set('due_bucket', 'this_week')
            ->set('is_question', false)
            ->call('addItem')
            ->assertHasNoErrors();

        $issue = Issue::first();

        $this->assertNotNull($issue);
        $this->assertSame('Book the dentist', $issue->title);
        $this->assertSame('this_week', $issue->due_bucket->value);
        $this->assertSame('Shared', $issue->project->name);
    }

    public function test_an_item_can_be_flagged_as_a_question(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Together::class)
            ->set('title', 'Are we free on Saturday?')
            ->set('is_question', true)
            ->call('addItem')
            ->assertHasNoErrors();

        $this->assertTrue(Issue::first()->is_question);
    }

    public function test_adding_an_item_requires_a_title(): void
    {
        $this->actingAs(User::factory()->create());

        Livewire::test(Together::class)
            ->set('title', '')
            ->call('addItem')
            ->assertHasErrors(['title' => 'required']);
    }

    public function test_a_user_can_post_a_message(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        Livewire::test(Messenger::class)
            ->set('body', 'On my way home now')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('body', '');

        $message = Message::first();

        $this->assertSame('On my way home now', $message->body);
        $this->assertSame($user->id, $message->sender_id);
    }

    public function test_open_issues_are_grouped_by_due_bucket(): void
    {
        $this->actingAs(User::factory()->create());

        $project = Project::create([
            'name' => 'Shared',
            'type' => 'personal',
            'status' => 'active',
            'money_status' => 'none',
        ]);

        Issue::create(['project_id' => $project->id, 'title' => 'Today thing', 'status' => 'open', 'urgency' => 'medium', 'due_bucket' => 'today']);
        Issue::create(['project_id' => $project->id, 'title' => 'Someday thing', 'status' => 'open', 'urgency' => 'medium', 'due_bucket' => 'whenever']);
        Issue::create(['project_id' => $project->id, 'title' => 'Unbucketed thing', 'status' => 'open', 'urgency' => 'medium']);

        Livewire::test(Together::class)
            ->assertSee('Today thing')
            ->assertSee('Someday thing')
            ->assertSee('Unbucketed thing')
            ->assertSee('No timeframe yet');
    }
}
