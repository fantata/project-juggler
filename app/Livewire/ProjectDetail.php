<?php

namespace App\Livewire;

use App\Enums\IssueStatus;
use App\Enums\IssueUrgency;
use App\Enums\MoneyStatus;
use App\Enums\ProjectStatus;
use App\Enums\ProjectType;
use App\Enums\RetainerFrequency;
use App\Models\Project;
use App\Services\GitHubService;
use Livewire\Attributes\Validate;
use Livewire\Component;

class ProjectDetail extends Component
{
    public Project $project;

    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required')]
    public string $type = '';

    #[Validate('required')]
    public string $status = '';

    #[Validate('required')]
    public string $money_status = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $money_value = null;

    #[Validate('nullable|date')]
    public ?string $deadline = null;

    #[Validate('nullable|string|max:255')]
    public ?string $next_action = null;

    #[Validate('nullable|string')]
    public ?string $notes = null;

    #[Validate('nullable|string|max:255')]
    public ?string $github_repo = null;

    public bool $is_retainer = false;

    public ?string $retainer_frequency = null;

    #[Validate('nullable|numeric|min:0')]
    public ?string $retainer_amount = null;

    #[Validate('required|string|min:1')]
    public string $newLogEntry = '';

    #[Validate('nullable|numeric|min:0')]
    public ?string $newLogHours = null;

    // Issue form
    public bool $showIssueForm = false;
    public string $newIssueTitle = '';
    public string $newIssueDescription = '';
    public string $newIssueUrgency = 'medium';

    public function mount(Project $project): void
    {
        $this->project = $project;
        $this->name = $project->name;
        $this->type = $project->type->value;
        $this->status = $project->status->value;
        $this->money_status = $project->money_status->value;
        $this->money_value = $project->money_value;
        $this->deadline = $project->deadline?->format('Y-m-d');
        $this->next_action = $project->next_action;
        $this->notes = $project->notes;
        $this->github_repo = $project->github_repo;
        $this->is_retainer = $project->is_retainer;
        $this->retainer_frequency = $project->retainer_frequency?->value;
        $this->retainer_amount = $project->retainer_amount;
    }

    public function save(): void
    {
        $this->validate([
            'name' => 'required|string|max:255',
            'type' => 'required',
            'status' => 'required',
            'money_status' => 'required',
            'money_value' => 'nullable|numeric|min:0',
            'deadline' => 'nullable|date',
            'next_action' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
            'github_repo' => 'nullable|string|max:255',
        ]);

        $this->project->update([
            'name' => $this->name,
            'type' => $this->type,
            'status' => $this->status,
            'money_status' => $this->money_status,
            'money_value' => $this->money_value ?: null,
            'deadline' => $this->deadline ?: null,
            'next_action' => $this->next_action ?: null,
            'notes' => $this->notes ?: null,
            'github_repo' => $this->github_repo ?: null,
            'is_retainer' => $this->is_retainer,
            'retainer_frequency' => $this->retainer_frequency ?: null,
            'retainer_amount' => $this->retainer_amount ?: null,
            'last_touched_at' => now(),
        ]);

        session()->flash('message', 'Project updated.');
    }

    public function addLog(): void
    {
        $this->validate([
            'newLogEntry' => 'required|string|min:1',
            'newLogHours' => 'nullable|numeric|min:0',
        ]);

        $this->project->logs()->create([
            'entry' => $this->newLogEntry,
            'hours' => $this->newLogHours ?: null,
        ]);

        $this->project->update(['last_touched_at' => now()]);
        $this->project->refresh();

        $this->newLogEntry = '';
        $this->newLogHours = null;
        session()->flash('log-message', 'Log entry added.');
    }

    public function createIssue(): void
    {
        $this->validate([
            'newIssueTitle' => 'required|string|max:255',
            'newIssueDescription' => 'nullable|string',
            'newIssueUrgency' => 'required|in:low,medium,high',
        ]);

        $issue = $this->project->issues()->create([
            'title' => $this->newIssueTitle,
            'description' => $this->newIssueDescription ?: null,
            'urgency' => $this->newIssueUrgency,
        ]);

        // Push to GitHub if configured
        if ($this->project->github_repo && GitHubService::isConfigured()) {
            try {
                $ghIssue = GitHubService::createIssue(
                    $this->project->github_repo,
                    $this->newIssueTitle,
                    $this->newIssueDescription ?: null
                );
                if (!empty($ghIssue['number'])) {
                    $issue->update(['github_issue_number' => $ghIssue['number']]);
                }
            } catch (\Exception $e) {
                // GitHub push failed silently — issue still created locally
            }
        }

        $this->project->update(['last_touched_at' => now()]);
        $this->project->refresh();

        $this->newIssueTitle = '';
        $this->newIssueDescription = '';
        $this->newIssueUrgency = 'medium';
        $this->showIssueForm = false;

        session()->flash('issue-message', 'Issue created.');
    }

