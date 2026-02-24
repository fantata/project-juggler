<x-slot name="header">
    <div class="flex justify-between items-center">
        <div class="flex items-center gap-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-300">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ $project->name }}
            </h2>
        </div>
        <button
            wire:click="delete"
            wire:confirm="Are you sure you want to delete this project?"
            class="inline-flex items-center px-4 py-2 bg-red-600 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest hover:bg-red-500"
        >
            Delete
        </button>
    </div>
</x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        @if(session('message'))
            <div class="mb-4 p-4 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded">
                {{ session('message') }}
            </div>
        @endif

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            <!-- Edit Form -->
            <div class="lg:col-span-2">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <form wire:submit="save" class="p-6 space-y-4">
                        <div>
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
                            <input type="text" wire:model="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            @error('name') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="type" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Type *</label>
                                <select wire:model="type" id="type" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($types as $t)
                                        <option value="{{ $t->value }}">{{ $t->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Status *</label>
                                <select wire:model="status" id="status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($statuses as $s)
                                        <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="money_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Money Status</label>
                                <select wire:model="money_status" id="money_status" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                    @foreach($moneyStatuses as $ms)
                                        <option value="{{ $ms->value }}">{{ $ms->label() }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label for="money_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value (&pound;)</label>
                                <input type="number" step="0.01" wire:model="money_value" id="money_value" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                            </div>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label for="deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Deadline</label>
                                <input type="date" wire:model="deadline" id="deadline" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                            </div>

                            <div>
                                <label for="next_action" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Next Action</label>
                                <input type="text" wire:model="next_action" id="next_action" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Call client...">
                            </div>
                        </div>

                        <div>
                            <label for="notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Notes</label>
                            <textarea wire:model="notes" id="notes" rows="4" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="Project notes..."></textarea>
                        </div>

                        <div>
                            <label for="github_repo" class="block text-sm font-medium text-gray-700 dark:text-gray-300">GitHub Repo</label>
                            <div class="flex gap-2 items-center">
                                <input type="text" wire:model="github_repo" id="github_repo" placeholder="org/repo" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                @if($project->github_repo)
                                    <a href="https://github.com/{{ $project->github_repo }}" target="_blank" class="mt-1 text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 text-sm whitespace-nowrap">
                                        View &rarr;
                                    </a>
                                @endif
                            </div>
                        </div>

                        <div>
                            <label class="inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="is_retainer" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-gray-900">
                                <span class="ml-2 text-sm text-gray-700 dark:text-gray-300">Retainer client</span>
                            </label>
                        </div>

                        @if($is_retainer)
                            <div class="grid grid-cols-2 gap-4 p-4 bg-indigo-50 dark:bg-indigo-900/20 rounded-lg">
                                <div>
                                    <label for="retainer_frequency" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Payment Frequency</label>
                                    <select wire:model="retainer_frequency" id="retainer_frequency" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm">
                                        <option value="">Select...</option>
                                        @foreach($retainerFrequencies as $freq)
                                            <option value="{{ $freq->value }}">{{ $freq->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <div>
                                    <label for="retainer_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Retainer Amount (&pound;)</label>
                                    <input type="number" step="0.01" wire:model="retainer_amount" id="retainer_amount" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" placeholder="0.00">
                                </div>
                            </div>
                        @endif

                        <div class="flex justify-end pt-4 border-t border-gray-200 dark:border-gray-700">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-800 dark:bg-gray-200 dark:text-gray-800 border border-transparent rounded-md hover:bg-gray-700 dark:hover:bg-white">
                                Save Changes
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Issues Section -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <div class="flex justify-between items-center mb-4">
                            <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">
                                Issues
                                @if($issues->where('status', '!=', \App\Enums\IssueStatus::Done)->count() > 0)
                                    <span class="ml-2 px-2 py-0.5 text-xs bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 rounded-full">
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
                                        class="px-3 py-1.5 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 rounded-md hover:bg-gray-200 dark:hover:bg-gray-600 disabled:opacity-50"
                                    >
                                        <span wire:loading.remove wire:target="syncGitHubIssues">Sync from GitHub</span>
                                        <span wire:loading wire:target="syncGitHubIssues">Syncing...</span>
                                    </button>
                                @endif
                                <button
                                    wire:click="$toggle('showIssueForm')"
                                    class="px-3 py-1.5 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-500"
                                >
                                    {{ $showIssueForm ? 'Cancel' : 'Add Issue' }}
                                </button>
                            </div>
                        </div>

                        @if(session('issue-message'))
                            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded text-sm">
                                {{ session('issue-message') }}
                            </div>
                        @endif

                        @if($showIssueForm)
                            <div class="mb-6 p-4 bg-gray-50 dark:bg-gray-700/50 rounded-lg space-y-3">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Title *</label>
                                    <input
                                        type="text"
                                        wire:model.live="newIssueTitle"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Issue title..."
                                    >
                                    @error('newIssueTitle') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Description</label>
                                    <textarea
                                        wire:model.live="newIssueDescription"
                                        rows="4"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                        placeholder="Description or action items..."
                                    ></textarea>
                                </div>

                                <div>
                                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Urgency</label>
                                    <select
                                        wire:model.live="newIssueUrgency"
                                        class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        @foreach($issueUrgencies as $u)
                                            <option value="{{ $u->value }}">{{ $u->label() }}</option>
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Optional: Parse from email -->
                                <details class="border border-gray-200 dark:border-gray-600 rounded-md">
                                    <summary class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400 cursor-pointer hover:text-gray-700 dark:hover:text-gray-300">
                                        Parse from client email
                                    </summary>
                                    <div class="px-3 pb-3 pt-1">
                                        <textarea
                                            wire:model.live="newIssueEmail"
                                            rows="5"
                                            class="block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                            placeholder="Paste the client's email here..."
                                        ></textarea>
                                        <button
                                            wire:click="parseEmail"
                                            wire:loading.attr="disabled"
                                            wire:target="parseEmail"
                                            class="mt-2 px-3 py-1.5 text-sm font-medium text-indigo-700 dark:text-indigo-300 bg-indigo-100 dark:bg-indigo-900/50 rounded-md hover:bg-indigo-200 dark:hover:bg-indigo-900 disabled:opacity-50"
                                        >
                                            <span wire:loading.remove wire:target="parseEmail">Parse with AI</span>
                                            <span wire:loading wire:target="parseEmail">Parsing...</span>
                                        </button>
                                    </div>
                                </details>

                                @if(count($newIssueTasks) > 0)
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Extracted Tasks ({{ count($newIssueTasks) }})</label>
                                        <div class="bg-white dark:bg-gray-800 border border-gray-200 dark:border-gray-600 rounded-md p-2 space-y-1">
                                            @foreach($newIssueTasks as $task)
                                                <div class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                                    </svg>
                                                    <span>{{ $task }}</span>
                                                </div>
                                            @endforeach
                                        </div>
                                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">These tasks will be created automatically when you create the issue.</p>
                                    </div>
                                @endif

                                <div class="flex justify-end">
                                    <button
                                        wire:click="createIssue"
                                        class="px-4 py-2 text-sm font-medium text-white bg-orange-600 rounded-md hover:bg-orange-500"
                                    >
                                        Create Issue
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Issue List -->
                        <div class="space-y-3">
                            @forelse($issues as $issue)
                                <div class="border border-gray-200 dark:border-gray-600 rounded-lg p-3 {{ $issue->status === \App\Enums\IssueStatus::Done ? 'opacity-60' : '' }}" x-data="{ expanded: false }">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="flex items-center gap-2 flex-1 min-w-0">
                                            <button @click="expanded = !expanded" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0">
                                                <svg class="w-4 h-4 transition-transform" :class="expanded && 'rotate-90'" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                                                </svg>
                                            </button>
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100 truncate {{ $issue->status === \App\Enums\IssueStatus::Done ? 'line-through' : '' }}">
                                                {{ $issue->title }}
                                            </span>
                                            @php
                                                $urgencyColors = [
                                                    'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                                                    'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                                                    'red' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                                                ];
                                            @endphp
                                            <span class="px-1.5 py-0.5 text-xs rounded {{ $urgencyColors[$issue->urgency->color()] }} shrink-0">
                                                {{ $issue->urgency->label() }}
                                            </span>
                                            @if($issue->tasks_count > 0)
                                                <span class="px-1.5 py-0.5 text-xs rounded {{ $issue->completed_tasks_count === $issue->tasks_count ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300' : 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300' }} shrink-0">
                                                    {{ $issue->completed_tasks_count }}/{{ $issue->tasks_count }}
                                                </span>
                                            @endif
                                            @if($issue->github_issue_number && $project->github_repo)
                                                <a href="https://github.com/{{ $project->github_repo }}/issues/{{ $issue->github_issue_number }}" target="_blank" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 shrink-0" title="View on GitHub">
                                                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16"><path d="M8 0C3.58 0 0 3.58 0 8c0 3.54 2.29 6.53 5.47 7.59.4.07.55-.17.55-.38 0-.19-.01-.82-.01-1.49-2.01.37-2.53-.49-2.69-.94-.09-.23-.48-.94-.82-1.13-.28-.15-.68-.52-.01-.53.63-.01 1.08.58 1.23.82.72 1.21 1.87.87 2.33.66.07-.52.28-.87.51-1.07-1.78-.2-3.64-.89-3.64-3.95 0-.87.31-1.59.82-2.15-.08-.2-.36-1.02.08-2.12 0 0 .67-.21 2.2.82.64-.18 1.32-.27 2-.27.68 0 1.36.09 2 .27 1.53-1.04 2.2-.82 2.2-.82.44 1.1.16 1.92.08 2.12.51.56.82 1.27.82 2.15 0 3.07-1.87 3.75-3.65 3.95.29.25.54.73.54 1.48 0 1.07-.01 1.93-.01 2.2 0 .21.15.46.55.38A8.013 8.013 0 0016 8c0-4.42-3.58-8-8-8z"/></svg>
                                                </a>
                                            @elseif($project->github_repo && !$issue->github_issue_number)
                                                <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">(local)</span>
                                            @endif
                                        </div>
                                        <select
                                            wire:change="updateIssueStatus({{ $issue->id }}, $event.target.value)"
                                            class="text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 pl-2 pr-6 shrink-0"
                                        >
                                            @foreach($issueStatuses as $is)
                                                <option value="{{ $is->value }}" {{ $issue->status === $is ? 'selected' : '' }}>{{ $is->label() }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div x-show="expanded" x-collapse class="mt-3 text-sm text-gray-600 dark:text-gray-400">
                                        @if($issue->description)
                                            <div class="whitespace-pre-wrap mb-3">{{ $issue->description }}</div>
                                        @endif

                                        <!-- Tasks Section -->
                                        <div class="mb-3" x-data="{ newTask: '' }">
                                            @if($issue->tasks->count() > 0)
                                                <div class="space-y-1 mb-2">
                                                    @foreach($issue->tasks as $task)
                                                        <div class="flex items-center gap-2 group">
                                                            <button
                                                                wire:click="toggleTask({{ $task->id }})"
                                                                class="flex items-center gap-2 flex-1 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-1 py-0.5 -mx-1"
                                                            >
                                                                @if($task->is_complete)
                                                                    <svg class="w-4 h-4 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                                                    </svg>
                                                                @else
                                                                    <svg class="w-4 h-4 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                        <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                                                    </svg>
                                                                @endif
                                                                <span class="{{ $task->is_complete ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                                                    {{ $task->description }}
                                                                </span>
                                                                @if($task->is_ai_generated)
                                                                    <span class="text-xs text-indigo-400 dark:text-indigo-500">AI</span>
                                                                @endif
                                                            </button>
                                                            <button
                                                                wire:click="deleteTask({{ $task->id }})"
                                                                wire:confirm="Delete this task?"
                                                                class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1"
                                                            >
                                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                                </svg>
                                                            </button>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            @endif

                                            <!-- Add Task Input -->
                                            <div class="flex items-center gap-2">
                                                <input
                                                    type="text"
                                                    x-model="newTask"
                                                    @keydown.enter="if(newTask.trim()) { $wire.addTask({{ $issue->id }}, newTask); newTask = ''; }"
                                                    placeholder="Add a task..."
                                                    class="flex-1 text-xs rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300 py-1 px-2"
                                                >
                                                <button
                                                    @click="if(newTask.trim()) { $wire.addTask({{ $issue->id }}, newTask); newTask = ''; }"
                                                    class="text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300 rounded hover:bg-gray-200 dark:hover:bg-gray-600"
                                                >
                                                    Add
                                                </button>
                                            </div>
                                        </div>

                                        @if($issue->raw_email)
                                            <div class="flex items-center gap-2 mb-2">
                                                <button
                                                    wire:click="reparseIssue({{ $issue->id }})"
                                                    wire:loading.attr="disabled"
                                                    wire:target="reparseIssue({{ $issue->id }})"
                                                    class="text-xs px-2 py-1 bg-indigo-100 dark:bg-indigo-900/50 text-indigo-600 dark:text-indigo-400 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900"
                                                >
                                                    <span wire:loading.remove wire:target="reparseIssue({{ $issue->id }})">Re-parse with AI</span>
                                                    <span wire:loading wire:target="reparseIssue({{ $issue->id }})">Parsing...</span>
                                                </button>
                                            </div>
                                            <details>
                                                <summary class="text-xs text-gray-400 dark:text-gray-500 cursor-pointer hover:text-gray-600 dark:hover:text-gray-300">Original email</summary>
                                                <div class="mt-1 p-2 bg-gray-100 dark:bg-gray-900 rounded text-xs whitespace-pre-wrap">{{ $issue->raw_email }}</div>
                                            </details>
                                        @endif
                                        <p class="text-xs text-gray-400 dark:text-gray-500 mt-2">{{ $issue->created_at->format('j M Y, g:ia') }}</p>
                                    </div>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No issues yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log History -->
            <div class="lg:col-span-1">
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Activity Log</h3>

                        @if(session('log-message'))
                            <div class="mb-4 p-3 bg-green-100 dark:bg-green-900 border border-green-400 dark:border-green-700 text-green-700 dark:text-green-300 rounded text-sm">
                                {{ session('log-message') }}
                            </div>
                        @endif

                        <!-- Add Log Entry -->
                        <form wire:submit="addLog" class="mb-6">
                            <div class="flex gap-2 mb-2">
                                <input
                                    type="text"
                                    wire:model="newLogEntry"
                                    placeholder="What did you do?"
                                    class="flex-1 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                <input
                                    type="number"
                                    step="0.25"
                                    min="0"
                                    wire:model="newLogHours"
                                    placeholder="Hours"
                                    class="w-20 rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                >
                                <button type="submit" class="px-3 py-2 text-sm font-medium text-white bg-gray-800 dark:bg-gray-200 dark:text-gray-800 rounded-md hover:bg-gray-700 dark:hover:bg-white">
                                    Log
                                </button>
                            </div>
                            @error('newLogEntry') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                            @error('newLogHours') <span class="text-red-500 text-sm">{{ $message }}</span> @enderror
                        </form>

                        <!-- Log Entries -->
                        <div class="space-y-4 max-h-96 overflow-y-auto">
                            @forelse($logs as $log)
                                <div class="border-l-2 border-gray-200 dark:border-gray-600 pl-4 py-1">
                                    <p class="text-sm text-gray-800 dark:text-gray-200">{{ $log->entry }}</p>
                                    <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                                        {{ $log->created_at->format('j M Y, g:ia') }}
                                        @if($log->hours)
                                            <span class="ml-2 px-1.5 py-0.5 bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300 rounded">{{ $log->hours }}h</span>
                                        @endif
                                    </p>
                                </div>
                            @empty
                                <p class="text-sm text-gray-500 dark:text-gray-400 italic">No log entries yet.</p>
                            @endforelse
                        </div>
                    </div>
                </div>

                <!-- Meta Info -->
                <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mt-6">
                    <div class="p-6">
                        <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100 mb-4">Info</h3>
                        <dl class="space-y-2 text-sm">
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Created</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ $project->created_at->format('j M Y') }}</dd>
                            </div>
                            <div class="flex justify-between">
                                <dt class="text-gray-500 dark:text-gray-400">Last touched</dt>
                                <dd class="text-gray-900 dark:text-gray-100">{{ $project->last_touched_at?->diffForHumans() ?? 'Never' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
