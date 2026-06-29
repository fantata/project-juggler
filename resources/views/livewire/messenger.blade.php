<div wire:poll.5s
     class="flex flex-col h-[calc(100vh-16rem)] min-h-[24rem] bg-white dark:bg-gray-800 rounded-2xl border border-cream-200 dark:border-gray-700 overflow-hidden">

    {{-- Message stream --}}
    <div x-data="{ toBottom() { this.$nextTick(() => { const b = this.$refs.scroll; if (b) b.scrollTop = b.scrollHeight; }); } }"
         x-init="toBottom()"
         @message-posted.window="toBottom()"
         class="flex-1 min-h-0 flex flex-col">
        <div x-ref="scroll" role="log" aria-live="polite" class="flex-1 min-h-0 overflow-y-auto p-4 space-y-3">
            @forelse($messages as $message)
                @php $isOwn = $message->sender_id === auth()->id(); @endphp
                <div wire:key="msg-{{ $message->id }}" class="flex {{ $isOwn ? 'justify-end' : 'justify-start' }}">
                    <div class="max-w-[80%] group">
                        {{-- Reply context: the message this one answers --}}
                        @if ($message->parent)
                            <div class="flex items-center gap-1 mb-0.5 px-2 text-sm text-gray-500 dark:text-gray-400 {{ $isOwn ? 'justify-end' : '' }}">
                                <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                <span class="truncate max-w-[12rem]">{{ $message->parent->sender->name }}: {{ str($message->parent->body)->limit(40) }}</span>
                            </div>
                        @endif

                        <div class="flex items-end gap-1.5 {{ $isOwn ? 'flex-row-reverse' : '' }}">
                            <div @class([
                                'rounded-2xl px-4 py-2.5',
                                'bg-terracotta-600 text-white rounded-br-md' => $isOwn,
                                'bg-cream-100 dark:bg-gray-700 text-bark-800 dark:text-cream-100 rounded-bl-md' => ! $isOwn,
                            ])>
                                <p class="text-base leading-relaxed whitespace-pre-wrap break-words">{{ $message->body }}</p>
                            </div>

                            {{-- React / reply — visible on hover (desktop) or focus (keyboard/tap) --}}
                            <div class="flex items-center gap-0.5 shrink-0 opacity-0 group-hover:opacity-100 focus-within:opacity-100 transition"
                                 x-data="{ pick: false }">
                                <div class="relative">
                                    <button type="button" @click="pick = !pick"
                                            class="p-1 rounded-full text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 hover:bg-cream-100 dark:hover:bg-gray-700"
                                            aria-label="Add reaction">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 01-6.364 0M21 12a9 9 0 11-18 0 9 9 0 0118 0zM9.75 9.75c0 .414-.168.75-.375.75S9 10.164 9 9.75 9.168 9 9.375 9s.375.336.375.75zm-.375 0h.008v.015h-.008V9.75zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75zm-.375 0h.008v.015h-.008V9.75z"/></svg>
                                    </button>
                                    <div x-show="pick" x-cloak @click.outside="pick = false" x-transition
                                         class="absolute z-20 bottom-full mb-1 flex gap-1 p-1.5 rounded-full bg-white dark:bg-gray-800 border border-cream-200 dark:border-gray-700 shadow-lg {{ $isOwn ? 'right-0' : 'left-0' }}">
                                        @foreach ($emojis as $emoji)
                                            <button type="button" @click="pick = false" wire:click="react({{ $message->id }}, '{{ $emoji }}')"
                                                    aria-label="React {{ $emoji }}"
                                                    class="text-xl leading-none motion-safe:hover:scale-125 transition-transform">{{ $emoji }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <button type="button" wire:click="startReply({{ $message->id }})"
                                        class="p-1 rounded-full text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 hover:bg-cream-100 dark:hover:bg-gray-700"
                                        aria-label="Reply">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15L3 9m0 0l6-6M3 9h12a6 6 0 010 12h-3"/></svg>
                                </button>
                            </div>
                        </div>

                        {{-- Reactions --}}
                        @if ($message->reactions->isNotEmpty())
                            <div class="flex flex-wrap gap-1 mt-1 px-1 {{ $isOwn ? 'justify-end' : '' }}">
                                @foreach ($message->reactions->groupBy('emoji') as $emoji => $reacts)
                                    @php $mine = $reacts->contains('user_id', auth()->id()); @endphp
                                    <button type="button" wire:click="react({{ $message->id }}, '{{ $emoji }}')"
                                            aria-pressed="{{ $mine ? 'true' : 'false' }}"
                                            aria-label="{{ $emoji }} reaction, {{ $reacts->count() }}{{ $mine ? ', you reacted' : '' }}"
                                            @class([
                                                'inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-sm border transition',
                                                'bg-terracotta-50 dark:bg-terracotta-900/30 border-terracotta-300 dark:border-terracotta-700 text-terracotta-700 dark:text-terracotta-300' => $mine,
                                                'bg-cream-100 dark:bg-gray-700 border-cream-200 dark:border-gray-600 text-bark-600 dark:text-gray-300' => ! $mine,
                                            ])>
                                        <span class="leading-none">{{ $emoji }}</span>
                                        <span class="text-xs leading-none">{{ $reacts->count() }}</span>
                                    </button>
                                @endforeach
                            </div>
                        @endif

                        <p class="mt-1 px-1 text-sm text-gray-500 dark:text-gray-400 {{ $isOwn ? 'text-right' : 'text-left' }}">
                            {{ $isOwn ? 'You' : $message->sender->name }} &middot; {{ $message->created_at->diffForHumans() }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="h-full flex items-center justify-center text-center px-6">
                    <p class="text-base text-gray-500 dark:text-gray-400">Nothing here yet — kick things off. 🎭</p>
                </div>
            @endforelse
        </div>
    </div>

    {{-- Reply banner --}}
    @if ($replyingTo)
        @php $replyTarget = $messages->firstWhere('id', $replyingTo); @endphp
        @if ($replyTarget)
            <div class="shrink-0 flex items-center justify-between gap-2 px-4 py-2 bg-cream-100 dark:bg-gray-700/60 border-t border-cream-200 dark:border-gray-700">
                <span class="truncate text-sm text-bark-600 dark:text-gray-300">
                    <span class="font-medium">Replying to {{ $replyTarget->sender_id === auth()->id() ? 'yourself' : $replyTarget->sender->name }}</span>
                    <span class="text-gray-500 dark:text-gray-400">— {{ str($replyTarget->body)->limit(50) }}</span>
                </span>
                <button type="button" wire:click="cancelReply" class="shrink-0 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200" aria-label="Cancel reply">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
        @endif
    @endif

    {{-- Composer --}}
    <form wire:submit="send" class="shrink-0 flex items-end gap-2 p-3 border-t border-cream-200 dark:border-gray-700">
        <div class="flex-1">
            <label for="message-body" class="sr-only">Your message</label>
            <textarea wire:model="body" id="message-body" rows="1"
                placeholder="{{ $replyingTo ? 'Write your reply…' : 'Write a message…' }}"
                x-data
                x-on:keydown.enter.prevent="$wire.send()"
                class="block w-full resize-none rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base"></textarea>
            @error('body') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
        </div>
        <button type="submit"
            class="shrink-0 inline-flex items-center justify-center w-12 h-12 rounded-xl bg-terracotta-600 text-white hover:bg-terracotta-700 focus:outline-none focus:ring-2 focus:ring-terracotta-400 focus:ring-offset-2 dark:focus:ring-offset-gray-800"
            aria-label="Send message">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 12L3.269 3.126A59.768 59.768 0 0121.485 12 59.77 59.77 0 013.27 20.876L5.999 12zm0 0h7.5" />
            </svg>
        </button>
    </form>
</div>
