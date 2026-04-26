<?php

namespace App\Services;

use App\Models\Issue;
use App\Models\Project;
use Illuminate\Database\Eloquent\Collection;

class IssueService
{
    public function __construct(private readonly GitHubService $github = new GitHubService) {}

    public function listForProject(Project $project, ?string $status = null): Collection
    {
        $query = $project->issues();

        if ($status) {
            $query->where('status', $status);
        }

        return $query->get();
    }

    public function create(Project $project, array $data, array $taskDescriptions = []): Issue
    {
        $issue = $project->issues()->create(array_merge(
            ['status' => 'open', 'urgency' => 'medium'],
            $data,
        ));

        foreach (array_values($taskDescriptions) as $i => $description) {
            $clean = trim((string) $description);
            if ($clean === '') {
                continue;
            }
            $issue->tasks()->create([
                'description' => $clean,
                'position' => $i + 1,
                'is_ai_generated' => false,
            ]);
        }

        if ($project->github_repo && GitHubService::isConfigured()) {
            try {
                $gh = GitHubService::createIssue($project->github_repo, $issue->title, $issue->description);
                if (! empty($gh['number'])) {
                    $issue->update(['github_issue_number' => $gh['number']]);
                }
            } catch (\Throwable $e) {
                // GitHub push failed — issue still created locally
            }
        }

        $project->markTouched();

        return $issue->fresh();
    }

    public function update(Issue $issue, array $data): Issue
    {
        $oldStatus = $issue->status;
        $issue->fill($data);
        $issue->save();
        $issue->project->markTouched();

        if (isset($data['status']) && $issue->github_issue_number && $issue->project->github_repo && GitHubService::isConfigured()) {
            try {
                if ($data['status'] === 'done' && $oldStatus !== 'done') {
                    GitHubService::closeIssue($issue->project->github_repo, $issue->github_issue_number);
                } elseif ($data['status'] !== 'done' && $oldStatus === 'done') {
                    GitHubService::reopenIssue($issue->project->github_repo, $issue->github_issue_number);
                }
            } catch (\Throwable $e) {
                // GitHub sync failed silently
            }
        }

        return $issue->fresh();
    }

    public function syncFromGithub(Project $project): array
    {
        if (! $project->github_repo) {
            throw new \RuntimeException('Project has no GitHub repo configured');
        }
        if (! GitHubService::isConfigured()) {
            throw new \RuntimeException('GITHUB_TOKEN not configured');
        }

        $ghIssues = GitHubService::listIssues($project->github_repo);
        $created = 0;
        $updated = 0;

        foreach ($ghIssues as $ghIssue) {
            $number = $ghIssue['number'];
            $ghStatus = ($ghIssue['state'] ?? null) === 'closed' ? 'done' : 'open';
            $local = $project->issues()->where('github_issue_number', $number)->first();

            if ($local) {
                $changes = [];
                if ($local->title !== $ghIssue['title']) {
                    $changes['title'] = $ghIssue['title'];
                }
                if ($ghStatus === 'done' && $local->status !== 'done') {
                    $changes['status'] = 'done';
                } elseif ($ghStatus === 'open' && $local->status === 'done') {
                    $changes['status'] = 'open';
                }
                if (! empty($changes)) {
                    $local->update($changes);
                    $updated++;
                }
            } else {
                $project->issues()->create([
                    'title' => $ghIssue['title'],
                    'description' => $ghIssue['body'] ?? null,
                    'status' => $ghStatus,
                    'urgency' => 'medium',
                    'github_issue_number' => $number,
                ]);
                $created++;
            }
        }

        return [
            'created' => $created,
            'updated' => $updated,
            'total_github_issues' => count($ghIssues),
        ];
    }
}
