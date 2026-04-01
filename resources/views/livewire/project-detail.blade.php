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
        <button
            wire:click="delete"
            wire:confirm="Are you sure you want to delete this project?"
            class="text-sm text-red-400 hover:text-red-600 font-medium"
        >
            Delete project
        </button>
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

            <!-- Issues Section -->
            <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
                <div class="p-6">
                    <div class="flex justify-between items-center mb-4">
                        <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200">
                            Issues
                            @if($issues->where('status', '!=', \App\Enums\IssueStatus::Done)->count() > 0)
                                <span class="ml-2 px-2 py-0.5 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">
                                    {{ $issues->where('status', '!=', \App\Enums\IssueStatus::Done)->count() }} open
                                </span>
                            @endif
                        </h3>
                        <div class="flex gap-2">
                            @if($project->github_repo && \App\Services\GitHubService::isConfigured())
                                <button
                                    wire:click="syncGitHubIssues"
                                    wire:loading.attr="disabled"
                                    wire:target="syncGitHubIssues"
                                    class="px-3 py-1.5 text-sm font-medium text-gray-600 dark:text-gray-400 bg-cream-100 dark:bg-gray-700 rounded-lg hover:bg-cream-200 dark:hover:bg-gray-600 disabled:opacity-50"
                                >
                                    <span wire:loading.remove wire:target="syncGitHubIssues">Sync GitHub</span>
                                    <span wire:loading wire:target="syncGitHubIssues">Syncing...</span>
                                </button>
                            @endif
                            <button
                                wire:click="$toggle('showIssueForm')"
                                class="px-3 py-1.5 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors"
                            >
                                {{ $showIssueForm ? 'Cancel' : 'Add Issue' }}
                            </button>
                        </div>
                    </div>

                    @if(session('issue-message'))
                        <div class="mb-4 p-3 bg-moss-50 dark:bg-moss-900/20 border border-moss-200 dark:border-moss-700 text-moss-700 dark:text-moss-300 rounded-lg text-sm">
                            {{ session('issue-message') }}
                        </div>
                    @endif

                    @if($showIssueForm)
                        <div class="mb-6 p-4 bg-cream-50 dark:bg-gray-700/50 rounded-xl space-y-3 border border-cream-200 dark:border-gray-600">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title</label>
                                <input type="text" wire:model.live="newIssueTitle" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="What needs doing?">
                                @error('newIssueTitle') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                <textarea wire:model.live="newIssueDescription" rows="3" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="Details..."></textarea>
                            </div>

                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urgency</label>
                                <select wire:model.live="newIssueUrgency" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm">
                                    @foreach($issueUrgencies as $u)
                                        <option value="{{ $u->value }}">{{ $u->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <details class="border border-cream-200 dark:border-gray-600 rounded-lg">
                                <summary class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 cursor-pointer hover:text-bark-600 dark:hover:text-cream-200">
                                    Parse from client email
                                </summary>
                                <div class="px-3 pb-3 pt-1">
                                    <textarea wire:model.live="newIssueEmail" rows="4" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-terracotta-400 focus:ring-terracotta-400 sm:text-sm" placeholder="Paste email..."></textarea>
                                    <button wire:click="parseEmail" wire:loading.attr="disabled" wire:target="parseEmail" class="mt-2 px-3 py-1.5 text-sm font-medium text-bark-600 dark:text-bark-300 bg-bark-100 dark:bg-bark-900/30 rounded-lg hover:bg-bark-200 disabled:opacity-50">
                                        <span wire:loading.remove wire:target="parseEmail">Parse with AI</span>
                                        <span wire:loading wire:target="parseEmail">Parsing...</span>
                                    </button>
                                </div>
                            </details>

                            @if(count($newIssueTasks) > 0)
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Extracted Tasks ({{ count($newIssueTasks) }})</label>
                                    <div class="bg-white dark:bg-gray-800 border border-cream-200 dark:border-gray-600 rounded-lg p-2 space-y-1">
                                        @foreach($newIssueTasks as $task)
                                            <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                <svg class="w-4 h-4 text-moss-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                                <span>{{ $task }}</span>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <div class="flex justify-end">
                                <button wire:click="createIssue" class="px-4 py-2 text-sm font-medium text-white bg-terracotta-500 rounded-lg hover:bg-terracotta-600 transition-colors">Create Issue</button>
                            </div>
                        </div>
                    @endif

                    <!-- Issue List -->
                    <div class="space-y-2">
                        @forelse($issues as $issue)
                            <div class="border border-cream-200 dark:border-gray-600 rounded-xl {{ $issue->status === \App\Enums\IssueStatus::Done ? 'opacity-50' : '' }}" x-data="{ expanded: false }">
                                <div class="flex items-center justify-between gap-2 p-3">
                                    <div class="flex items-center gap-2 flex-1 min-w-0">
                                        <button @click="expanded = !expanded" class="text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 shrink-0">
                                            <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                                        </button>
                                        <span class="text-sm font-medium text-bark-800 dark:text-cream-200 truncate {{ $issue->status === \App\Enums\IssueStatus::Done ? 'line-through' : '' }}">{{ $issue->title }}</span>
                                        @php
                                            $urgencyColors = [
                                                'gray' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                                                'yellow' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                                                'red' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
                                            ];
                                        @endphp
                                        <span class="px-1.5 py-0.5 text-xs rounded-full {{ $urgencyColors[$issue->urgency->color()] }} shrink-0">{{ $issue->urgency->label() }}</span>
                                        @if($issue->tasks_count > 0)
                                            <span class="px-1.5 py-0.5 text-xs rounded-full {{ $issue->completed_tasks_count === $issue->tasks_count ? 'bg-moss-100 text-moss-700 dark:bg-moss-900/30 dark:text-moss-400' : 'bg-bark-100 text-bark-600 dark:bg-bark-900/30 dark:text-bark-400' }} shrink-0">
                                                {{ $issue->completed_tasks_count }}/{{ $issue->tasks_count }}
                                            </span>
                                        @endif
                                        @if($issue->github_issue_number && $project->github_repo)
                                            <a href="https://github.com/{{ $project->github_repo }}/issues/{{ $issue->github_issue_number }}" target="_blank" class="text-gray-400 hover:text-bark-600 dark:hover:text-cream-200 shrink-0">
                                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                                            </a>
                                        @endif
                                    </div>
                                    <select wire:change="updateIssueStatus({{ $issue->id }}, $event.target.value)" class="text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 pl-2 pr-6 shrink-0 focus:border-terracotta-400 focus:ring-terracotta-400">
                                        @foreach($issueStatuses as $is)
                                            <option value="{{ $is->value }}" {{ $issue->status === $is ? 'selected' : '' }}>{{ $is->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div x-show="expanded" x-collapse class="px-3 pb-3 text-sm text-gray-600 dark:text-gray-400">
                                    @if($issue->description)
                                        <div class="whitespace-pre-wrap mb-3 pl-6">{{ $issue->description }}</div>
                                    @endif

                                    <div class="pl-6 mb-3" x-data="{ newTask: '' }">
                                        @if($issue->tasks->count() > 0)
                                            <div class="space-y-1 mb-2">
                                                @foreach($issue->tasks as $task)
                                                    <div class="flex items-center gap-2 group">
                                                        <button wire:click="toggleTask({{ $task->id }})" class="flex items-center gap-2 flex-1 text-left hover:bg-cream-50 dark:hover:bg-gray-700/50 rounded-lg px-1 py-0.5 -mx-1">
                                                            @if($task->is_complete)
                                                                <svg class="w-4 h-4 text-moss-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                                            @else
                                                                <svg class="w-4 h-4 text-cream-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9" stroke-width="2"/></svg>
                                                            @endif
                                                            <span class="{{ $task->is_complete ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">{{ $task->description }}</span>
                                                            @if($task->is_ai_generated)
                                                                <span class="text-xs text-bark-400">AI</span>
                                                            @endif
                                                        </button>
                                                        <button wire:click="deleteTask({{ $task->id }})" wire:confirm="Delete this task?" class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1 transition-opacity">
                                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                                        </button>
                                                    </div>
                                                @endforeach
                                            </div>
                                        @endif

                                        <div class="flex items-center gap-2">
                                            <input type="text" x-model="newTask" @keydown.enter="if(newTask.trim()) { $wire.addTask({{ $issue->id }}, newTask); newTask = ''; }" placeholder="Add a task..." class="flex-1 text-xs rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1.5 px-2 focus:border-terracotta-400 focus:ring-terracotta-400">
                                            <button @click="if(newTask.trim()) { $wire.addTask({{ $issue->id }}, newTask); newTask = ''; }" class="text-xs px-2 py-1.5 bg-cream-100 dark:bg-gray-700 text-bark-600 dark:text-gray-300 rounded-lg hover:bg-cream-200 dark:hover:bg-gray-600">Add</button>
                                        </div>
                                    </div>

                                    @if($issue->raw_email)
                                        <div class="pl-6 flex items-center gap-2 mb-2">
                                            <button wire:click="reparseIssue({{ $issue->id }})" wire:loading.attr="disabled" wire:target="reparseIssue({{ $issue->id }})" class="text-xs px-2 py-1 bg-bark-100 dark:bg-bark-900/30 text-bark-600 dark:text-bark-400 rounded-lg hover:bg-bark-200">
                                                <span wire:loading.remove wire:target="reparseIssue({{ $issue->id }})">Re-parse with AI</span>
                                                <span wire:loading wire:target="reparseIssue({{ $issue->id }})">Parsing...</span>
                                            </button>
                                        </div>
                                        <details class="pl-6">
                                            <summary class="text-xs text-gray-400 cursor-pointer hover:text-bark-600 dark:hover:text-cream-200">Original email</summary>
                                            <div class="mt-1 p-2 bg-cream-50 dark:bg-gray-900 rounded-lg text-xs whitespace-pre-wrap">{{ $issue->raw_email }}</div>
                                        </details>
                                    @endif
                                    <p class="text-xs text-gray-400 mt-2 pl-6">{{ $issue->created_at->format('j M Y, g:ia') }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-gray-400 italic">No issues yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
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
</div>
