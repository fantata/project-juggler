<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Issue;
use App\Models\IssueTask;
use App\Models\Project;
use Carbon\Carbon;

class StatusController extends Controller
{
    public function __invoke()
    {
        $activeProjects = Project::whereNotIn('status', ['complete', 'killed']);

        $upcomingDeadlines = Project::whereNotNull('deadline')
            ->where('deadline', '>=', Carbon::today())
            ->where('deadline', '<=', Carbon::today()->addDays(14))
            ->whereNotIn('status', ['complete', 'killed'])
            ->orderBy('deadline')
            ->get(['id', 'name', 'deadline', 'status']);

        $openIssues = Issue::whereIn('status', ['open', 'in_progress'])
            ->whereHas('project', fn ($q) => $q->whereNotIn('status', ['complete', 'killed']))
            ->count();

        $incompleteTasks = IssueTask::where('is_complete', false)
            ->whereHas('issue.project', fn ($q) => $q->whereNotIn('status', ['complete', 'killed']))
            ->count();

        $awaitingMoney = Project::where('money_status', 'awaiting')
            ->whereNotIn('status', ['complete', 'killed'])
            ->get(['id', 'name', 'money_value']);

        return response()->json([
            'active_projects' => $activeProjects->count(),
            'blocked_projects' => (clone $activeProjects)->where('status', 'blocked')->count(),
            'waiting_on_client' => (clone $activeProjects)->where('waiting_on_client', true)->count(),
            'open_issues' => $openIssues,
            'incomplete_tasks' => $incompleteTasks,
            'upcoming_deadlines' => $upcomingDeadlines,
            'awaiting_money' => $awaitingMoney,
            'total_awaiting_value' => $awaitingMoney->sum('money_value'),
        ]);
    }
}
