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
        $activeProjectFilter = function ($q) {
            $q->whereNotIn('status', ['complete', 'killed']);
        };

        // Child tasks from issues that have sub-tasks (existing behaviour)
        $taskQuery = IssueTask::with(['issue.project'])
            ->whereHas('issue.project', $activeProjectFilter);

        if (!$this->showCompleted) {
            $taskQuery->where('is_complete', false);
        }

        $childTasks = $taskQuery->orderBy('is_complete')->orderBy('created_at', 'desc')->get();

        // Standalone issues (no child tasks) - these are themselves the work item
        $issueQuery = Issue::with('project')
            ->doesntHave('tasks')
            ->whereHas('project', $activeProjectFilter);

        if (!$this->showCompleted) {
            $issueQuery->whereIn('status', ['open', 'in_progress']);
        }

        $standaloneIssues = $issueQuery->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
            ->orderBy('created_at', 'desc')
            ->get();

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

        $grouped = $workItems->groupBy('project_name');

        // Counts include both types
        $totalChildTasks = IssueTask::whereHas('issue.project', $activeProjectFilter)->count();
        $completedChildTasks = IssueTask::whereHas('issue.project', $activeProjectFilter)->where('is_complete', true)->count();

        $totalStandalone = Issue::doesntHave('tasks')->whereHas('project', $activeProjectFilter)->count();
        $completedStandalone = Issue::doesntHave('tasks')->whereHas('project', $activeProjectFilter)->where('status', 'done')->count();

        $totalTasks = $totalChildTasks + $totalStandalone;
        $completedTasks = $completedChildTasks + $completedStandalone;

        return view('livewire.my-tasks', [
            'tasksByProject' => $grouped,
            'totalTasks' => $totalTasks,
            'completedTasks' => $completedTasks,
        ]);
    }
}
