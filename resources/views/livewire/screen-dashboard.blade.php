<div wire:poll.30s class="p-4 max-w-full">
    {{-- Header --}}
    <div class="flex justify-between items-center mb-4">
        <h1 class="text-2xl font-bold text-gray-100">TODAY</h1>
        <div class="flex items-center gap-4">
            <span class="text-gray-400 text-sm">{{ now()->format('l, j F Y') }}</span>
            <a href="{{ route('dashboard') }}" class="text-xs text-gray-500 hover:text-gray-300 underline">Main Dashboard</a>
        </div>
    </div>

    {{-- Top Row: Priorities + Calendar --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        {{-- Priorities --}}
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Priorities</h2>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($priorities as $issue)
                    <div class="flex items-center gap-2 text-sm">
                        @php
                            $urgencyColors = [
                                'high' => 'text-red-400',
                                'medium' => 'text-yellow-400',
                                'low' => 'text-gray-500',
                            ];
                            $statusIcons = [
                                'in_progress' => 'text-blue-400',
                                'open' => 'text-gray-500',
                            ];
                        @endphp
                        <span class="w-2 h-2 rounded-full {{ $issue->status->value === 'in_progress' ? 'bg-blue-400' : 'bg-gray-600' }} shrink-0"></span>
                        <span class="{{ $urgencyColors[$issue->urgency->value] ?? 'text-gray-400' }} shrink-0 text-xs font-medium w-6">
                            {{ strtoupper(substr($issue->urgency->value, 0, 1)) }}
                        </span>
                        <span class="text-gray-200 truncate flex-1">{{ $issue->title }}</span>
                        <span class="text-gray-600 text-xs shrink-0">{{ $issue->project->name }}</span>
                    </div>
                @empty
                    <p class="text-gray-600 text-sm italic">No open tasks</p>
                @endforelse
            </div>
        </div>

        {{-- Calendar --}}
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Calendar — Next 3 Days</h2>
            <div class="space-y-2 max-h-80 overflow-y-auto">
                @forelse($calendarEvents as $event)
                    <div class="flex items-center gap-2 text-sm">
                        @php
                            $sourceColors = [
                                'family' => 'bg-pink-500',
                                'work' => 'bg-blue-500',
                                'dogface' => 'bg-orange-500',
                                'podcast' => 'bg-purple-500',
                                'local' => 'bg-blue-500',
                            ];
                            $dotColor = $sourceColors[$event->source] ?? 'bg-gray-500';
                        @endphp
                        <span class="w-2 h-2 rounded-full {{ $dotColor }} shrink-0"></span>
                        <span class="text-gray-500 text-xs w-16 shrink-0">
                            @if($event->all_day)
                                All day
                            @else
                                {{ $event->start->format('H:i') }}
                            @endif
                        </span>
                        <span class="text-gray-200 truncate flex-1">{{ $event->title }}</span>
                        @if($event->start->isToday())
                            <span class="text-xs text-green-500 shrink-0">Today</span>
                        @elseif($event->start->isTomorrow())
                            <span class="text-xs text-gray-500 shrink-0">Tomorrow</span>
                        @else
                            <span class="text-xs text-gray-600 shrink-0">{{ $event->start->format('D') }}</span>
                        @endif
                    </div>
                @empty
                    <p class="text-gray-600 text-sm italic">No upcoming events</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Middle Row: Active Projects + Money --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 mb-4">
        {{-- Active Projects --}}
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Active Projects</h2>
            <div class="space-y-2 max-h-60 overflow-y-auto">
                @forelse($activeProjects as $project)
                    <div class="flex items-center justify-between text-sm">
                        <div class="flex items-center gap-2 min-w-0 flex-1">
                            @php
                                $categoryIcons = [
                                    'consultancy' => 'text-blue-400',
                                    'podcast' => 'text-purple-400',
                                    'creative' => 'text-yellow-400',
                                    'event' => 'text-green-400',
                                    'generic' => 'text-gray-400',
                                ];
                            @endphp
                            <span class="w-2 h-2 rounded-full {{ $project->waiting_on_client ? 'bg-yellow-500' : ($project->status->value === 'blocked' ? 'bg-red-500' : 'bg-green-500') }} shrink-0"></span>
                            <span class="text-gray-200 truncate">{{ $project->name }}</span>
                        </div>
                        <div class="flex items-center gap-2 shrink-0">
                            @if($project->open_issue_count > 0)
                                <span class="text-xs text-orange-400">{{ $project->open_issue_count }} open</span>
                            @endif
                            @if($project->waiting_on_client)
                                <span class="text-xs text-yellow-500">waiting</span>
                            @endif
                            @if($project->next_action)
                                <span class="text-xs text-gray-500 max-w-32 truncate" title="{{ $project->next_action }}">{{ $project->next_action }}</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <p class="text-gray-600 text-sm italic">No active projects</p>
                @endforelse
            </div>
        </div>

        {{-- Money --}}
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Money</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-baseline">
                    <span class="text-gray-400 text-sm">Awaiting</span>
                    <span class="text-xl font-bold {{ $moneyStatus['awaiting_total'] > 0 ? 'text-orange-400' : 'text-gray-600' }}">
                        &pound;{{ number_format($moneyStatus['awaiting_total'], 0) }}
                    </span>
                </div>
                <div class="flex justify-between items-baseline">
                    <span class="text-gray-400 text-sm">Overdue</span>
                    <span class="text-lg font-semibold {{ $moneyStatus['overdue_total'] > 0 ? 'text-red-400' : 'text-gray-600' }}">
                        &pound;{{ number_format($moneyStatus['overdue_total'], 0) }}
                    </span>
                </div>
                @if($moneyStatus['retainers_due'] > 0)
                    <div class="flex justify-between items-baseline">
                        <span class="text-gray-400 text-sm">Retainers due</span>
                        <span class="text-lg font-semibold text-yellow-400">{{ $moneyStatus['retainers_due'] }}</span>
                    </div>
                @endif
                @if($moneyStatus['awaiting_projects']->count() > 0)
                    <div class="border-t border-gray-800 pt-2 mt-2 space-y-1">
                        @foreach($moneyStatus['awaiting_projects'] as $p)
                            <div class="flex justify-between text-xs">
                                <span class="text-gray-500 truncate">{{ $p->name }}</span>
                                <span class="text-orange-400 shrink-0">&pound;{{ number_format($p->money_value, 0) }}</span>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Deadlines --}}
    @if($deadlines->count() > 0)
        <div class="bg-gray-900 rounded-lg border border-gray-800 p-4 mb-4">
            <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Upcoming Deadlines</h2>
            <div class="flex flex-wrap gap-4">
                @foreach($deadlines as $project)
                    <div class="flex items-center gap-2 text-sm">
                        <span class="{{ $project->deadline->isPast() ? 'text-red-400' : ($project->deadline->isToday() ? 'text-orange-400' : 'text-gray-400') }} text-xs">
                            {{ $project->deadline->format('j M') }}
                        </span>
                        <span class="text-gray-200">{{ $project->name }}</span>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    {{-- Quick Capture --}}
    <div class="bg-gray-900 rounded-lg border border-gray-800 p-4">
        <h2 class="text-sm font-semibold text-gray-400 uppercase tracking-wider mb-3">Quick Capture</h2>
        <form wire:submit="addQuickCapture" class="flex gap-2">
            <input
                type="text"
                wire:model="quickCapture"
                placeholder="Capture a thought or task..."
                class="flex-1 bg-gray-800 border-gray-700 text-gray-200 rounded-md text-sm placeholder-gray-600 focus:border-indigo-500 focus:ring-indigo-500"
            >
            <button
                type="submit"
                class="px-4 py-2 bg-indigo-600 text-white text-sm font-medium rounded-md hover:bg-indigo-500"
            >
                +
            </button>
        </form>
    </div>
</div>
