<x-slot name="header">
    <h2 class="font-semibold text-xl text-bark-800 dark:text-cream-200 leading-tight">Calendar Feeds</h2>
</x-slot>

<div class="max-w-4xl mx-auto space-y-6">
    <!-- Add Feed Button -->
    <div class="flex justify-end">
        <button
            wire:click="$toggle('showAddFeed')"
            class="inline-flex items-center gap-2 px-4 py-2 bg-moss-500 text-white text-sm font-medium rounded-lg hover:bg-moss-600 transition-colors"
        >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="2">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
            </svg>
            Add Feed
        </button>
    </div>
    <!-- Add Feed Form -->
    @if($showAddFeed)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 p-6">
            <h3 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-4">Subscribe to a calendar</h3>
            <form wire:submit="addFeed" class="space-y-4">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label for="newFeedName" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Name</label>
                        <input type="text" wire:model="newFeedName" id="newFeedName" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-moss-400 focus:ring-moss-400 sm:text-sm" placeholder="Dogface Improv">
                        @error('newFeedName') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                    </div>
                    <div class="flex gap-3">
                        <div class="flex-1">
                            <label for="newFeedUrl" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">ICS URL</label>
                            <input type="url" wire:model="newFeedUrl" id="newFeedUrl" class="block w-full rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 shadow-sm focus:border-moss-400 focus:ring-moss-400 sm:text-sm" placeholder="https://...">
                            @error('newFeedUrl') <span class="text-red-500 text-xs">{{ $message }}</span> @enderror
                        </div>
                        <div class="w-16">
                            <label for="newFeedColor" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Colour</label>
                            <input type="color" wire:model="newFeedColor" id="newFeedColor" class="block w-full h-[38px] rounded-lg border-gray-300 dark:border-gray-600 cursor-pointer">
                        </div>
                    </div>
                </div>
                <div class="flex justify-end gap-3">
                    <button type="button" wire:click="$set('showAddFeed', false)" class="px-4 py-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-gray-200">Cancel</button>
                    <button type="submit" class="px-5 py-2 text-sm font-medium text-white bg-moss-500 rounded-lg hover:bg-moss-600 transition-colors">
                        Subscribe
                    </button>
                </div>
            </form>
        </div>
    @endif

    <!-- Feed List -->
    @forelse($feeds as $feed)
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 overflow-hidden">
            <div class="p-5 flex items-center gap-4">
                <span class="w-4 h-4 rounded-full shrink-0" style="background-color: {{ $feed->color ?? '#9CA3AF' }}"></span>
                <div class="flex-1 min-w-0">
                    <h3 class="font-medium text-bark-800 dark:text-cream-200">{{ $feed->name }}</h3>
                    <p class="text-xs text-gray-500 dark:text-gray-400 truncate">{{ $feed->url }}</p>
                </div>
                <div class="flex items-center gap-3 shrink-0">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        {{ $feed->events_count }} events
                    </span>
                    @if($feed->last_sync_status === 'success')
                        <span class="w-2 h-2 rounded-full bg-moss-500" title="Synced {{ $feed->last_synced_at->diffForHumans() }}"></span>
                    @elseif($feed->last_sync_status === 'error')
                        <span class="w-2 h-2 rounded-full bg-red-500" title="{{ $feed->last_sync_error }}"></span>
                    @endif
                    <button wire:click="syncFeed({{ $feed->id }})" class="text-xs text-moss-600 dark:text-moss-400 hover:text-moss-800 font-medium">
                        Sync
                    </button>
                    <button wire:click="showRules({{ $feed->id }})" class="text-xs text-bark-600 dark:text-bark-400 hover:text-bark-800 font-medium">
                        Rules ({{ $feed->rules->count() }})
                    </button>
                    <button wire:click="toggleFeed({{ $feed->id }})" class="text-xs {{ $feed->is_enabled ? 'text-gray-500' : 'text-red-500' }} hover:text-gray-700">
                        {{ $feed->is_enabled ? 'Disable' : 'Enable' }}
                    </button>
                    <button wire:click="deleteFeed({{ $feed->id }})" wire:confirm="Delete this feed and all its events?" class="text-xs text-red-400 hover:text-red-600">
                        Delete
                    </button>
                </div>
            </div>

            <!-- Rules Panel -->
            @if($rulesFeedId === $feed->id && $showRuleForm)
                <div class="border-t border-cream-200 dark:border-gray-700 p-5 bg-cream-50 dark:bg-gray-750">
                    <h4 class="text-sm font-semibold text-bark-700 dark:text-cream-200 mb-3">Rules for {{ $feed->name }}</h4>

                    <!-- Existing Rules -->
                    @if($feed->rules->count() > 0)
                        <div class="space-y-2 mb-4">
                            @foreach($feed->rules as $rule)
                                <div class="flex items-center gap-2 text-sm bg-white dark:bg-gray-800 rounded-lg px-3 py-2 border border-cream-200 dark:border-gray-600">
                                    <span class="text-gray-600 dark:text-gray-300">
                                        If <strong>{{ $rule->field->value }}</strong>
                                        {{ $rule->operator->label() }}
                                        "<strong>{{ $rule->value }}</strong>"
                                        then <strong class="{{ $rule->action === \App\Enums\FeedRuleAction::MarkRelevant ? 'text-terracotta-600' : ($rule->action === \App\Enums\FeedRuleAction::Background ? 'text-gray-400' : 'text-moss-600') }}">{{ $rule->action->label() }}</strong>
                                        @if($rule->action_value)
                                            ({{ $rule->action_value }})
                                        @endif
                                    </span>
                                    <button wire:click="deleteRule({{ $rule->id }})" wire:confirm="Delete this rule?" class="ml-auto text-red-400 hover:text-red-600 shrink-0">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <!-- Add Rule -->
                    <form wire:submit="addRule" class="flex flex-wrap items-end gap-2">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Field</label>
                            <select wire:model="ruleField" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5">
                                <option value="title">Title</option>
                                <option value="description">Description</option>
                                <option value="location">Location</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Operator</label>
                            <select wire:model="ruleOperator" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5">
                                <option value="contains">Contains</option>
                                <option value="starts_with">Starts with</option>
                                <option value="matches_regex">Regex</option>
                            </select>
                        </div>
                        <div class="flex-1 min-w-[120px]">
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Value</label>
                            <input type="text" wire:model="ruleValue" class="w-full text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5" placeholder="Chris">
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-gray-400 mb-1">Action</label>
                            <select wire:model="ruleAction" class="text-sm rounded-lg border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-200 py-1.5">
                                <option value="mark_relevant">Mark relevant</option>
                                <option value="background">Background</option>
                                <option value="set_note">Set note</option>
                            </select>
                        </div>
                        <button type="submit" class="px-3 py-1.5 text-sm font-medium text-white bg-moss-500 rounded-lg hover:bg-moss-600">Add</button>
                    </form>
                </div>
            @endif
        </div>
    @empty
        <div class="bg-white dark:bg-gray-800 rounded-xl shadow-sm border border-cream-200 dark:border-gray-700 p-12 text-center">
            <svg class="w-12 h-12 mx-auto text-bark-300 dark:text-gray-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12.75 19.5v-.75a7.5 7.5 0 00-7.5-7.5H4.5m0-6.75h.75c7.87 0 14.25 6.38 14.25 14.25v.75M6 18.75a.75.75 0 11-1.5 0 .75.75 0 011.5 0z"/>
            </svg>
            <p class="text-gray-500 dark:text-gray-400 mb-2">No calendar feeds yet</p>
            <p class="text-sm text-gray-400 dark:text-gray-500">Subscribe to external calendars to see them overlaid on yours.</p>
        </div>
    @endforelse
</div>
