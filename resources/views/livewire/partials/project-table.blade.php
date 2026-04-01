<table class="min-w-full divide-y divide-cream-200 dark:divide-gray-700">
    <thead class="bg-cream-50 dark:bg-gray-700">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-semibold text-bark-600 dark:text-bark-300 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-bark-600 dark:text-bark-300 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-bark-600 dark:text-bark-300 uppercase tracking-wider">Money</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-bark-600 dark:text-bark-300 uppercase tracking-wider">Deadline</th>
            <th class="px-6 py-3 text-left text-xs font-semibold text-bark-600 dark:text-bark-300 uppercase tracking-wider">Next Action</th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-cream-200 dark:divide-gray-700">
        @forelse($projects as $project)
            <tr class="hover:bg-cream-50 dark:hover:bg-gray-700 cursor-pointer" onclick="window.location='{{ route('projects.show', $project) }}'">
                <td class="px-6 py-4 whitespace-nowrap">
                    <a href="{{ route('projects.show', $project) }}" class="text-bark-800 dark:text-cream-200 font-medium hover:text-terracotta-600 dark:hover:text-terracotta-400">
                        {{ $project->name }}
                    </a>
                    @if($project->open_issue_count > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">{{ $project->open_issue_count }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">{{ $project->type->label() }}</td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $moneyColors = [
                            'green' => 'bg-moss-100 text-moss-700 dark:bg-moss-900/30 dark:text-moss-400',
                            'yellow' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
                            'orange' => 'bg-terracotta-100 text-terracotta-700 dark:bg-terracotta-900/30 dark:text-terracotta-400',
                            'gray' => 'bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400',
                            'purple' => 'bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400',
                        ];
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $moneyColors[$project->money_status->color()] }}">
                        {{ $project->money_status->label() }}
                    </span>
                    @if($project->money_value)
                        <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">&pound;{{ number_format($project->money_value, 0) }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    @if($project->deadline)
                        <span class="{{ $project->deadline->isPast() ? 'text-red-500 font-medium' : ($project->deadline->diffInDays(now()) <= 7 ? 'text-amber-600 dark:text-amber-400' : '') }}">
                            {{ $project->deadline->format('j M Y') }}
                        </span>
                    @else
                        <span class="text-gray-300 dark:text-gray-600">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                    {{ $project->next_action ?? '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-400 dark:text-gray-500">
                    No projects
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
