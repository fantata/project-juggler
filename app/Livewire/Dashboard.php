<?php

namespace App\Livewire;

use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Models\Project;
use Livewire\Attributes\Url;
use Livewire\Component;

class Dashboard extends Component
{
    #[Url]
    public string $filterType = '';

    #[Url]
    public string $filterMoneyStatus = '';

    #[Url]
    public string $viewMode = 'tiles';

    #[Url]
    public bool $showCompleted = false;

    public bool $showQuickAdd = false;

    public function clearFilters(): void
    {
        $this->filterType = '';
        $this->filterMoneyStatus = '';
    }

    public function render()
    {
        $issueCountQuery = ['issues as open_issue_count' => function ($q) {
            $q->whereIn('status', ['open', 'in_progress']);
        }];

        $baseQuery = Project::query()
            ->withCount($issueCountQuery)
            ->active();

        if ($this->filterType) {
            $baseQuery->where('type', $this->filterType);
        }

        if ($this->filterMoneyStatus) {
            $baseQuery->where('money_status', $this->filterMoneyStatus);
        }

        // Shared sorting: awaiting money first, then projects with open issues,
        // then by priority (nulls last)
        $commonSort = fn($q) => $q
            ->orderByRaw("CASE WHEN money_status = 'awaiting' THEN 0 ELSE 1 END")
            ->orderByDesc('open_issue_count')
            ->orderByRaw("CASE WHEN priority IS NULL THEN 1 ELSE 0 END")
            ->orderBy('priority', 'asc');

        $activeProjects = $commonSort((clone $baseQuery)
            ->where('waiting_on_client', false)
            ->where('is_retainer', false))
            ->orderBy('deadline', 'asc')
            ->orderBy('money_value', 'desc')
            ->get();

        $retainerProjects = $commonSort((clone $baseQuery)
            ->where('is_retainer', true))
            ->orderBy('name', 'asc')
            ->get();

        $waitingProjects = $commonSort((clone $baseQuery)
            ->where('waiting_on_client', true)
            ->where('is_retainer', false))
            ->orderBy('last_touched_at', 'desc')
            ->get();

        $completedProjects = collect();
        if ($this->showCompleted) {
            $completedQuery = Project::query()
                ->withCount($issueCountQuery)
                ->whereIn('status', ['complete', 'killed']);

            if ($this->filterType) {
                $completedQuery->where('type', $this->filterType);
            }
            if ($this->filterMoneyStatus) {
                $completedQuery->where('money_status', $this->filterMoneyStatus);
            }

            $completedProjects = $completedQuery
                ->orderBy('updated_at', 'desc')
                ->get();
        }

        return view('livewire.dashboard', [
            'activeProjects' => $activeProjects,
            'retainerProjects' => $retainerProjects,
            'waitingProjects' => $waitingProjects,
            'completedProjects' => $completedProjects,
            'types' => ProjectType::cases(),
            'moneyStatuses' => MoneyStatus::cases(),
        ]);
    }
}
