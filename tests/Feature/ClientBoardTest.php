<?php

namespace Tests\Feature;

use App\Livewire\ClientBoard;
use App\Models\Issue;
use App\Models\Project;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use Tests\TestCase;

class ClientBoardTest extends TestCase
{
    use RefreshDatabase;

    /** A project with its client board switched on, returned fresh with a token. */
    private function sharedProject(string $name = 'Client site'): Project
    {
        $project = Project::create(['name' => $name, 'type' => 'client', 'status' => 'active']);
        $project->enableClientBoard();

        return $project->fresh();
    }

    /** Introduce the guest so write actions are allowed. */
    private function asGuest(Project $project, string $name = 'Sarah', string $key = 'guest-key-1'): \Livewire\Features\SupportTesting\Testable
    {
        return Livewire::test(ClientBoard::class, ['token' => $project->share_token])
            ->set('guestKey', $key)
            ->set('nameInput', $name)
            ->call('saveName');
    }

    public function test_board_loads_with_a_valid_enabled_token(): void
    {
        $project = $this->sharedProject('Acme redesign');
        Issue::create(['project_id' => $project->id, 'title' => 'Homepage draft', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->get(route('board.show', $project->share_token))
            ->assertOk()
            ->assertSee('Acme redesign')
            ->assertSee('Homepage draft');
    }

    public function test_a_disabled_board_is_not_found(): void
    {
        $project = $this->sharedProject();
        $project->disableClientBoard();

        $this->get(route('board.show', $project->share_token))->assertNotFound();
    }

    public function test_an_unknown_token_is_not_found(): void
    {
        $this->get(route('board.show', 'nope-not-a-real-token'))->assertNotFound();
    }

    public function test_only_this_projects_cards_appear(): void
    {
        $mine = $this->sharedProject('Mine');
        $theirs = Project::create(['name' => 'Theirs', 'type' => 'client', 'status' => 'active']);
        Issue::create(['project_id' => $mine->id, 'title' => 'My card', 'board_column' => 'todo', 'is_client_visible' => true]);
        Issue::create(['project_id' => $theirs->id, 'title' => 'Their secret card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->get(route('board.show', $mine->share_token))
            ->assertOk()
            ->assertSee('My card')
            ->assertDontSee('Their secret card');
    }

    public function test_internal_cards_are_hidden_from_the_client_board(): void
    {
        $project = $this->sharedProject('Acme');
        Issue::create(['project_id' => $project->id, 'title' => 'Client-facing card', 'board_column' => 'todo', 'is_client_visible' => true]);
        // Default is_client_visible = false — internal, must never reach the board.
        Issue::create(['project_id' => $project->id, 'title' => 'Internal billing note', 'board_column' => 'todo']);

        $this->get(route('board.show', $project->share_token))
            ->assertOk()
            ->assertSee('Client-facing card')
            ->assertDontSee('Internal billing note');
    }

    public function test_a_guest_cannot_write_before_naming_themselves(): void
    {
        $project = $this->sharedProject();

        Livewire::test(ClientBoard::class, ['token' => $project->share_token])
            ->set('newTitle', 'Sneaky anonymous card')
            ->call('addCard');

        $this->assertSame(0, $project->issues()->count());
    }

    public function test_a_named_guest_can_add_a_card_scoped_to_the_project(): void
    {
        $project = $this->sharedProject();

        $this->asGuest($project, 'Sarah', 'k1')
            ->set('newTitle', 'Please tweak the logo')
            ->set('newDescription', 'A bit smaller')
            ->call('addCard')
            ->assertHasNoErrors();

        $card = $project->issues()->first();
        $this->assertSame('Please tweak the logo', $card->title);
        $this->assertSame('Sarah', $card->guest_name);
        $this->assertSame('k1', $card->guest_key);
        $this->assertSame('open', $card->status->value);
    }

    public function test_a_guest_can_comment_and_delete_only_their_own(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        // Sarah comments.
        $sarah = $this->asGuest($project, 'Sarah', 'sarah-key')
            ->call('openCard', $issue->id)
            ->set('commentBody', 'Love it')
            ->call('addComment')
            ->assertHasNoErrors();

        $comment = $issue->comments()->first();
        $this->assertSame('Sarah', $comment->guest_name);

        // A different guest cannot delete Sarah's comment.
        Livewire::test(ClientBoard::class, ['token' => $project->share_token])
            ->set('guestKey', 'someone-else')
            ->set('nameInput', 'Mallory')->call('saveName')
            ->call('openCard', $issue->id)
            ->call('deleteOwnComment', $comment->id);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);

        // Sarah can delete her own.
        $sarah->call('deleteOwnComment', $comment->id);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
    }

    public function test_a_reaction_toggles_per_guest(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $sarah = $this->asGuest($project, 'Sarah', 'sarah-key');
        $sarah->call('react', $issue->id, 'approve');
        $this->assertSame(1, $issue->reactions()->count());

        // A second guest reacting the same way is a distinct reaction.
        $this->asGuest($project, 'Danny', 'danny-key')->call('react', $issue->id, 'approve');
        $this->assertSame(2, $issue->reactions()->count());

        // Sarah toggling hers off leaves Danny's intact.
        $sarah->call('react', $issue->id, 'approve');
        $this->assertSame(1, $issue->reactions()->count());
    }

    public function test_an_unknown_reaction_key_is_ignored(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->asGuest($project)->call('react', $issue->id, 'poop');

        $this->assertSame(0, $issue->reactions()->count());
    }

    public function test_a_guest_can_upload_a_file_but_not_an_executable_type(): void
    {
        Storage::fake('local');
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $guest = $this->asGuest($project)->call('openCard', $issue->id);

        $guest->set('files', [UploadedFile::fake()->image('screenshot.png')])->assertHasNoErrors();
        $this->assertSame(1, $issue->fresh()->attachments()->count());

        $guest->set('files', [UploadedFile::fake()->create('xss.svg', 2, 'image/svg+xml')])
            ->assertHasErrors('files.*');
        $this->assertSame(1, $issue->fresh()->attachments()->count());
    }

    public function test_shared_attachment_streams_for_its_own_project(): void
    {
        Storage::fake('local');
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->asGuest($project)
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->image('shot.jpg')]);

        $attachment = $issue->fresh()->attachments()->first();

        $this->get(route('board.file', ['token' => $project->share_token, 'attachment' => $attachment->id]))
            ->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
    }

    public function test_one_boards_link_cannot_reach_another_projects_file(): void
    {
        Storage::fake('local');
        $mine = $this->sharedProject('Mine');
        $theirs = Project::create(['name' => 'Theirs', 'type' => 'client', 'status' => 'active']);
        $theirIssue = Issue::create(['project_id' => $theirs->id, 'title' => 'Theirs', 'board_column' => 'todo', 'is_client_visible' => true]);
        $theirAttachment = $theirIssue->attachments()->create([
            'disk' => 'local', 'path' => 'attachments/x/secret.jpg',
            'original_name' => 'secret.jpg', 'mime_type' => 'image/jpeg', 'size' => 10,
        ]);

        // Using MY token to fetch THEIR file must 404.
        $this->get(route('board.file', ['token' => $mine->share_token, 'attachment' => $theirAttachment->id]))
            ->assertNotFound();
    }

    public function test_rotating_the_token_kills_the_old_link(): void
    {
        $project = $this->sharedProject();
        $old = $project->share_token;

        $project->rotateShareToken();

        $this->assertNotSame($old, $project->fresh()->share_token);
        $this->get(route('board.show', $old))->assertNotFound();
        $this->get(route('board.show', $project->fresh()->share_token))->assertOk();
    }
}
