<?php

namespace Tests\Feature;

use App\Livewire\Board;
use App\Models\Attachment;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_files_can_be_dropped_onto_a_card(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Poster', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->image('poster.jpg')])
            ->assertHasNoErrors();

        $attachment = $issue->fresh()->attachments()->first();
        $this->assertNotNull($attachment);
        $this->assertSame('poster.jpg', $attachment->original_name);
        Storage::disk('local')->assertExists($attachment->path);
    }

    public function test_executable_file_types_are_rejected(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Poster', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->create('xss.svg', 2, 'image/svg+xml')])
            ->assertHasErrors('files.*');

        $this->assertSame(0, $issue->fresh()->attachments()->count());
    }

    public function test_deleting_an_attachment_removes_the_file(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Poster', 'board_column' => 'todo']);

        $component = Livewire::test(Board::class, ['project' => $project])
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->create('cue.mp3', 200, 'audio/mpeg')]);

        $attachment = $issue->fresh()->attachments()->first();
        $path = $attachment->path;

        $component->call('deleteAttachment', $attachment->id);

        $this->assertSame(0, $issue->fresh()->attachments()->count());
        Storage::disk('local')->assertMissing($path);
    }

    public function test_cannot_delete_an_attachment_from_another_project(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $mine = $this->project();
        $theirs = $this->project();
        $otherIssue = Issue::create(['project_id' => $theirs->id, 'title' => 'Theirs', 'board_column' => 'todo']);
        $attachment = $otherIssue->attachments()->create([
            'disk' => 'local', 'path' => 'attachments/x/keep.jpg',
            'original_name' => 'keep.jpg', 'mime_type' => 'image/jpeg', 'size' => 10,
        ]);

        Livewire::test(Board::class, ['project' => $mine])
            ->call('deleteAttachment', $attachment->id);

        $this->assertDatabaseHas('attachments', ['id' => $attachment->id]);
    }

    public function test_image_attachment_streams_inline_with_nosniff(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Poster', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->image('poster.jpg')]);

        $this->get(route('attachments.show', $issue->fresh()->attachments()->first()))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_non_image_attachment_is_forced_to_download(): void
    {
        Storage::fake('local');
        $this->actingAs(User::factory()->create());
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Cue', 'board_column' => 'todo']);

        Livewire::test(Board::class, ['project' => $project])
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->create('cue.mp3', 100, 'audio/mpeg')]);

        $response = $this->get(route('attachments.show', $issue->fresh()->attachments()->first()));

        $response->assertOk();
        $this->assertSame('application/octet-stream', $response->headers->get('Content-Type'));
        $this->assertStringContainsString('attachment', (string) $response->headers->get('Content-Disposition'));
    }

    public function test_attachment_download_requires_authentication(): void
    {
        $project = $this->project();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'X', 'board_column' => 'todo']);
        $attachment = $issue->attachments()->create([
            'disk' => 'local', 'path' => 'attachments/x/f.jpg',
            'original_name' => 'f.jpg', 'mime_type' => 'image/jpeg', 'size' => 10,
        ]);

        $this->get(route('attachments.show', $attachment))
            ->assertRedirect(route('login'));
    }
}
