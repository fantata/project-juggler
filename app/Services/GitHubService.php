<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GitHubService
{
    public static function isConfigured(): bool
    {
        return !empty(env('GITHUB_TOKEN'));
    }

    public static function createIssue(string $repo, string $title, ?string $body = null): array
    {
        $response = self::request('POST', "/repos/{$repo}/issues", [
            'title' => $title,
            'body' => $body,
        ]);

        return $response;
    }

    public static function updateIssue(string $repo, int $number, array $data): array
    {
        return self::request('PATCH', "/repos/{$repo}/issues/{$number}", $data);
    }

    public static function closeIssue(string $repo, int $number): void
    {
        self::request('PATCH', "/repos/{$repo}/issues/{$number}", [
            'state' => 'closed',
        ]);
    }

    public static function reopenIssue(string $repo, int $number): void
    {
        self::request('PATCH', "/repos/{$repo}/issues/{$number}", [
            'state' => 'open',
        ]);
    }

    public static function listIssues(string $repo, string $state = 'all'): array
    {
        $allIssues = [];
        $page = 1;

        do {
            $response = Http::withHeaders(self::headers())
                ->timeout(15)
                ->get("https://api.github.com/repos/{$repo}/issues", [
                    'state' => $state,
                    'per_page' => 100,
                    'page' => $page,
                ]);

            if (!$response->successful()) {
                break;
            }

            $issues = $response->json();

            // Filter out pull requests (GitHub API returns PRs in issues endpoint)
            $issues = array_filter($issues, fn($issue) => !isset($issue['pull_request']));
            $allIssues = array_merge($allIssues, $issues);

            $page++;
        } while (count($response->json()) === 100);

        return $allIssues;
    }

    private static function request(string $method, string $path, array $data = []): array
    {
        $response = Http::withHeaders(self::headers())
            ->timeout(15)
            ->{strtolower($method)}("https://api.github.com{$path}", $data);

        return $response->json() ?? [];
    }

    private static function headers(): array
    {
        return [
            'Authorization' => 'Bearer ' . env('GITHUB_TOKEN'),
            'Accept' => 'application/vnd.github.v3+json',
            'X-GitHub-Api-Version' => '2022-11-28',
        ];
    }
}
