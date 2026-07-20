@if($projects->count() > 0)
<div class="mb-8">
    <h3 class="text-sm font-semibold text-{{ $accent }}-600 dark:text-{{ $accent }}-400 uppercase tracking-wider mb-3 flex items-center gap-2">
        <span class="w-2 h-2 rounded-full bg-{{ $accent }}-500"></span>
        {{ $title }}
        <span class="text-gray-400 dark:text-gray-500 font-normal normal-case tracking-normal">({{ $projects->count() }})</span>
    </h3>

    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($projects as $project)
            @php
                $isPriority = $project->priority && $project->priority <= 2;
            @endphp

            {{-- Card is a plain container; the project name is a "stretched link"
                 that makes the whole card open the detail page, while the board
                 links in the footer sit above it (relative z-10) and stay clickable. --}}
            <div
                class="group relative bg-white dark:bg-gray-800 rounded-xl border border-cream-200 dark:border-gray-700 p-5 hover:shadow-md hover:border-{{ $accent }}-300 dark:hover:border-{{ $accent }}-600 transition-all duration-200 {{ $isPriority ? 'border-l-4 border-l-terracotta-400' : '' }}"
            >
                <!-- Project name + type -->
                <div class="flex items-start justify-between gap-2 mb-3">
                    <h4 class="font-semibold text-bark-800 dark:text-cream-200 leading-tight">
                        <a href="{{ route('projects.detail', $project) }}" wire:navigate
                           class="before:absolute before:inset-0 group-hover:text-terracotta-600 dark:group-hover:text-terracotta-400 transition-colors focus:outline-none focus-visible:ring-2 focus-visible:ring-terracotta-400 rounded">
                            {{ $project->name }}
                        </a>
                    </h4>
                    <span class="text-xs text-gray-400 dark:text-gray-500 shrink-0">{{ $project->type->label() }}</span>
                </div>

                <!-- Next action -->
                @if($project->next_action)
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">{{ $project->next_action }}</p>
                @endif

                <!-- Badges row -->
                <div class="flex flex-wrap items-center gap-2">
                    <!-- Money badge -->
                    @php
                        $moneyBg = match($project->money_status->color()) {
                            'green' => 'bg-moss-100 text-moss-700 dark:bg-moss-900/30 dark:text-moss-400',
                            'yellow' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'orange' => 'bg-terracotta-100 text-terracotta-700 dark:bg-terracotta-900/30 dark:text-terracotta-400',
                            'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                            default => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                        };
                    @endphp
                    <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium rounded-full {{ $moneyBg }}">
                        {{ $project->money_status->label() }}
                        @if($project->money_value)
                            &middot; &pound;{{ number_format($project->money_value, 0) }}
                        @endif
                    </span>

                    <!-- Issue count -->
                    @if($project->open_issue_count > 0)
                        <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium rounded-full bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">
                            {{ $project->open_issue_count }} {{ Str::plural('issue', $project->open_issue_count) }}
                        </span>
                    @endif

                    <!-- Priority -->
                    @if($project->priority)
                        <span class="text-xs text-gray-400 dark:text-gray-500">P{{ $project->priority }}</span>
                    @endif
                </div>

                <!-- Deadline -->
                @if($project->deadline)
                    <div class="mt-3 flex items-center gap-2 text-xs text-gray-400 dark:text-gray-500">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        {{ $project->deadline->format('j M Y') }}
                    </div>
                @endif

                <!-- Retainer indicator -->
                @if($project->is_retainer)
                    <div class="mt-2 text-xs text-moss-500 dark:text-moss-400 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 2a1 1 0 011 1v2.101a7.002 7.002 0 0111.601 2.566 1 1 0 11-1.885.666A5.002 5.002 0 005.999 7H9a1 1 0 010 2H4a1 1 0 01-1-1V3a1 1 0 011-1zm.008 9.057a1 1 0 011.276.61A5.002 5.002 0 0014.001 13H11a1 1 0 110-2h5a1 1 0 011 1v5a1 1 0 11-2 0v-2.101a7.002 7.002 0 01-11.601-2.566 1 1 0 01.61-1.276z" clip-rule="evenodd"/>
                        </svg>
                        {{ $project->retainer_frequency?->label() }}
                        @if($project->retainer_amount)
                            &middot; &pound;{{ number_format($project->retainer_amount, 0) }}
                        @endif
                    </div>
                @endif

                {{-- Board links: sit above the stretched link so they're clickable.
                     Kanban is always available; the client board only shows when a
                     share link is switched on for the project. --}}
                <div class="relative z-10 mt-4 pt-3 border-t border-cream-100 dark:border-gray-700/60 flex items-center gap-4">
                    <a href="{{ route('projects.board', $project) }}" wire:navigate
                       class="inline-flex items-center gap-1.5 text-xs font-medium text-bark-600 dark:text-cream-200 hover:text-terracotta-600 dark:hover:text-terracotta-400 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 016 3.75h2.25A2.25 2.25 0 0110.5 6v12a2.25 2.25 0 01-2.25 2.25H6A2.25 2.25 0 013.75 18V6zM13.5 6a2.25 2.25 0 012.25-2.25H18A2.25 2.25 0 0120.25 6v6a2.25 2.25 0 01-2.25 2.25h-2.25A2.25 2.25 0 0113.5 12V6z"/>
                        </svg>
                        Kanban
                    </a>

                    @if($project->shareUrl())
                        <a href="{{ $project->shareUrl() }}" target="_blank" rel="noopener"
                           class="inline-flex items-center gap-1.5 text-xs font-medium text-bark-600 dark:text-cream-200 hover:text-terracotta-600 dark:hover:text-terracotta-400 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" stroke-width="1.5">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 011.242 7.244l-4.5 4.5a4.5 4.5 0 01-6.364-6.364l1.757-1.757m13.35-.622l1.757-1.757a4.5 4.5 0 00-6.364-6.364l-4.5 4.5a4.5 4.5 0 001.242 7.244"/>
                            </svg>
                            Client board
                        </a>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
</div>
@endif
