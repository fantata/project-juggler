<x-slot name="header">
    <div class="flex justify-between items-center">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            Projects
        </h2>
        <button
            wire:click="$set('showQuickAdd', true)"
            class="inline-flex items-center px-4 py-2 bg-gray-800 dark:bg-gray-200 border border-transparent rounded-md font-semibold text-xs text-white dark:text-gray-800 uppercase tracking-widest hover:bg-gray-700 dark:hover:bg-white"
        >
            Add Project
        </button>
    </div>
</x-slot>

<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <!-- Filters -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="p-4 flex flex-wrap gap-4 items-end">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Type</label>
                    <select wire:model.live="filterType" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                        <option value="">All Types</option>
                        @foreach($types as $type)
                            <option value="{{ $type->value }}">{{ $type->label() }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Money</label>
                    <select wire:model.live="filterMoneyStatus" class="rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm text-sm">
                        <option value="">All</option>
                        @foreach($moneyStatuses as $moneyStatus)
                            <option value="{{ $moneyStatus->value }}">{{ $moneyStatus->label() }}</option>
                        @endforeach
                    </select>
                </div>
                @if($filterType || $filterMoneyStatus)
                    <button wire:click="clearFilters" class="text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200 underline">
                        Clear filters
                    </button>
                @endif
                <div class="ml-auto">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox" wire:model.live="showCompleted" class="rounded border-gray-300 dark:border-gray-700 text-indigo-600 shadow-sm focus:ring-indigo-500 dark:focus:ring-indigo-600 dark:bg-gray-900">
                        <span class="ml-2 text-sm text-gray-600 dark:text-gray-400">Show completed</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Active Projects (Your Court) -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Your Court ({{ $activeProjects->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                @include('livewire.partials.project-table', ['projects' => $activeProjects])
            </div>
        </div>

        <!-- Waiting Projects (Client's Court) -->
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg mb-6">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Waiting on Client ({{ $waitingProjects->count() }})</h3>
            </div>
            <div class="overflow-x-auto">
                @include('livewire.partials.project-table', ['projects' => $waitingProjects])
            </div>
        </div>

        <!-- Completed Projects -->
        @if($showCompleted)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg opacity-75">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Completed / Killed ({{ $completedProjects->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    @include('livewire.partials.project-table', ['projects' => $completedProjects])
                </div>
            </div>
        @endif
    </div>

    <!-- Quick Add Modal -->
    @if($showQuickAdd)
        <div class="fixed inset-0 bg-gray-500 dark:bg-gray-900 bg-opacity-75 dark:bg-opacity-75 flex items-center justify-center z-50" wire:click.self="$set('showQuickAdd', false)">
            <div class="bg-white dark:bg-gray-800 rounded-lg shadow-xl max-w-lg w-full mx-4" @close-modal.window="$wire.showQuickAdd = false" @project-created.window="$wire.$refresh()">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-gray-700 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-gray-100">Add Project</h3>
                    <button wire:click="$set('showQuickAdd', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                </div>
                <livewire:quick-add />
            </div>
        </div>
    @endif
</div>
