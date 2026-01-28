<?php

namespace App\Livewire;

use App\Models\IssueTask;
use Livewire\Attributes\Url;
use Livewire\Component;

class MyTasks extends Component
{
    #[Url]
    public bool $showCompleted = false;

    public function toggleTask(int $taskId): void
    {
        $task = IssueTask::findOrFail($taskId);
        $task->update(['is_complete' => !$task->is_complete]);
        $task->issue->project->update(['last_touched_at' => now()]);
    }

    public function deleteTask(int $taskId): void
    {
        $task = IssueTask::findOrFail($taskId);
        $task->delete();
    }

    public function render()
    {
        $query = IssueTask::with(['issue.project'])
            ->whereHas('issue.project', function ($q) {
                $q->whereNotIn('status', ['complete', 'killed']);
            });

        if (!$this->showCompleted) {
            $query->where('is_complete', false);
        }

        $tasks = $query
            ->orderBy('is_complete')
            ->orderBy('created_at', 'desc')
            ->get()
            ->groupBy(fn($task) => $task->issue->project->name);

        $totalTasks = IssueTask::whereHas('issue.project', function ($q) {
            $q->whereNotIn('status', ['complete', 'killed']);
        })->count();

        $completedTasks = IssueTask::whereHas('issue.project', function ($q) {
            $q->whereNotIn('status', ['complete', 'killed']);
        })->where('is_complete', true)->count();

        return view('livewire.my-tasks', [
            'tasksByProject' => $tasks,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
        ]);
    }
}
