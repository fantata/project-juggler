<?php

namespace App\Livewire;

use App\Models\Message;
use App\Models\Project;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Messenger extends Component
{
    #[Validate('required|string|max:2000')]
    public string $body = '';

    /** The message currently being replied to, if any. */
    public ?int $replyingTo = null;

    /**
     * Which room: null = the shared Together room, otherwise a project thread.
     * Locked so the client can't retag a live component into another room — it's
     * server-set in mount and trusted thereafter.
     */
    #[Locked]
    public ?int $projectId = null;

    /** The reaction palette — a little Dogface theatre tucked in there. */
    public const EMOJIS = ['👍', '❤️', '😂', '🎭', '🙌', '🔥'];

    public function mount(?int $projectId = null): void
    {
        // A project room must point at a real project.
        if ($projectId !== null) {
            abort_unless(Project::whereKey($projectId)->exists(), 404);
        }

        $this->projectId = $projectId;
    }

    public function startReply(int $messageId): void
    {
        $this->replyingTo = $this->roomMessages()->whereKey($messageId)->value('id');
    }

    public function cancelReply(): void
    {
        $this->replyingTo = null;
    }

    public function send(): void
    {
        $this->validate();

        Message::create([
            'sender_id' => Auth::id(),
            'project_id' => $this->projectId,
            'parent_id' => $this->replyingTo,
            'body' => trim($this->body),
        ]);

        $this->reset('body', 'replyingTo');
        $this->dispatch('message-posted');
    }

    /**
     * Toggle the current user's reaction. One of each emoji per person, so a
     * second tap removes it. Scoped to this room's messages.
     */
    public function react(int $messageId, string $emoji): void
    {
        if (! in_array($emoji, self::EMOJIS, true)) {
            return;
        }

        $message = $this->roomMessages()->whereKey($messageId)->first();

        if ($message === null) {
            return;
        }

        $existing = $message->reactions()
            ->where('user_id', Auth::id())
            ->where('emoji', $emoji)
            ->first();

        if ($existing) {
            $existing->delete();

            return;
        }

        $message->reactions()->create([
            'user_id' => Auth::id(),
            'emoji' => $emoji,
        ]);
    }

    /** Base query for the current room (the shared room or a project thread). */
    private function roomMessages()
    {
        return Message::query()->when(
            $this->projectId === null,
            fn ($query) => $query->whereNull('project_id'),
            fn ($query) => $query->where('project_id', $this->projectId),
        );
    }

    public function render()
    {
        return view('livewire.messenger', [
            'messages' => $this->roomMessages()
                ->with(['sender', 'parent.sender', 'reactions'])
                ->orderBy('created_at')
                ->get(),
            'emojis' => self::EMOJIS,
        ]);
    }
}
