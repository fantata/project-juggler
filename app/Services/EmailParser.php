<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailParser
{
    public static function parse(string $rawEmail): array
    {
        $apiKey = env('GROQ_API_KEY');

        if (!$apiKey) {
            return self::fallbackParse($rawEmail);
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->timeout(15)->post('https://api.groq.com/openai/v1/chat/completions', [
                'model' => 'llama-3.3-70b-versatile',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Extract structured data from this client email. Return ONLY valid JSON (no markdown, no code fences) with these fields:\n- title: a short summary of what the client needs (max 100 chars)\n- description: a brief summary or context about the request\n- urgency: one of 'low', 'medium', 'high' based on the tone and content\n- tasks: an array of specific actionable tasks extracted from the email (each task should be a short, concrete action item)\n\nEmail:\n{$rawEmail}",
                    ],
                ],
            ]);

            $text = $response->json('choices.0.message.content');

            // Strip markdown code fences if present
            $text = preg_replace('/^```(?:json)?\s*/i', '', $text);
            $text = preg_replace('/\s*```$/i', '', $text);

            $parsed = json_decode(trim($text), true);

            if (!$parsed || !isset($parsed['title'])) {
                return self::fallbackParse($rawEmail);
            }

            // Handle description as array (bullet points) or string
            $description = $parsed['description'] ?? null;
            if (is_array($description)) {
                $description = implode("\n", array_map(fn($item) => "• {$item}", $description));
            }

            // Extract tasks array
            $tasks = $parsed['tasks'] ?? [];
            if (!is_array($tasks)) {
                $tasks = [];
            }

            return [
                'title' => substr($parsed['title'], 0, 255),
                'description' => $description,
                'urgency' => in_array($parsed['urgency'] ?? '', ['low', 'medium', 'high'])
                    ? $parsed['urgency'] : 'medium',
                'tasks' => $tasks,
            ];
        } catch (\Exception $e) {
            return self::fallbackParse($rawEmail);
        }
    }

    private static function fallbackParse(string $rawEmail): array
    {
        $lines = explode("\n", trim($rawEmail));

        return [
            'title' => substr($lines[0] ?? 'Untitled issue', 0, 255),
            'description' => $rawEmail,
            'urgency' => 'medium',
            'tasks' => [],
        ];
    }
}
