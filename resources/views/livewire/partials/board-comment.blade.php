{{-- One comment row. Expects: $comment, $depth (0 = top-level, 1 = reply). --}}
<div class="flex items-start justify-between gap-2 group">
    <div class="min-w-0">
        <p class="text-sm text-bark-800 dark:text-cream-100 whitespace-pre-wrap break-words">{{ $comment->body }}</p>
        <div class="flex items-center gap-2 mt-0.5 text-xs text-gray-500 dark:text-gray-400">
            <span class="font-medium text-gray-500 dark:text-gray-400">{{ $comment->user->name }}</span>
            <span>&middot;</span>
            <span>{{ $comment->created_at->diffForHumans() }}</span>
            @if ($depth === 0)
                <button type="button" wire:click="startCommentReply({{ $comment->id }})" class="hover:text-bark-600 dark:hover:text-cream-200">Reply</button>
            @endif
            <div x-data="{ pick: false }" class="relative">
                <button type="button" @click="pick = !pick" class="hover:text-bark-600 dark:hover:text-cream-200" aria-label="React to comment">React</button>
                <div x-show="pick" x-cloak @click.outside="pick = false" x-transition
                     class="absolute z-20 top-full mt-1 left-0 flex gap-1 p-1.5 rounded-full bg-white dark:bg-gray-800 border border-cream-200 dark:border-gray-700 shadow-lg">
                    @foreach ($emojis as $emoji)
                        <button type="button" @click="pick = false" wire:click="reactToComment({{ $comment->id }}, '{{ $emoji }}')"
                                aria-label="React {{ $emoji }}"
                                class="text-lg leading-none motion-safe:hover:scale-125 transition-transform">{{ $emoji }}</button>
                    @endforeach
                </div>
            </div>
        </div>

        @if ($comment->reactions->isNotEmpty())
            <div class="flex flex-wrap gap-1 mt-1">
                @foreach ($comment->reactions->groupBy('emoji') as $emoji => $reacts)
                    @php $mine = $reacts->contains('user_id', auth()->id()); @endphp
                    <button type="button" wire:click="reactToComment({{ $comment->id }}, '{{ $emoji }}')"
                            aria-pressed="{{ $mine ? 'true' : 'false' }}"
                            aria-label="{{ $emoji }} reaction, {{ $reacts->count() }}{{ $mine ? ', you reacted' : '' }}"
                            @class([
                                'inline-flex items-center gap-1 px-1.5 py-0.5 rounded-full text-xs border transition',
                                'bg-terracotta-50 dark:bg-terracotta-900/30 border-terracotta-300 dark:border-terracotta-700 text-terracotta-700 dark:text-terracotta-300' => $mine,
                                'bg-cream-100 dark:bg-gray-700 border-cream-200 dark:border-gray-600 text-bark-600 dark:text-gray-300' => ! $mine,
                            ])>
                        <span class="leading-none">{{ $emoji }}</span><span class="leading-none">{{ $reacts->count() }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </div>
    @if ($comment->user_id === auth()->id())
        <button type="button" wire:click="deleteComment({{ $comment->id }})" wire:confirm="Delete this comment?"
                class="shrink-0 opacity-0 group-hover:opacity-100 focus:opacity-100 text-gray-500 dark:text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" aria-label="Delete comment">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
    @endif
</div>
