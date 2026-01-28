<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            My Tasks
            @if($totalTasks > 0)
                <span class="ml-2 text-sm font-normal text-gray-500 dark:text-gray-400">
                    {{ $completedTasks }}/{{ $totalTasks }} done
                </span>
            @endif
        </h2>
        <div class="flex items-center gap-3">
            @if($tasksByProject->count() > 0)
                <button
                    x-data
                    @click="
                        const text = document.getElementById('tasks-for-copy').innerText;
                        navigator.clipboard.writeText(text).then(() => {
                            $el.querySelector('.copy-icon').classList.add('hidden');
                            $el.querySelector('.check-icon').classList.remove('hidden');
                            setTimeout(() => {
                                $el.querySelector('.copy-icon').classList.remove('hidden');
                                $el.querySelector('.check-icon').classList.add('hidden');
                            }, 2000);
                        });
                    "
                    class="p-2 text-gray-500 dark:text-gray-400 hover:text-gray-700 dark:hover:text-gray-200 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-md"
                    title="Copy tasks for AI"
                >
                    <svg class="w-5 h-5 copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg class="w-5 h-5 check-icon hidden text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            @endif
            <a href="{{ route('dashboard') }}" wire:navigate class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">
                Back to Projects
            </a>
        </div>
    </div>
</x-slot>

<div class="py-6">
    <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
        <!-- Progress Bar -->
        @if($totalTasks > 0)
            <div class="mb-6">
                <div class="flex justify-between text-sm text-gray-600 dark:text-gray-400 mb-1">
                    <span>Progress</span>
                    <span>{{ round(($completedTasks / $totalTasks) * 100) }}%</span>
                </div>
                <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                    <div class="bg-green-500 h-2 rounded-full transition-all duration-300" style="width: {{ ($completedTasks / $totalTasks) * 100 }}%"></div>
                </div>
            </div>
        @endif

        <!-- Filter -->
        <div class="mb-4 flex items-center gap-4">
            <label class="inline-flex items-center cursor-pointer">
                <input type="checkbox" wire:model.live="showCompleted" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-gray-900">
                <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Show completed</span>
            </label>
        </div>

        <!-- Tasks by Project -->
        @forelse($tasksByProject as $projectName => $tasks)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-4">
                <div class="p-4">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-300 mb-3 flex items-center gap-2">
                        <span>{{ $projectName }}</span>
                        <span class="text-xs font-normal px-2 py-0.5 bg-gray-100 dark:bg-gray-700 rounded-full">
                            {{ $tasks->where('is_complete', false)->count() }} remaining
                        </span>
                    </h3>
                    <div class="space-y-1">
                        @foreach($tasks as $task)
                            <div class="flex items-center gap-3 group {{ $task->is_complete ? 'opacity-50' : '' }}">
                                <button
                                    wire:click="toggleTask({{ $task->id }})"
                                    class="flex items-center gap-3 flex-1 text-left hover:bg-gray-50 dark:hover:bg-gray-700/50 rounded px-2 py-1.5 -mx-2"
                                >
                                    @if($task->is_complete)
                                        <svg class="w-5 h-5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                        </svg>
                                    @else
                                        <svg class="w-5 h-5 text-gray-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                        </svg>
                                    @endif
                                    <div class="flex-1 min-w-0">
                                        <span class="text-sm text-gray-800 dark:text-gray-200 {{ $task->is_complete ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                            {{ $task->description }}
                                        </span>
                                        <div class="flex items-center gap-2 mt-0.5">
                                            <a href="{{ route('projects.show', $task->issue->project) }}" wire:navigate class="text-xs text-indigo-500 hover:text-indigo-700 dark:text-indigo-400">
                                                {{ $task->issue->title }}
                                            </a>
                                            @if($task->is_ai_generated)
                                                <span class="text-xs text-indigo-400 dark:text-indigo-500">AI</span>
                                            @endif
                                        </div>
                                    </div>
                                </button>
                                <button
                                    wire:click="deleteTask({{ $task->id }})"
                                    wire:confirm="Delete this task?"
                                    class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1 shrink-0"
                                >
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                </button>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        @empty
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-8 text-center">
                    <svg class="w-12 h-12 mx-auto text-gray-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <p class="text-gray-500 dark:text-gray-400">
                        @if($showCompleted)
                            No tasks found.
                        @else
                            All caught up! No pending tasks.
                        @endif
                    </p>
                    <a href="{{ route('dashboard') }}" wire:navigate class="inline-block mt-4 text-sm text-indigo-600 dark:text-indigo-400 hover:text-indigo-800">
                        View projects to add tasks
                    </a>
                </div>
            </div>
        @endforelse

        <!-- Hidden element for copy -->
        <div id="tasks-for-copy" class="sr-only" aria-hidden="true">Here are my current tasks:
@foreach($tasksByProject as $projectName => $tasks)

## {{ $projectName }}
@foreach($tasks->where('is_complete', false) as $task)
- [ ] {{ $task->description }} ({{ $task->issue->title }})
@endforeach
@endforeach
        </div>
    </div>
</div>
