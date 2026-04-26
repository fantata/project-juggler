<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\IssueTask;

class TaskService
{
    public function listAll(bool $includeCompleted = false): array
    {
        $tasksQuery = IssueTask::with(['issue.project'])
            ->whereHas('issue.project', fn ($q) => $q->whereNotIn('status', ['complete', 'killed']));

        if (! $includeCompleted) {
            $tasksQuery->where('is_complete', false);
        }

        $childTasks = $tasksQuery->orderByDesc('created_at')->get();

        $issuesQuery = Issue::with('project')
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', ['complete', 'killed']))
            ->whereDoesntHave('tasks');

        if (! $includeCompleted) {
            $issuesQuery->whereIn('status', ['open', 'in_progress']);
        }

        $standaloneIssues = $issuesQuery->orderByDesc('created_at')->get();

        $items = [];

        foreach ($childTasks as $t) {
            $items[] = [
                'type' => 'task',
                'id' => $t->id,
                'description' => $t->description,
                'is_complete' => (bool) $t->is_complete,
                'project' => $t->issue->project->name,
                'project_id' => $t->issue->project->id,
                'parent_issue' => $t->issue->title,
                'parent_issue_id' => $t->issue->id,
                'urgency' => $t->issue->urgency,
            ];
        }

        foreach ($standaloneIssues as $i) {
            $items[] = [
                'type' => 'issue',
                'id' => $i->id,
                'description' => $i->title,
                'is_complete' => $i->status === 'done',
                'status' => $i->status,
                'project' => $i->project->name,
                'project_id' => $i->project->id,
                'urgency' => $i->urgency,
            ];
        }

        return $items;
    }
}
