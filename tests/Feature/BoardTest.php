<?php

namespace Tests\Feature;

use App\Livewire\Board;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class BoardTest extends TestCase
{
    use RefreshDatabase;

    private function project(): Project
    {
        return Project::create([
            'name' => 'Panto',
            'type' => 'personal',
            'status' => 'active',
        ]);
    }

    public function test_board_renders_columns_and_places_cards(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();

        Issue::create(['project_id' => $project->id, 'title' => 'Design poster', 'board_column' => 'todo']);
        Issue::create(['project_id' => $project->id, 'title' => 'Book venue', 'board_column' => 'doing']);

        Livewire::test(Board::class, ['project' => $project])
            ->assertSee('Ideas')
            ->assertSee('To do')
            ->assertSee('Doing')
            ->assertSee('Done')
            ->assertSee('Design poster')
            ->assertSee('Book venue');
    }

    public function test_board_page_loads_for_an_authenticated_user(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        Issue::create(['project_id' => $project->id, 'title' => 'A real card', 'board_column' => 'todo']);

        $this->get(route('projects.board', $project))
            ->assertOk()
            ->assertSee('Board')
            ->assertSee('A real card');
    }

    public function test_board_requires_authentication(): void
    {
        $this->get(route('projects.board', $this->project()))
            ->assertRedirect(route('login'));
    }

    public function test_move_card_changes_its_column(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Sound cues', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('moveCard', $issue->id, 'doing');

        $this->assertSame('doing', $issue->fresh()->board_column);
    }

    public function test_move_card_ignores_an_unknown_column(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Leave me', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('moveCard', $issue->id, 'nonsense');

        $this->assertSame('todo', $issue->fresh()->board_column);
    }

    public function test_a_card_can_be_assigned_and_unassigned(): void
    {
        $this->actingAs(User::factory()->create());
        $danny = User::factory()->create(['name' => 'Danny']);
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Sound design', 'board_column' => 'todo']);

        $component = Livewire::test(Board::class, ['project' => $project]);

        $component->call('assignCard', $issue->id, $danny->id);
        $this->assertSame($danny->id, $issue->fresh()->assignee_id);

        $component->call('assignCard', $issue->id, null);
        $this->assertNull($issue->fresh()->assignee_id);
    }

    public function test_assigning_an_unknown_user_is_ignored(): void
    {
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'X', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('assignCard', $issue->id, 99999);

        $this->assertNull($issue->fresh()->assignee_id);
    }
}