    public function updateIssueStatus(int $issueId, string $status): void
    {
        $issue = $this->project->issues()->findOrFail($issueId);
        $oldStatus = $issue->status->value;
        $issue->update(['status' => $status]);

        // Push status change to GitHub
        if ($issue->github_issue_number && $this->project->github_repo && GitHubService::isConfigured()) {
            try {
                if ($status === 'done' && $oldStatus !== 'done') {
                    GitHubService::closeIssue($this->project->github_repo, $issue->github_issue_number);
                } elseif ($status !== 'done' && $oldStatus === 'done') {
                    GitHubService::reopenIssue($this->project->github_repo, $issue->github_issue_number);
                }
            } catch (\Exception $e) {
                // GitHub sync failed silently
            }
        }

        $this->project->refresh();
    }

    public function syncGitHubIssues(): void
    {
        if (!$this->project->github_repo || !GitHubService::isConfigured()) {
            return;
        }

        try {
            $ghIssues = GitHubService::listIssues($this->project->github_repo);
        } catch (\Exception $e) {
            session()->flash('issue-message', 'Failed to sync from GitHub: ' . $e->getMessage());
            return;
        }

        $created = 0;
        $updated = 0;

        foreach ($ghIssues as $ghIssue) {
            $number = $ghIssue['number'];
            $existing = $this->project->issues()
                ->where('github_issue_number', $number)
                ->first();

            $ghStatus = $ghIssue['state'] === 'closed' ? 'done' : 'open';

            if ($existing) {
                $updates = ['title' => $ghIssue['title']];
                // Only update status if it changed, and preserve in_progress
                if ($ghStatus === 'done' && $existing->status->value !== 'done') {
                    $updates['status'] = 'done';
                    $updated++;
                } elseif ($ghStatus === 'open' && $existing->status->value === 'done') {
                    $updates['status'] = 'open';
                    $updated++;
                }
                if ($existing->title !== $ghIssue['title']) {
                    $updated++;
                }
                $existing->update($updates);
            } else {
                $this->project->issues()->create([
                    'title' => $ghIssue['title'],
                    'description' => $ghIssue['body'] ?? null,
                    'status' => $ghStatus,
                    'urgency' => 'medium',
                    'github_issue_number' => $number,
                ]);
                $created++;
            }
        }

        $this->project->refresh();
        session()->flash('issue-message', "Synced from GitHub: {$created} created, {$updated} updated.");
    }

    public function addTask(int $issueId, string $description): void
    {
        if (empty(trim($description))) {
            return;
        }

        $issue = $this->project->issues()->findOrFail($issueId);
        $maxPosition = $issue->tasks()->max('position') ?? 0;

        $issue->tasks()->create([
            'description' => trim($description),
            'position' => $maxPosition + 1,
        ]);

        $this->project->update(['last_touched_at' => now()]);
        $this->project->refresh();
    }

    public function toggleTask(int $taskId): void
    {
        $task = \App\Models\IssueTask::whereHas('issue', fn($q) => $q->where('project_id', $this->project->id))
            ->findOrFail($taskId);

        $task->update(['is_complete' => !$task->is_complete]);
        $this->project->refresh();
    }

    public function deleteTask(int $taskId): void
    {
        $task = \App\Models\IssueTask::whereHas('issue', fn($q) => $q->where('project_id', $this->project->id))
            ->findOrFail($taskId);

        $task->delete();
        $this->project->refresh();
    }

    public function delete(): void
    {
        $this->project->delete();
        $this->redirect(route('dashboard'), navigate: true);
    }

    public function render()
    {
        return view('livewire.project-detail', [
            'types' => ProjectType::cases(),
            'statuses' => ProjectStatus::cases(),
            'moneyStatuses' => MoneyStatus::cases(),
            'retainerFrequencies' => RetainerFrequency::cases(),
            'issueStatuses' => IssueStatus::cases(),
            'issueUrgencies' => IssueUrgency::cases(),
            'logs' => $this->project->logs()->orderByDesc('created_at')->get(),
            'issues' => $this->project->issues()
                ->with('tasks')
                ->withCount(['tasks', 'tasks as completed_tasks_count' => fn($q) => $q->where('is_complete', true)])
                ->orderByRaw("CASE WHEN status = 'done' THEN 1 ELSE 0 END")
                ->orderByDesc('created_at')
                ->get(),
        ]);
    }
}
