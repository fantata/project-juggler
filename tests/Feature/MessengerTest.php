<?php

namespace Tests\Feature;

use App\Livewire\Messenger;
use App\Models\Message;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class MessengerTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_message_can_reply_to_another(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $parent = Message::create(['sender_id' => $user->id, 'body' => 'First']);

        Livewire::test(Messenger::class)
            ->call('startReply', $parent->id)
            ->assertSet('replyingTo', $parent->id)
            ->set('body', 'A reply')
            ->call('send')
            ->assertHasNoErrors()
            ->assertSet('replyingTo', null);

        $reply = Message::where('body', 'A reply')->first();
        $this->assertSame($parent->id, $reply->parent_id);
    }

    public function test_a_user_can_react_then_toggle_it_off(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $message = Message::create(['sender_id' => $user->id, 'body' => 'React to me']);

        $component = Livewire::test(Messenger::class);

        $component->call('react', $message->id, '🎭');
        $this->assertSame(1, $message->reactions()->count());

        $component->call('react', $message->id, '🎭');
        $this->assertSame(0, $message->reactions()->count());
    }

    public function test_an_unknown_emoji_is_ignored(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $message = Message::create(['sender_id' => $user->id, 'body' => 'x']);

        Livewire::test(Messenger::class)->call('react', $message->id, '💣');

        $this->assertSame(0, $message->reactions()->count());
    }

    public function test_a_project_room_only_shows_its_own_messages(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $project = Project::create(['name' => 'Panto', 'type' => 'personal', 'status' => 'active']);

        Message::create(['sender_id' => $user->id, 'project_id' => null, 'body' => 'Shared room only']);

        // Posting in the project room tags the message with the project...
        Livewire::test(Messenger::class, ['projectId' => $project->id])
            ->set('body', 'Panto room only')
            ->call('send')
            ->assertSee('Panto room only')
            ->assertDontSee('Shared room only');

        $this->assertSame($project->id, Message::where('body', 'Panto room only')->value('project_id'));

        // ...and the shared room doesn't show it.
        Livewire::test(Messenger::class)
            ->assertSee('Shared room only')
            ->assertDontSee('Panto room only');
    }

    public function test_the_room_cannot_be_retagged_by_the_client(): void
    {
        $this->actingAs(User::factory()->create());
        $a = Project::create(['name' => 'A', 'type' => 'personal', 'status' => 'active']);
        $b = Project::create(['name' => 'B', 'type' => 'personal', 'status' => 'active']);

        // projectId is #[Locked] — a tampered client update is refused.
        $this->expectException(\Livewire\Features\SupportLockedProperties\CannotUpdateLockedPropertyException::class);

        Livewire::test(Messenger::class, ['projectId' => $a->id])->set('projectId', $b->id);
    }

    public function test_different_emojis_stack(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);
        $message = Message::create(['sender_id' => $user->id, 'body' => 'x']);

        $component = Livewire::test(Messenger::class);
        $component->call('react', $message->id, '👍');
        $component->call('react', $message->id, '❤️');

        $this->assertSame(2, $message->reactions()->count());
    }
}
