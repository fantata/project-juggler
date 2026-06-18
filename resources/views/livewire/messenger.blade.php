<div wire:poll.5s
     class="flex flex-col h-[calc(100vh-16rem)] min-h-[24rem] bg-white dark:bg-gray-800 rounded-2xl border border-cream-200 dark:border-gray-700 overflow-hidden">

    {{-- Message stream --}}
    <div x-data="{ toBottom() { this.$nextTick(() => { const b = this.$refs.scroll; if (b) b.scrollTop = b.scrollHeight; }); } }"
         x-init="toBottom()"
         @message-posted.window="toBottom()"
         class="flex-1 min-h-0 flex flex-col">
        <div x-ref="scroll" class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3">
            @forelse($messages as $message)
                @php $isOwn = $message->sender_id === auth()->id(); @endphp
                <div wire:key="msg-{{ $message->id }}" class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%]">
                        <div @class([
                            'rounded-2xl px-4 py-2.5',
                            'bg-terracotta-500 text-white rounded-br-md' => $isOwn,
                            'bg-cream-100 dark:bg-gray-700 text-bark-800 dark:text-cream-100 rounded-bl-md' => ! $isOwn,
                        ])>
                            <p class="text-base leading-relaxed whitespace-pre-wrap break-words">{{ $message->body }}</p>
                        </div>
                        <p class="mt-1 px-1 text-sm text-gray-400 dark:text-gray-500 {{ $isOwn ? 'text-right' : 'text-left' }}">
                            {{ $isOwn ? 'You' : $message->sender->name }} &middot; {{ $message->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="h-full flex items-center justify-center">
                    <p class="text-base text-gray-500 dark:text-gray-400">No messages yet. Say hello.</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Composer --}}
    <form wire:submit="send" class="shrink-0 flex items-end gap-2 p-3 border-t border-cream-200 dark:border-gray-700">
        <div class="flex-1">
            <label for="message-body" class="sr-only">Your message</label>
            <textarea wire:model="body" id="message-body" rows="1"
                placeholder="Write a message..."
                x-data
                x-on:keydown.enter.prevent="$wire.send()"
                class="block w-full resize-none rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base"></textarea>
            @error('body') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>
        <button type="submit"
            class="shrink-0 inline-flex items-center justify-center w-12 h-12 rounded-xl bg-terracotta-500 text-white hover:bg-terracotta-600 focus:outline-none focus:ring-2 focus:ring-terracotta-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            aria-label="Send message">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
            </svg>
        </button>
    </form>
</div>
