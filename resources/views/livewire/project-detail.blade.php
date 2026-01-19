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
                                <label for="money_value" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Value (£)</label>
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
                                    <label for="retainer_amount" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Retainer Amount (£)</label>
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
