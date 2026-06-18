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

    public function send(): void
    {
        $this->validate();

        Message::create([
            'sender_id' => Auth::id(),
            'body' => trim($this->body),
        ]);

        $this->reset('body');
        $this->dispatch('message-posted');
    }

    public function render()
    {
        return view('livewire.messenger', [
            'messages' => Message::with('sender')->orderBy('created_at')->get(),
        ]);
    }
}
