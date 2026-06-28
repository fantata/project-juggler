<div>
    <x-slot name="header">
        <div class="min-w-0">
            <a href="{{ route('projects.detail', $project) }}" wire:navigate
               class="text-sm text-gray-500 dark:text-gray-400 hover:text-bark-600 dark:hover:text-cream-200">
                &larr; {{ $project->name }}
            </a>
            <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">Board</h2>
        </div>
    </x-slot>

    {{-- Mobile-first kanban: one column ~fills the phone and scroll-snaps;
         columns sit side-by-side from sm up. Negative margins let the strip
         bleed to the screen edges on mobile so a column peeks in from the side. --}}
    <div class="flex gap-3 sm:gap-4 overflow-x-auto pb-4 snap-x snap-mandatory
                -mx-4 px-4 sm:mx-0 sm:px-0">
        @foreach ($columns as $key => $label)
            @php($columnCards = $cards[$key] ?? collect())
            @php($keys = array_keys($columns))
            @php($index = array_search($key, $keys))

            <section class="snap-start shrink-0 w-[82vw] sm:w-72 lg:w-80 flex flex-col">
                <div class="flex items-center justify-between px-1 mb-2">
                    <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200">{{ $label }}</h3>
                    <span class="text-sm text-gray-400 dark:text-gray-500">{{ $columnCards->count() }}</span>
                </div>

                <div class="flex-1 space-y-2 rounded-xl bg-cream-100 dark:bg-gray-900/40 p-2 min-h-[6rem]">
                    @forelse ($columnCards as $card)
                        <article wire:key="card-{{ $card->id }}"
                                 class="bg-white dark:bg-gray-800 rounded-lg shadow-sm border border-cream-200 dark:border-gray-700 p-3">
                            <p class="text-sm font-medium text-bark-800 dark:text-cream-200">{{ $card->title }}</p>

                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                @if ($card->kind && $card->kind !== 'task')
                                    <span class="text-xs px-2 py-0.5 rounded-full capitalize
                                                 bg-terracotta-50 dark:bg-terracotta-900/30 text-terracotta-700 dark:text-terracotta-300">
                                        {{ $card->kind }}
                                    </span>
                                @endif

                                @if ($card->tasks_count > 0)
                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $card->completed_tasks_count }}/{{ $card->tasks_count }}
                                    </span>
                                @endif

                                @if ($card->urgency->value === 'high')
                                    <span class="text-xs font-medium text-red-500 dark:text-red-400">High</span>
                                @endif

                                <span class="flex-1"></span>

                                @if ($card->assignee)
                                    <span title="{{ $card->assignee->name }}"
                                          class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-moss-500 text-white text-xs font-semibold">
                                        {{ $card->assignee->initials }}
                                    </span>
                                @endif
                            </div>

                            {{-- Mobile-friendly move controls (tap to shift columns).
                                 Drag-and-drop arrives next for desktop. --}}
                            <div class="flex items-center justify-between mt-2 pt-2 border-t border-cream-100 dark:border-gray-700/60">
                                <button type="button"
                                        wire:click="moveCard({{ $card->id }}, '{{ $keys[max(0, $index - 1)] }}')"
                                        @disabled($index === 0)
                                        class="text-xs px-2 py-1 text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 disabled:opacity-30 disabled:hover:text-gray-400">
                                    &larr; Move
                                </button>
                                <button type="button"
                                        wire:click="moveCard({{ $card->id }}, '{{ $keys[min(count($keys) - 1, $index + 1)] }}')"
                                        @disabled($index === count($keys) - 1)
                                        class="text-xs px-2 py-1 text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 disabled:opacity-30 disabled:hover:text-gray-400">
                                    Move &rarr;
                                </button>
                            </div>
                        </article>
                    @empty
                        <p class="text-xs text-center text-gray-400 dark:text-gray-500 py-4">Nothing here yet</p>
                    @endforelse
                </div>
            </section>
        @endforeach
    </div>
</div>
