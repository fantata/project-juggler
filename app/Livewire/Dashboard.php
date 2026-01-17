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
    public bool $showCompleted = false;

    public bool $showQuickAdd = false;

    public function clearFilters(): void
    {
        $this->filterType = '';
        $this->filterMoneyStatus = '';
    }

    public function render()
    {
        $baseQuery = Project::query()
            ->whereNotIn('status', ['complete', 'killed']);

        if ($this->filterType) {
            $baseQuery->where('type', $this->filterType);
        }

        if ($this->filterMoneyStatus) {
            $baseQuery->where('money_status', $this->filterMoneyStatus);
        }

        // Active projects (ball in your court)
        // Priority: lower number = higher priority, NULL = unprioritized (at bottom)
        $activeProjects = (clone $baseQuery)
            ->where('waiting_on_client', false)
            ->orderByRaw("CASE WHEN priority IS NULL THEN 1 ELSE 0 END")
            ->orderBy('priority', 'asc')
            ->orderByRaw("CASE WHEN money_status = 'awaiting' THEN 0 ELSE 1 END")
            ->orderBy('deadline', 'asc')
            ->orderBy('money_value', 'desc')
            ->get();

        // Waiting projects (ball with client)
        $waitingProjects = (clone $baseQuery)
            ->where('waiting_on_client', true)
            ->orderByRaw("CASE WHEN priority IS NULL THEN 1 ELSE 0 END")
            ->orderBy('priority', 'asc')
            ->orderBy('last_touched_at', 'desc')
            ->get();

        // Completed projects (only loaded when checkbox is checked)
        $completedProjects = collect();
        if ($this->showCompleted) {
            $completedQuery = Project::query()
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
            'waitingProjects' => $waitingProjects,
            'completedProjects' => $completedProjects,
            'types' => ProjectType::cases(),
            'moneyStatuses' => MoneyStatus::cases(),
        ]);
    }
}
