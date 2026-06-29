<x-slot name="header">
    <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">Together</h2>
</x-slot>

<div class="max-w-2xl mx-auto" x-data="{ tab: 'priorities' }">

    {{-- Tab switcher --}}
    <div class="sticky top-0 z-10 -mx-4 px-4 pt-1 pb-3 bg-cream-100/90 dark:bg-gray-900/90 backdrop-blur-sm sm:mx-0 sm:px-0">
        <div class="flex gap-2 p-1 bg-white dark:bg-gray-800 rounded-2xl border border-cream-200 dark:border-gray-700">
            <button type="button" @click="tab = 'priorities'"
                :class="tab === 'priorities' ? 'bg-terracotta-500 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300'"
                class="flex-1 rounded-xl py-3 text-base font-semibold transition-colors">
                Priorities
            </button>
            <button type="button" @click="tab = 'messages'"
                :class="tab === 'messages' ? 'bg-terracotta-500 text-white shadow-sm' : 'text-gray-600 dark:text-gray-300'"
                class="flex-1 rounded-xl py-3 text-base font-semibold transition-colors">
                Messages
            </button>
        </div>
    </div>

    {{-- PRIORITIES --}}
    <div x-show="tab === 'priorities'" x-cloak class="space-y-6 pb-8">

        <p class="text-base text-gray-600 dark:text-gray-300 leading-relaxed">
            Our shared space. Add anything that needs the other person, or ask a question — the quiet project work stays tucked away below.
        </p>

        {{-- Add an item --}}
        <div class="bg-white dark:bg-gray-800 rounded-2xl border border-cream-200 dark:border-gray-700 overflow-hidden">
            @if (! $showAdd)
                <button type="button" wire:click="$set('showAdd', true)"
                    class="w-full flex items-center gap-3 px-5 py-4 text-left text-base font-medium text-terracotta-700 dark:text-terracotta-400 hover:bg-cream-50 dark:hover:bg-gray-700/50 transition-colors">
                    <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                    </svg>
                    Add something
                </button>
            @else
                <form wire:submit="addItem" class="p-5 space-y-4">
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 dark:text-gray-300">What is it?</label>
                        <input type="text" wire:model="title" id="title" autofocus
                            placeholder="Pick up milk, ring the plumber..."
                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base">
                        @error('title') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="note" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Anything to add? (optional)</label>
                        <textarea wire:model="note" id="note" rows="2"
                            placeholder="A bit more detail..."
                            class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base"></textarea>
                        @error('note') <span class="text-red-600 dark:text-red-400 text-sm">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="due_bucket" class="block text-sm font-medium text-gray-700 dark:text-gray-300">When?</label>
                            <select wire:model="due_bucket" id="due_bucket"
                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base">
                                @foreach ($dueBuckets as $bucket)
                                    <option value="{{ $bucket->value }}">{{ $bucket->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="project_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Project</label>
                            <select wire:model="project_id" id="project_id"
                                class="mt-1 block w-full rounded-xl border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 text-base">
                                @foreach ($projects as $project)
                                    <option value="{{ $project->id }}">{{ $project->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <label class="flex items-center gap-3 cursor-pointer py-1">
                        <input type="checkbox" wire:model="is_question"
                            class="w-5 h-5 rounded border-gray-300 dark:border-gray-700 text-terracotta-600 shadow-sm focus:ring-terracotta-400 dark:bg-gray-900">
                        <span class="text-base text-gray-700 dark:text-gray-300">This is a question</span>
                    </label>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" wire:click="$set('showAdd', false)"
                            class="px-4 py-2.5 text-base font-medium text-gray-700 dark:text-gray-300 bg-cream-100 dark:bg-gray-700 rounded-xl hover:bg-cream-200 dark:hover:bg-gray-600">
                            Cancel
                        </button>
                        <button type="submit"
                            class="px-5 py-2.5 text-base font-semibold text-white bg-terracotta-600 rounded-xl hover:bg-terracotta-700">
                            Add it
                        </button>
                    </div>
                </form>
            @endif
        </div>

        {{-- Questions — the things that need a person --}}
        @if ($questions->isNotEmpty())
            <section>
                <h3 class="px-1 mb-2 text-sm font-semibold uppercase tracking-wide text-moss-700 dark:text-moss-300">Needs an answer</h3>
                <ul class="space-y-2">
                    @foreach ($questions as $issue)
                        <li wire:key="q-{{ $issue->id }}"
                            class="rounded-2xl border px-4 py-3.5 bg-moss-50 dark:bg-moss-900/20 border-moss-300 dark:border-moss-700">
                            <p class="text-base font-medium text-bark-800 dark:text-cream-100">{{ $issue->title }}</p>
                            @if ($issue->description)
                                <p class="mt-1 text-base text-gray-600 dark:text-gray-300 leading-relaxed">{{ $issue->description }}</p>
                            @endif
                            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $issue->project->name }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        {{-- On the radar — shared + time-bucketed --}}
        @foreach ($radarGroups as $group)
            <section wire:key="radar-{{ $group['key'] }}">
                <h3 class="px-1 mb-2 text-sm font-semibold uppercase tracking-wide text-bark-500 dark:text-cream-300/70">{{ $group['label'] }}</h3>
                <ul class="space-y-2">
                    @foreach ($group['items'] as $issue)
                        <li wire:key="r-{{ $issue->id }}"
                            class="rounded-2xl border px-4 py-3.5 bg-white dark:bg-gray-800 border-cream-200 dark:border-gray-700">
                            <p class="text-base font-medium text-bark-800 dark:text-cream-100">{{ $issue->title }}</p>
                            @if ($issue->description)
                                <p class="mt-1 text-base text-gray-600 dark:text-gray-300 leading-relaxed">{{ $issue->description }}</p>
                            @endif
                            <p class="mt-1.5 text-sm text-gray-500 dark:text-gray-400">{{ $issue->project->name }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endforeach

        {{-- Calm state when nothing needs attention --}}
        @if ($questions->isEmpty() && $radarGroups->isEmpty())
            <div class="text-center py-10">
                <p class="text-base text-gray-500 dark:text-gray-400">Nothing needs you right now. All calm. &#9749;</p>
            </div>
        @endif

        {{-- Everything else — quiet project work, collapsed by default --}}
        @if ($otherCount > 0)
            <div x-data="{ open: false }" class="pt-2">
                <button type="button" @click="open = !open" :aria-expanded="open"
                    class="w-full flex items-center justify-between gap-2 px-4 py-3 rounded-2xl bg-cream-50 dark:bg-gray-800/60 border border-cream-200 dark:border-gray-700 text-left">
                    <span class="text-sm font-medium text-gray-600 dark:text-gray-300">From the projects &middot; {{ $otherCount }} item{{ $otherCount === 1 ? '' : 's' }}, no action needed</span>
                    <svg class="w-5 h-5 text-gray-400 shrink-0 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 8.25l-7.5 7.5-7.5-7.5"/></svg>
                </button>

                <div x-show="open" x-cloak class="mt-3 space-y-4">
                    @foreach ($otherByProject as $projectName => $items)
                        <div wire:key="other-{{ \Illuminate\Support\Str::slug($projectName) }}">
                            <h4 class="px-1 mb-1.5 text-xs font-semibold uppercase tracking-wide text-gray-400 dark:text-gray-500">{{ $projectName }}</h4>
                            <ul class="rounded-xl border border-cream-200 dark:border-gray-700 divide-y divide-cream-100 dark:divide-gray-700/70 overflow-hidden">
                                @foreach ($items as $issue)
                                    <li wire:key="o-{{ $issue->id }}" class="px-4 py-2.5 bg-white dark:bg-gray-800">
                                        <span class="text-sm text-bark-700 dark:text-cream-200">{{ $issue->title }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>

    {{-- MESSAGES --}}
    <div x-show="tab === 'messages'" x-cloak class="pb-4">
        <livewire:messenger />
    </div>
</div>
