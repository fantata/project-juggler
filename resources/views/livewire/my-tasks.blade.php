<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">
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
                    class="p-2 text-gray-400 dark:text-gray-500 hover:text-bark-600 dark:hover:text-cream-200 hover:bg-cream-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
                    title="Copy tasks for AI"
                >
                    <svg class="w-5 h-5 copy-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                    </svg>
                    <svg class="w-5 h-5 check-icon hidden text-moss-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </button>
            @endif
        </div>
    </div>
</x-slot>

<div class="max-w-4xl mx-auto">
    <!-- Progress Bar -->
    @if($totalTasks > 0)
        <div class="mb-6">
            <div class="flex justify-between text-sm text-gray-500 dark:text-gray-400 mb-1.5">
                <span>Progress</span>
                <span class="font-medium text-bark-600 dark:text-bark-300">{{ round(($completedTasks / $totalTasks) * 100) }}%</span>
            </div>
            <div class="w-full bg-cream-200 dark:bg-gray-700 rounded-full h-2.5">
                <div class="bg-gradient-to-r from-terracotta-400 to-moss-500 h-2.5 rounded-full transition-all duration-500" style="width: {{ ($completedTasks / $totalTasks) * 100 }}%"></div>
            </div>
        </div>
    @endif

    <!-- Filter -->
    <div class="mb-4">
        <label class="inline-flex items-center cursor-pointer gap-2">
            <input type="checkbox" wire:model.live="showCompleted" class="rounded border-gray-300 dark:border-gray-600 text-terracotta-500 focus:ring-terracotta-400 dark:bg-gray-700">
            <span class="text-sm text-gray-500 dark:text-gray-400">Show completed</span>
        </label>
    </div>

    <!-- Tasks by Project -->
    @forelse($tasksByProject as $projectName => $items)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 mb-4 overflow-hidden">
            <div class="px-5 py-3 bg-cream-50 dark:bg-gray-750 border-b border-cream-200 dark:border-gray-700">
                <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 flex items-center gap-2">
                    <span>{{ $projectName }}</span>
                    <span class="text-xs font-normal px-2 py-0.5 bg-cream-200 dark:bg-gray-600 text-bark-500 dark:text-gray-300 rounded-full">
                        {{ $items->where('is_complete', false)->count() }} remaining
                    </span>
                </h3>
            </div>
            <div class="p-3 space-y-0.5">
                @foreach($items as $item)
                    <div class="flex items-center gap-3 group {{ $item->is_complete ? 'opacity-40' : '' }}">
                        <button
                            wire:click="{{ $item->type === 'issue' ? 'toggleIssue' : 'toggleTask' }}({{ $item->id }})"
                            class="flex items-center gap-3 flex-1 text-left hover:bg-cream-50 dark:hover:bg-gray-700/50 rounded-lg px-2 py-2 -mx-2"
                        >
                            @if($item->is_complete)
                                <svg class="w-5 h-5 text-moss-500 shrink-0" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                                </svg>
                            @else
                                <svg class="w-5 h-5 text-cream-300 dark:text-gray-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <circle cx="12" cy="12" r="9" stroke-width="2"/>
                                </svg>
                            @endif
                            <div class="flex-1 min-w-0">
                                <span class="text-sm text-bark-800 dark:text-cream-200 {{ $item->is_complete ? 'line-through text-gray-400 dark:text-gray-500' : '' }}">
                                    {{ $item->description }}
                                </span>
                                <div class="flex items-center gap-2 mt-0.5">
                                    @if($item->type === 'task' && $item->parent_title)
                                        <a href="{{ route('projects.show', $item->project_id) }}" wire:navigate class="text-xs text-terracotta-500 hover:text-terracotta-700 dark:text-terracotta-400">
                                            {{ $item->parent_title }}
                                        </a>
                                    @endif
                                    @if($item->type === 'issue')
                                        @if($item->urgency === 'high')
                                            <span class="text-xs text-red-500 dark:text-red-400 font-medium">High</span>
                                        @elseif($item->urgency === 'low')
                                            <span class="text-xs text-gray-400 dark:text-gray-500">Low</span>
                                        @endif
                                    @endif
                                    @if($item->is_ai_generated)
                                        <span class="text-xs text-bark-400 dark:text-bark-500">AI</span>
                                    @endif
                                </div>
                            </div>
                        </button>
                        <button
                            wire:click="{{ $item->type === 'issue' ? 'deleteIssue' : 'deleteTask' }}({{ $item->id }})"
                            wire:confirm="Delete this {{ $item->type === 'issue' ? 'issue' : 'task' }}?"
                            class="opacity-0 group-hover:opacity-100 text-red-400 hover:text-red-600 p-1 shrink-0 transition-opacity"
                        >
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                            </svg>
                        </button>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700">
            <div class="p-12 text-center">
                <svg class="w-12 h-12 mx-auto text-moss-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-bark-600 dark:text-gray-400 mb-1">
                    @if($showCompleted)
                        No tasks found.
                    @else
                        All caught up!
                    @endif
                </p>
                <p class="text-sm text-gray-400 dark:text-gray-500">
                    @if(!$showCompleted)
                        Nothing pending. Nice work.
                    @endif
                </p>
            </div>
        </div>
    @endforelse

    <!-- Hidden element for copy -->
    <div id="tasks-for-copy" class="sr-only" aria-hidden="true">Here are my current tasks:
@foreach($tasksByProject as $projectName => $items)

## {{ $projectName }}
@foreach($items->where('is_complete', false) as $item)
- [ ] {{ $item->description }}{{ $item->parent_title ? ' (' . $item->parent_title . ')' : '' }}
@endforeach
@endforeach
    </div>
</div>
