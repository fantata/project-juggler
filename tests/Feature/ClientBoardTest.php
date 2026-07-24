<?php

namespace Tests\Feature;

use App\Livewire\ClientBoard;
use App\Models\Issue;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Features\SupportTesting\Testable;
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
    private function asGuest(Project $project, string $name = 'Sarah', string $key = 'guest-key-1'): Testable
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

    public function test_forcing_open_an_internal_card_leaks_nothing(): void
    {
        $project = $this->sharedProject();
        $internal = Issue::create([
            'project_id' => $project->id,
            'title' => 'Internal only card',
            'description' => 'Rate rise to discuss privately',
            'board_column' => 'todo',
            'is_client_visible' => false,
        ]);
        $internal->comments()->create(['body' => 'Private note', 'guest_name' => 'Chris']);

        // openCardId is a public property, so a visitor can set it to any id.
        Livewire::test(ClientBoard::class, ['token' => $project->share_token])
            ->set('openCardId', $internal->id)
            ->assertDontSee('Internal only card')
            ->assertDontSee('Rate rise to discuss privately')
            ->assertDontSee('Private note');
    }

    public function test_a_guest_can_edit_their_own_card_and_it_is_marked_edited(): void
    {
        $project = $this->sharedProject();

        $guest = $this->asGuest($project, 'Sarah', 'sarah-key')
            ->set('newTitle', 'Tweak the logo')
            ->call('addCard');

        $card = $project->issues()->first();
        $this->assertNull($card->edited_at);

        $guest->call('openCard', $card->id)
            ->call('editCard', $card->id)
            ->assertSet('editTitle', 'Tweak the logo')
            ->set('editTitle', 'Tweak the logo, smaller')
            ->set('editDescription', 'About 20% down')
            ->call('updateCard')
            ->assertHasNoErrors();

        $card->refresh();
        $this->assertSame('Tweak the logo, smaller', $card->title);
        $this->assertSame('About 20% down', $card->description);
        $this->assertNotNull($card->edited_at);
        $this->assertTrue($card->wasEdited());
    }

    public function test_a_guest_cannot_edit_or_delete_someone_elses_card(): void
    {
        $project = $this->sharedProject();

        $this->asGuest($project, 'Sarah', 'sarah-key')
            ->set('newTitle', 'Sarahs card')
            ->call('addCard');

        $card = $project->issues()->first();

        // Mallory knows the board link but not Sarah's guest key.
        $mallory = $this->asGuest($project, 'Mallory', 'mallory-key')
            ->call('openCard', $card->id);

        $mallory->call('editCard', $card->id)->assertSet('editingCardId', null);

        // Even forcing the editor open server-side must not let the update land.
        $mallory->set('editingCardId', $card->id)
            ->set('editTitle', 'Hijacked')
            ->call('updateCard');

        $mallory->call('deleteOwnCard', $card->id);

        $card->refresh();
        $this->assertSame('Sarahs card', $card->title);
        $this->assertNull($card->edited_at);
        $this->assertDatabaseHas('issues', ['id' => $card->id]);
    }

    public function test_a_guest_cannot_edit_an_internal_card(): void
    {
        $project = $this->sharedProject();
        // Raised by Chris on the internal board — no guest_key.
        $internal = Issue::create(['project_id' => $project->id, 'title' => 'Internal card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $guest = $this->asGuest($project, 'Sarah', 'sarah-key')->call('openCard', $internal->id);

        $guest->call('editCard', $internal->id)->assertSet('editingCardId', null);
        $guest->call('deleteOwnCard', $internal->id);

        $this->assertDatabaseHas('issues', ['id' => $internal->id, 'title' => 'Internal card']);
    }

    public function test_an_unnamed_guest_with_no_key_cannot_touch_any_card(): void
    {
        $project = $this->sharedProject();
        $internal = Issue::create(['project_id' => $project->id, 'title' => 'Internal card', 'board_column' => 'todo', 'is_client_visible' => true]);

        // No name entered, so guestKey is blank — it must match nothing.
        Livewire::test(ClientBoard::class, ['token' => $project->share_token])
            ->set('guestKey', '')
            ->call('editCard', $internal->id)
            ->assertSet('editingCardId', null)
            ->call('deleteOwnCard', $internal->id);

        $this->assertDatabaseHas('issues', ['id' => $internal->id]);
    }

    public function test_deleting_a_card_takes_its_comments_tasks_and_files_with_it(): void
    {
        Storage::fake('local');
        $project = $this->sharedProject();

        $guest = $this->asGuest($project, 'Sarah', 'sarah-key')
            ->set('newTitle', 'Card to bin')
            ->call('addCard');

        $card = $project->issues()->first();

        $guest->call('openCard', $card->id)
            ->set('commentBody', 'A thought')
            ->call('addComment')
            ->set('files', [UploadedFile::fake()->image('shot.jpg')]);

        $card->tasks()->create(['description' => 'A subtask', 'position' => 1]);

        $comment = $card->comments()->first();
        $attachment = $card->attachments()->first();
        $this->assertNotNull($comment);
        $this->assertNotNull($attachment);
        Storage::disk('local')->assertExists($attachment->path);

        $guest->call('deleteOwnCard', $card->id);

        $this->assertDatabaseMissing('issues', ['id' => $card->id]);
        $this->assertDatabaseMissing('comments', ['id' => $comment->id]);
        $this->assertDatabaseMissing('attachments', ['id' => $attachment->id]);
        $this->assertDatabaseMissing('issue_tasks', ['issue_id' => $card->id]);
        Storage::disk('local')->assertMissing($attachment->path);
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

    public function test_a_guest_can_edit_their_own_comment_and_it_is_marked_edited(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $guest = $this->asGuest($project, 'Sarah', 'sarah-key')
            ->call('openCard', $issue->id)
            ->set('commentBody', 'Looks god')
            ->call('addComment');

        $comment = $issue->comments()->first();
        $this->assertNull($comment->edited_at);

        $guest->call('editComment', $comment->id)
            ->assertSet('editCommentBody', 'Looks god')
            ->set('editCommentBody', 'Looks good')
            ->call('updateComment')
            ->assertHasNoErrors();

        $comment->refresh();
        $this->assertSame('Looks good', $comment->body);
        $this->assertNotNull($comment->edited_at);
        $this->assertTrue($comment->wasEdited());
    }

    public function test_a_guest_cannot_edit_someone_elses_comment(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->asGuest($project, 'Sarah', 'sarah-key')
            ->call('openCard', $issue->id)
            ->set('commentBody', 'Sarahs words')
            ->call('addComment');

        $comment = $issue->comments()->first();

        $mallory = $this->asGuest($project, 'Mallory', 'mallory-key')->call('openCard', $issue->id);

        $mallory->call('editComment', $comment->id)->assertSet('editingCommentId', null);

        // Forcing the editor open server-side must not let the rewrite land.
        $mallory->set('editingCommentId', $comment->id)
            ->set('editCommentBody', 'Hijacked')
            ->call('updateComment');

        $this->assertSame('Sarahs words', $comment->fresh()->body);
        $this->assertNull($comment->fresh()->edited_at);
    }

    public function test_a_guest_cannot_edit_a_comment_left_by_the_project_owner(): void
    {
        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        // Chris replying from the internal board — a user comment, no guest_key.
        $owner = User::factory()->create();
        $comment = $issue->comments()->create(['user_id' => $owner->id, 'body' => 'On it this week']);

        $guest = $this->asGuest($project, 'Sarah', 'sarah-key')->call('openCard', $issue->id);

        $guest->call('editComment', $comment->id)->assertSet('editingCommentId', null);
        $guest->call('deleteOwnComment', $comment->id);

        $this->assertSame('On it this week', $comment->fresh()->body);
        $this->assertDatabaseHas('comments', ['id' => $comment->id]);
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

    /**
     * A browser-recorded voice note (webm audio) uploads and records its metadata.
     *
     * NB: this does NOT reproduce the prod 500 — Livewire's TemporaryUploadedFile
     * short-circuits getSize()/getMimeType() under runningUnitTests(), so the
     * move-then-read crash only surfaces over real HTTP. The guard against that
     * regression is reading metadata BEFORE store() in the component itself.
     */
    public function test_a_guest_can_upload_a_recorded_voice_note(): void
    {
        Storage::fake('local');
        config(['livewire.temporary_file_upload.disk' => 'local']);

        $project = $this->sharedProject();
        $issue = Issue::create(['project_id' => $project->id, 'title' => 'Card', 'board_column' => 'todo', 'is_client_visible' => true]);

        $this->asGuest($project)
            ->call('openCard', $issue->id)
            ->set('files', [UploadedFile::fake()->create('voice-memo.webm', 40, 'audio/webm')])
            ->assertHasNoErrors();

        $attachment = $issue->fresh()->attachments()->first();

        $this->assertNotNull($attachment);
        $this->assertSame(40 * 1024, $attachment->size);
        $this->assertSame('voice-memo.webm', $attachment->original_name);
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
