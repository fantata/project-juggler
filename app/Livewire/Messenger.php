<?php

namespace App\Livewire;

use App\Models\Message;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Validate;
use Livewire\Component;

class Messenger extends Component
{
    #[Validate('required|string|max:2000')]
    public string $body = '';

    /** The message currently being replied to, if any. */
    public ?int $replyingTo = null;

    /** The reaction palette — a little Dogface theatre tucked in there. */
    public const EMOJIS = ['👍', '❤️', '😂', '🎭', '🙌', '🔥'];

    public function startReply(int $messageId): void
    {
        $this->replyingTo = Message::whereKey($messageId)->value('id');
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
            'parent_id' => $this->replyingTo,
            'body' => trim($this->body),
        ]);

        $this->reset('body', 'replyingTo');
        $this->dispatch('message-posted');
    }

    /**
     * Toggle the current user's reaction. One of each emoji per person, so a
     * second tap removes it.
     */
    public function react(int $messageId, string $emoji): void
    {
        if (! in_array($emoji, self::EMOJIS, true)) {
            return;
        }

        $message = Message::findOrFail($messageId);

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

    public function render()
    {
        return view('livewire.messenger', [
            'messages' => Message::with(['sender', 'parent.sender', 'reactions'])
                ->orderBy('created_at')
                ->get(),
            'emojis' => self::EMOJIS,
        ]);
    }
}
