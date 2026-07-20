<x-slot name="header">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-400 dark:text-gray-500 hover:text-bark-600 dark:hover:text-cream-200 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5L3 12m0 0l7.5-7.5M3 12h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">
                {{ $project->name }}
            </h2>
        </div>
        <div class="flex items-center gap-4">
            <a href="{{ route('projects.board', $project) }}" wire:navigate
               class="inline-flex items-center gap-1.5 text-sm font-medium text-bark-600 dark:text-cream-200 hover:text-terracotta-600 dark:hover:text-terracotta-400 transition-colors">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v12a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18V6zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v6a2.25 2.25 0 01-2.25 2.25h-2.25A2.25 2.25 0 0113.5 12V6z"/>
                </svg>
                Board
            </a>
            <button
                wire:click="delete"
                wire:confirm="Are you sure you want to delete this project?"
                class="text-sm text-red-400 hover:text-red-600 font-medium"
            >
                Delete project
            </button>
        </div>
    </div>
</x-slot>

<div class="max-w-7xl mx-auto">
    @if(session('message'))
        <div class="mb-4 p-4 bg-moss-50 dark:bg-moss-900/20 border border-moss-200 dark:border-moss-700 text-moss-700 dark:text-moss-300 rounded-xl text-sm">
            {{ session('message') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Edit Form -->
        <div class="lg:col-span-2 space-y-6">
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
                <form wire:submit="save" class="p-6 space-y-4">
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" wire:model="name" id="name" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                        @error('name') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                            <select wire:model="type" id="type" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                                @foreach($types as $t)
                                    <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Status</label>
                            <select wire:model="status" id="status" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                                @foreach($statuses as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="money_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Money Status</label>
                            <select wire:model="money_status" id="money_status" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                                @foreach($moneyStatuses as $ms)
                                    <option value="{{ $ms->value }}">{{ $ms->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label for="money_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Value (&pound;)</label>
                            <input type="number" step="0.01" wire:model="money_value" id="money_value" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="0.00">
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Deadline</label>
                            <input type="date" wire:model="deadline" id="deadline" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                        </div>
                        <div>
                            <label for="next_action" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Next Action</label>
                            <input type="text" wire:model="next_action" id="next_action" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="What's the next step?">
                        </div>
                    </div>

                    <div>
                        <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Notes</label>
                        <textarea wire:model="notes" id="notes" rows="3" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="Project notes..."></textarea>
                    </div>

                    <div>
                        <label for="github_repo" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">GitHub Repo</label>
                        <div class="flex gap-2 items-center">
                            <input type="text" wire:model="github_repo" id="github_repo" placeholder="org/repo" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                            @if($project->github_repo)
                                <a href="https://github.com/{{ $project->github_repo }}" target="_blank" class="text-terracotta-500 hover:text-terracotta-700 text-sm whitespace-nowrap font-medium">View</a>
                            @endif
                        </div>
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" wire:model.live="is_retainer" class="rounded border-gray-300 dark:border-gray-600 text-moss-500 focus:ring-moss-400 dark:bg-gray-700">
                        <span class="text-sm text-gray-700 dark:text-gray-300">Retainer client</span>
                    </label>

                    @if($is_retainer)
                        <div class="grid grid-cols-2 gap-4 p-4 bg-moss-50 dark:bg-moss-900/10 rounded-lg border border-moss-200 dark:border-moss-800">
                            <div>
                                <label for="retainer_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Frequency</label>
                                <select wire:model="retainer_frequency" id="retainer_frequency" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-moss-400 focus:ring-moss-400 sm:text-sm">
                                    <option value="">Select...</option>
                                    @foreach($retainerFrequencies as $freq)
                                        <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label for="retainer_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Amount (&pound;)</label>
                                <input type="number" step="0.01" wire:model="retainer_amount" id="retainer_amount" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-moss-400 focus:ring-moss-400 sm:text-sm" placeholder="0.00">
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end pt-4 border-t border-cream-200 dark:border-gray-700">
                        <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">
                            Save Changes
                        </button>
                    </div>
                </form>
            </div>

        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Client share board -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
                <div class="p-5" x-data="{ copied: false }">
                    <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200 mb-1">Client board</h3>
                    <p class="text-sm text-gray-500 dark:text-gray-400 mb-4">A shareable, no-login board where a client can add cards, comment, react and drop files.</p>

                    @if(session('share-message'))
                        <div class="mb-4 p-3 bg-moss-50 dark:bg-moss-900/20 border border-moss-200 dark:border-moss-700 text-moss-700 dark:text-moss-300 rounded-lg text-sm">
                            {{ session('share-message') }}
                        </div>
                    @endif

                    @if($project->shareUrl())
                        <label for="share-url" class="sr-only">Share link</label>
                        <div class="flex gap-2">
                            <input type="text" id="share-url" readonly value="{{ $project->shareUrl() }}"
                                   x-ref="url" @focus="$el.select()"
                                   class="flex-1 min-w-0 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm sm:text-sm">
                            <button type="button"
                                    @click="navigator.clipboard.writeText($refs.url.value); copied = true; setTimeout(() => copied = false, 1800)"
                                    class="shrink-0 px-3 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">
                                <span x-show="!copied">Copy</span>
                                <span x-show="copied" x-cloak>Copied</span>
                            </button>
                        </div>

                        <div class="flex items-center gap-4 mt-3">
                            <a href="{{ $project->shareUrl() }}" target="_blank" rel="noopener"
                               class="text-sm text-bark-600 dark:text-cream-200 hover:text-terracotta-600 dark:hover:text-terracotta-400">Preview</a>
                            <button type="button" wire:click="rotateShareToken"
                                    wire:confirm="Generate a new link? The current one will stop working immediately."
                                    class="text-sm text-gray-500 dark:text-gray-400 hover:text-bark-700 dark:hover:text-cream-200">New link</button>
                            <button type="button" wire:click="disableClientBoard"
                                    wire:confirm="Turn off the client board? The link will stop working until you re-enable it."
                                    class="text-sm text-red-400 hover:text-red-600 ml-auto">Turn off</button>
                        </div>
                    @else
                        <button type="button" wire:click="enableClientBoard"
                                class="inline-flex items-center gap-1.5 px-3 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.8"><path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244"/></svg>
                            Create a share link
                        </button>
                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">Anyone with the link can view and post. Revoke it any time.</p>
                    @endif
                </div>
            </div>

            <!-- Log -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
                <div class="p-5">
                    <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200 mb-4">Activity Log</h3>

                    @if(session('log-message'))
                        <div class="mb-4 p-3 bg-moss-50 dark:bg-moss-900/20 border border-moss-200 dark:border-moss-700 text-moss-700 dark:text-moss-300 rounded-lg text-sm">
                            {{ session('log-message') }}
                        </div>
                    @endif

                    <form wire:submit="addLog" class="mb-5">
                        <div class="flex gap-2 mb-2">
                            <input type="text" wire:model="newLogEntry" placeholder="What did you do?" class="flex-1 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                            <input type="number" step="0.25" min="0" wire:model="newLogHours" placeholder="Hrs" class="w-16 rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                            <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">Log</button>
                        </div>
                        @error('newLogEntry') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </form>

                    <div class="space-y-3 max-h-96 overflow-y-auto">
                        @forelse($logs as $log)
                            <div class="border-l-2 border-bark-200 dark:border-gray-600 pl-3 py-1">
                                <p class="text-sm text-bark-800 dark:text-cream-200">{{ $log->entry }}</p>
                                <p class="text-xs text-gray-400 mt-1">
                                    {{ $log->created_at->format('j M Y, g:ia') }}
                                    @if($log->hours)
                                        <span class="ml-1 px-1.5 py-0.5 bg-bark-100 text-bark-600 dark:bg-bark-900/30 dark:text-bark-400 rounded text-xs">{{ $log->hours }}h</span>
                                    @endif
                                </p>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 italic">No entries yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Info -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
                <div class="p-5">
                    <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200 mb-3">Info</h3>
                    <dl class="space-y-2 text-sm">
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                            <dd class="text-bark-700 dark:text-cream-200">{{ $project->created_at->format('j M Y') }}</dd>
                        </div>
                        <div class="flex justify-between">
                            <dt class="text-gray-500 dark:text-gray-400">Last touched</dt>
                            <dd class="text-bark-700 dark:text-cream-200">{{ $project->last_touched_at?->diffForHumans() ?? 'Never' }}</dd>
                        </div>
                    </dl>
                </div>
            </div>
        </div>
    </div>

    {{-- Project discussion — a chat room scoped to this project --}}
    <div class="mt-6">
        <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200 mb-3">Discussion</h3>
        <livewire:messenger :project-id="$project->id" :key="'project-chat-'.$project->id" />
    </div>
</div>
