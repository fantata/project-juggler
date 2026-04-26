<?php

namespace App\Services;

use App\Models\Project;
use Illuminate\Support\Facades\Http;

class GithubActivityService
{
    public function recentCommits(int $days = 14, ?string $repoFilter = null): array
    {
        if (! GitHubService::isConfigured()) {
            throw new \RuntimeException('GITHUB_TOKEN not configured');
        }

        $days = max(1, min(30, $days));
        $org = env('GITHUB_ORG', 'fantata');

        $commitsByRepo = $this->fetchOrgCommits($org, $days, $repoFilter);

        $repoToProject = [];
        foreach (Project::whereNotNull('github_repo')->get(['name', 'github_repo']) as $p) {
            $slug = preg_replace('#^.*/#', '', $p->github_repo);
            $repoToProject[$slug] = $p->name;
            $repoToProject[$p->github_repo] = $p->name;
        }

        $summary = collect($commitsByRepo)
            ->sortByDesc(fn ($r) => count($r['commits']))
            ->map(fn ($r) => [
                'repo' => $r['repo'],
                'project' => $repoToProject[$r['repo']] ?? $repoToProject[$r['full_repo']] ?? null,
                'commit_count' => count($r['commits']),
                'commits' => array_slice($r['commits'], 0, 10),
                'last_commit' => $r['commits'][0]['date'] ?? null,
            ])
            ->values()
            ->all();

        return [
            'days' => $days,
            'org' => $org,
            'total_commits' => array_sum(array_column($summary, 'commit_count')),
            'repos_touched' => count($summary),
            'activity' => $summary,
            'note' => empty($summary)
                ? 'No push activity found. Make sure GITHUB_TOKEN has read:org and repo scopes.'
                : null,
        ];
    }

    private function fetchOrgCommits(string $org, int $days, ?string $repoFilter): array
    {
        $since = now()->subDays($days)->toIso8601String();
        $reposResp = Http::withHeaders($this->headers())
            ->timeout(15)
            ->get("https://api.github.com/orgs/{$org}/repos", [
                'per_page' => 100,
                'sort' => 'pushed',
            ]);

        if (! $reposResp->successful()) {
            return [];
        }

        $byRepo = [];

        foreach ($reposResp->json() as $repo) {
            $slug = $repo['name'];
            if ($repoFilter && $slug !== $repoFilter) {
                continue;
            }

            $commitsResp = Http::withHeaders($this->headers())
                ->timeout(15)
                ->get("https://api.github.com/repos/{$org}/{$slug}/commits", [
                    'since' => $since,
                    'per_page' => 50,
                ]);

            if (! $commitsResp->successful()) {
                continue;
            }

            $commits = collect($commitsResp->json())
                ->reject(fn ($c) => str_starts_with($c['commit']['message'] ?? '', 'Merge '))
                ->map(fn ($c) => [
                    'sha' => substr($c['sha'] ?? '', 0, 7),
                    'message' => explode("\n", $c['commit']['message'] ?? '')[0],
                    'date' => $c['commit']['author']['date'] ?? null,
                    'author' => $c['commit']['author']['name'] ?? null,
                ])
                ->values()
                ->all();

            if (! empty($commits)) {
                $byRepo[$slug] = [
                    'repo' => $slug,
                    'full_repo' => "{$org}/{$slug}",
                    'commits' => $commits,
                ];
            }
        }

        return $byRepo;
    }

    private function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . env('GITHUB_TOKEN'),
            'Accept' => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
