<x-slot name="header">
    <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">Projects</h2>
</x-slot>

<div class="max-w-7xl mx-auto">
    <!-- Toolbar -->
    <div class="flex flex-wrap gap-3 items-center mb-6">
        <select wire:model.live="filterType" class="rounded-lg border-cream-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm text-sm focus:border-terracotta-400 focus:ring-terracotta-400">
            <option value="">All Types</option>
            @foreach($types as $type)
                <option value="{{ $type->value }}">{{ $type->label() }}</option>
            @endforeach
        </select>
        <select wire:model.live="filterMoneyStatus" class="rounded-lg border-cream-200 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm text-sm focus:border-terracotta-400 focus:ring-terracotta-400">
            <option value="">All Money</option>
            @foreach($moneyStatuses as $moneyStatus)
                <option value="{{ $moneyStatus->value }}">{{ $moneyStatus->label() }}</option>
            @endforeach
        </select>
        @if($filterType || $filterMoneyStatus)
            <button wire:click="clearFilters" class="text-sm text-terracotta-500 hover:text-terracotta-700 font-medium">
                Clear
            </button>
        @endif
        <label class="inline-flex items-center cursor-pointer gap-2">
            <input type="checkbox" wire:model.live="showCompleted" class="rounded border-gray-300 dark:border-gray-600 text-terracotta-500 focus:ring-terracotta-400 dark:bg-gray-700">
            <span class="text-sm text-gray-500 dark:text-gray-400">Show completed</span>
        </label>

        <div class="ml-auto flex items-center gap-3">
            <!-- View Switcher -->
            <div class="hidden sm:flex bg-cream-100 dark:bg-gray-700 rounded-lg p-0.5">
                <button wire:click="$set('viewMode', 'tiles')" class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $viewMode === 'tiles' ? 'bg-white dark:bg-gray-600 text-bark-700 dark:text-cream-200 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                    Tiles
                </button>
                <button wire:click="$set('viewMode', 'table')" class="px-3 py-1.5 text-xs font-medium rounded-md transition-colors {{ $viewMode === 'table' ? 'bg-white dark:bg-gray-600 text-bark-700 dark:text-cream-200 shadow-sm' : 'text-gray-500 dark:text-gray-400 hover:text-gray-700' }}">
                    Table
                </button>
            </div>
            <button
                wire:click="$set('showQuickAdd', true)"
                class="inline-flex items-center gap-2 px-4 py-2 bg-terracotta-500 text-white text-sm font-medium rounded-lg hover:bg-terracotta-600 transition-colors"
            >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                </svg>
                Add Project
            </button>
        </div>
    </div>

    @if($viewMode === 'tiles')
        <!-- TILES VIEW -->
        @include('livewire.partials.project-tiles', ['title' => 'Your Court', 'projects' => $activeProjects, 'accent' => 'terracotta'])
        @include('livewire.partials.project-tiles', ['title' => 'Retainers', 'projects' => $retainerProjects, 'accent' => 'moss'])
        @include('livewire.partials.project-tiles', ['title' => 'Waiting on Client', 'projects' => $waitingProjects, 'accent' => 'bark'])

        @if($showCompleted && $completedProjects->count() > 0)
            <div class="opacity-60">
                @include('livewire.partials.project-tiles', ['title' => 'Completed / Killed', 'projects' => $completedProjects, 'accent' => 'gray'])
            </div>
        @endif
    @else
        <!-- TABLE VIEW -->
        @foreach([
            ['title' => 'Your Court', 'projects' => $activeProjects, 'ring' => ''],
            ['title' => 'Retainers', 'projects' => $retainerProjects, 'ring' => 'ring-2 ring-moss-400'],
            ['title' => 'Waiting on Client', 'projects' => $waitingProjects, 'ring' => ''],
        ] as $section)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl mb-6 {{ $section['ring'] }}">
                <div class="px-6 py-4 border-b border-cream-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200">{{ $section['title'] }} ({{ $section['projects']->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    @include('livewire.partials.project-table', ['projects' => $section['projects']])
                </div>
            </div>
        @endforeach

        @if($showCompleted && $completedProjects->count() > 0)
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm rounded-xl opacity-60">
                <div class="px-6 py-4 border-b border-cream-200 dark:border-gray-700">
                    <h3 class="text-base font-semibold text-bark-800 dark:text-cream-200">Completed / Killed ({{ $completedProjects->count() }})</h3>
                </div>
                <div class="overflow-x-auto">
                    @include('livewire.partials.project-table', ['projects' => $completedProjects])
                </div>
            </div>
        @endif
    @endif
</div>

<!-- Quick Add Modal -->
@if($showQuickAdd)
    <div class="fixed inset-0 bg-gray-900/50 flex items-center justify-center z-50 p-4" wire:click.self="$set('showQuickAdd', false)">
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-xl max-w-lg w-full" @close-modal.window="$wire.showQuickAdd = false" @project-created.window="$wire.$refresh()">
            <div class="px-6 py-4 border-b border-cream-200 dark:border-gray-700 flex justify-between items-center">
                <h3 class="text-lg font-semibold text-bark-800 dark:text-cream-200">Add Project</h3>
                <button wire:click="$set('showQuickAdd', false)" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <livewire:quick-add />
        </div>
    </div>
@endif
