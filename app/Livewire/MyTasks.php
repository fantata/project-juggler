<?php

namespace App\Livewire;

use App\Models\Issue;
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

    public function toggleIssue(int $issueId): void
    {
        $issue = Issue::findOrFail($issueId);
        $newStatus = $issue->status->value === 'done' ? 'open' : 'done';
        $issue->update(['status' => $newStatus]);
        $issue->project->update(['last_touched_at' => now()]);
    }

    public function deleteIssue(int $issueId): void
    {
        $issue = Issue::findOrFail($issueId);
        $issue->delete();
    }

    public function render()
    {
        // Child tasks from issues that have sub-tasks
        // Always load all (including completed) so we can compute totals from the collection
        $allChildTasks = IssueTask::with(['issue.project'])
            ->whereHas('issue.project', fn($q) => $q->active())
            ->orderBy('is_complete')
            ->orderBy('created_at', 'desc')
            ->get();

        // Standalone issues (no child tasks)
        $allStandaloneIssues = Issue::with('project')
            ->doesntHave('tasks')
            ->whereHas('project', fn($q) => $q->active())
            ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderBy('created_at', 'desc')
            ->get();

        // Compute totals from loaded collections (no extra queries)
        $totalTasks = $allChildTasks->count() + $allStandaloneIssues->count();
        $completedTasks = $allChildTasks->where('is_complete', true)->count()
            + $allStandaloneIssues->where('status.value', 'done')->count();

        // Filter for display if not showing completed
        $childTasks = $this->showCompleted
            ? $allChildTasks
            : $allChildTasks->where('is_complete', false);

        $standaloneIssues = $this->showCompleted
            ? $allStandaloneIssues
            : $allStandaloneIssues->whereIn('status.value', ['open', 'in_progress']);

        // Normalize into a unified collection
        $workItems = collect();

        foreach ($childTasks as $task) {
            $workItems->push((object) [
                'type' => 'task',
                'id' => $task->id,
                'description' => $task->description,
                'is_complete' => $task->is_complete,
                'project_name' => $task->issue->project->name,
                'project_id' => $task->issue->project->id,
                'parent_title' => $task->issue->title,
                'urgency' => $task->issue->urgency->value,
                'is_ai_generated' => $task->is_ai_generated,
            ]);
        }

        foreach ($standaloneIssues as $issue) {
            $workItems->push((object) [
                'type' => 'issue',
                'id' => $issue->id,
                'description' => $issue->title,
                'is_complete' => $issue->status->value === 'done',
                'project_name' => $issue->project->name,
                'project_id' => $issue->project->id,
                'parent_title' => null,
                'urgency' => $issue->urgency->value,
                'is_ai_generated' => false,
            ]);
        }

        return view('livewire.my-tasks', [
            'tasksByProject' => $workItems->groupBy('project_name'),
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
        ]);
    }
}
