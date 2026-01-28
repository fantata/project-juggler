<table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
    <thead class="bg-gray-50 dark:bg-gray-700">
        <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Name</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Type</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Money</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Deadline</th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Next Action</th>
        </tr>
    </thead>
    <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
        @forelse($projects as $project)
            <tr class="hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer" onclick="window.location='{{ route('projects.show', $project) }}'">
                <td class="px-6 py-4 whitespace-nowrap">
                    <a href="{{ route('projects.show', $project) }}" class="text-gray-900 dark:text-gray-100 font-medium hover:text-gray-600 dark:hover:text-gray-300">
                        {{ $project->name }}
                    </a>
                    @if($project->priority > 0)
                        <span class="ml-2 px-1.5 py-0.5 text-xs bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300 rounded">P{{ $project->priority }}</span>
                    @endif
                    @if($project->open_issue_count > 0)
                        <span class="ml-1 px-1.5 py-0.5 text-xs bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300 rounded-full">{{ $project->open_issue_count }} {{ Str::plural('issue', $project->open_issue_count) }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    <span class="text-sm text-gray-600 dark:text-gray-400">{{ $project->type->label() }}</span>
                </td>
                <td class="px-6 py-4 whitespace-nowrap">
                    @php
                        $moneyColors = [
                            'green' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                            'yellow' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                            'orange' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                            'gray' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                            'purple' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                        ];
                    @endphp
                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full {{ $moneyColors[$project->money_status->color()] }}">
                        {{ $project->money_status->label() }}
                    </span>
                    @if($project->money_value)
                        <span class="ml-1 text-sm text-gray-600 dark:text-gray-400">£{{ number_format($project->money_value, 0) }}</span>
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600 dark:text-gray-400">
                    @if($project->deadline)
                        <span class="{{ $project->deadline->isPast() ? 'text-red-600 dark:text-red-400 font-medium' : ($project->deadline->diffInDays(now()) <= 7 ? 'text-orange-600 dark:text-orange-400' : '') }}">
                            {{ $project->deadline->format('j M Y') }}
                        </span>
                    @else
                        <span class="text-gray-400 dark:text-gray-500">-</span>
                    @endif
                </td>
                <td class="px-6 py-4 text-sm text-gray-600 dark:text-gray-400 max-w-xs truncate">
                    {{ $project->next_action ?? '-' }}
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5" class="px-6 py-8 text-center text-gray-500 dark:text-gray-400">
                    No projects
                </td>
            </tr>
        @endforelse
    </tbody>
</table>
