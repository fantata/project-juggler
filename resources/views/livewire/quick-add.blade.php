<form wire:submit="save" class="p-6 space-y-4">
    <div>
        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-gray-300">Name *</label>
        <input type="text" wire:model="name" id="name" class="mt-1 block w-full rounded-md border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm" autofocus>
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

    <div class="flex justify-end gap-3 pt-4 border-t border-gray-200 dark:border-gray-700">
        <button type="button" wire:click="$dispatch('close-modal')" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-white dark:bg-gray-700 border border-gray-300 dark:border-gray-600 rounded-md hover:bg-gray-50 dark:hover:bg-gray-600">
            Cancel
        </button>
        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-gray-800 dark:bg-gray-200 dark:text-gray-800 border border-transparent rounded-md hover:bg-gray-700 dark:hover:bg-white">
            Create Project
        </button>
    </div>
</form>
