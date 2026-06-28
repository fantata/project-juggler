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
                            <button type="button" wire:click="openCard({{ $card->id }})"
                                    class="block w-full text-left text-sm font-medium text-bark-800 dark:text-cream-200 hover:text-terracotta-600 dark:hover:text-terracotta-400">
                                {{ $card->title }}
                            </button>

                            <div class="flex items-center gap-2 mt-2 flex-wrap">
                                @if ($card->attachments_count > 0)
                                    <span class="inline-flex items-center gap-1 text-xs text-gray-400 dark:text-gray-500" title="{{ $card->attachments_count }} file(s)">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M18.375 12.739l-7.693 7.693a4.5 4.5 0 01-6.364-6.364l10.94-10.94A3 3 0 1119.5 7.372L8.552 18.32m.009-.01l-.01.01m5.699-9.941l-7.81 7.81a1.5 1.5 0 002.112 2.13"/></svg>
                                        {{ $card->attachments_count }}
                                    </span>
                                @endif
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

                                {{-- Assignee picker: tap the chip to assign a person --}}
                                <div x-data="{ open: false }" class="relative">
                                    <button type="button" @click="open = !open"
                                            title="{{ $card->assignee?->name ?? 'Assign' }}"
                                            class="block rounded-full focus:outline-none focus:ring-2 focus:ring-terracotta-400">
                                        @if ($card->assignee)
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-moss-500 text-white text-xs font-semibold">
                                                {{ $card->assignee->initials }}
                                            </span>
                                        @else
                                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full border border-dashed border-gray-300 dark:border-gray-600 text-gray-400 dark:text-gray-500 text-xs">+</span>
                                        @endif
                                    </button>

                                    <div x-show="open" x-cloak @click.outside="open = false"
                                         x-transition.origin.top.right
                                         class="absolute right-0 mt-1 w-44 py-1 rounded-lg shadow-lg z-20
                                                bg-white dark:bg-gray-800 border border-cream-200 dark:border-gray-700">
                                        @foreach ($users as $u)
                                            <button type="button" @click="open = false"
                                                    wire:click="assignCard({{ $card->id }}, {{ $u->id }})"
                                                    class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left text-bark-700 dark:text-cream-200 hover:bg-cream-100 dark:hover:bg-gray-700">
                                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-moss-500 text-white text-xs font-semibold shrink-0">{{ $u->initials }}</span>
                                                <span class="flex-1 truncate">{{ $u->name }}</span>
                                                @if ($card->assignee_id === $u->id)
                                                    <svg class="w-4 h-4 text-moss-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/></svg>
                                                @endif
                                            </button>
                                        @endforeach
                                        @if ($card->assignee)
                                            <button type="button" @click="open = false"
                                                    wire:click="assignCard({{ $card->id }}, null)"
                                                    class="w-full px-3 py-2 text-sm text-left text-gray-500 dark:text-gray-400 hover:bg-cream-100 dark:hover:bg-gray-700 border-t border-cream-100 dark:border-gray-700/60">
                                                Unassign
                                            </button>
                                        @endif
                                    </div>
                                </div>
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

    {{-- Card detail: a bottom sheet on mobile, a centred dialog on desktop --}}
    @if ($openCard)
        <div class="fixed inset-0 z-40 flex items-end sm:items-center justify-center sm:p-4"
             x-data x-on:keydown.escape.window="$wire.closeCard()">
            <div class="absolute inset-0 bg-gray-900/50" wire:click="closeCard"></div>

            <div class="relative w-full sm:max-w-lg max-h-[92vh] overflow-y-auto
                        bg-white dark:bg-gray-800 rounded-t-2xl sm:rounded-2xl shadow-xl">
                <div class="sticky top-0 z-10 bg-white dark:bg-gray-800 px-5 py-4 border-b border-cream-200 dark:border-gray-700 flex items-start justify-between gap-3">
                    <h3 class="text-lg font-semibold text-bark-800 dark:text-cream-200">{{ $openCard->title }}</h3>
                    <button type="button" wire:click="closeCard" class="-m-1 p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="px-5 py-4 space-y-5">
                    <div class="flex items-center gap-2 flex-wrap text-sm">
                        @if ($openCard->kind && $openCard->kind !== 'task')
                            <span class="text-xs px-2 py-0.5 rounded-full capitalize bg-terracotta-50 dark:bg-terracotta-900/30 text-terracotta-700 dark:text-terracotta-300">{{ $openCard->kind }}</span>
                        @endif
                        @if ($openCard->assignee)
                            <span class="inline-flex items-center gap-1.5 text-gray-600 dark:text-gray-300">
                                <span class="inline-flex items-center justify-center w-5 h-5 rounded-full bg-moss-500 text-white text-xs font-semibold">{{ $openCard->assignee->initials }}</span>
                                {{ $openCard->assignee->name }}
                            </span>
                        @else
                            <span class="text-gray-400 dark:text-gray-500">Unassigned</span>
                        @endif
                    </div>

                    @if ($openCard->description)
                        <p class="text-sm text-bark-700 dark:text-gray-300 whitespace-pre-line">{{ $openCard->description }}</p>
                    @endif

                    <div>
                        <h4 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-2">Files</h4>

                        @if ($openCard->attachments->isNotEmpty())
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-3">
                                @foreach ($openCard->attachments as $att)
                                    <div wire:key="att-{{ $att->id }}" class="group relative rounded-lg overflow-hidden border border-cream-200 dark:border-gray-700 bg-cream-50 dark:bg-gray-900/40">
                                        @if ($att->isImage())
                                            <a href="{{ $att->url() }}" target="_blank" rel="noopener" class="block aspect-square">
                                                <img src="{{ $att->url() }}" alt="{{ $att->original_name }}" class="w-full h-full object-cover">
                                            </a>
                                        @else
                                            <a href="{{ $att->url() }}" target="_blank" rel="noopener" class="flex flex-col items-center justify-center aspect-square p-2 text-center">
                                                <svg class="w-7 h-7 text-bark-400 dark:text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 00-3.375-3.375h-1.5A1.125 1.125 0 0113.5 7.125v-1.5a3.375 3.375 0 00-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 00-9-9z"/></svg>
                                                <span class="text-xs text-bark-700 dark:text-gray-300 truncate w-full mt-1">{{ $att->original_name }}</span>
                                            </a>
                                        @endif
                                        <button type="button" wire:click="deleteAttachment({{ $att->id }})" wire:confirm="Remove this file?"
                                                class="absolute top-1 right-1 rounded-full p-1 bg-white/90 dark:bg-gray-800/90 text-red-500 opacity-0 group-hover:opacity-100 focus:opacity-100 transition">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                        <div class="px-1.5 py-1 text-xs text-gray-500 dark:text-gray-400 truncate">{{ $att->humanSize() }}</div>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        {{-- Native file input fills the zone, so drag-drop AND tap both work, no JS dep --}}
                        <div class="relative border-2 border-dashed border-cream-300 dark:border-gray-600 rounded-xl p-6 text-center">
                            <input type="file" multiple wire:model="files" aria-label="Add files"
                                   class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                            <div class="pointer-events-none">
                                <p class="text-sm text-bark-600 dark:text-gray-300">Drop files here, or tap to add</p>
                                <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Posters, audio, PDFs — up to 25 MB each</p>
                            </div>
                            <div wire:loading wire:target="files,updatedFiles" class="absolute inset-0 flex items-center justify-center rounded-xl bg-white/80 dark:bg-gray-800/80 text-sm text-bark-600 dark:text-cream-200">
                                Uploading…
                            </div>
                        </div>
                        @error('files') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                        @error('files.*') <p class="text-sm text-red-500 mt-1">{{ $message }}</p> @enderror
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
