<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class EmailParser
{
    public static function parse(string $rawEmail): array
    {
        $apiKey = config('services.anthropic.api_key');

        if (!$apiKey) {
            return self::fallbackParse($rawEmail);
        }

        try {
            $response = Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->timeout(15)->post('https://api.anthropic.com/v1/messages', [
                'model' => 'claude-sonnet-4-20250514',
                'max_tokens' => 1024,
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => "Extract structured data from this client email. Return ONLY valid JSON (no markdown, no code fences) with these fields:\n- title: a short summary of what the client needs (max 100 chars)\n- description: bullet-point action items extracted from the email\n- urgency: one of 'low', 'medium', 'high' based on the tone and content\n\nEmail:\n{$rawEmail}",
                    ],
                ],
            ]);

            $text = $response->json('content.0.text');
            $parsed = json_decode($text, true);

            if (!$parsed || !isset($parsed['title'])) {
                return self::fallbackParse($rawEmail);
            }

            return [
                'title' => substr($parsed['title'], 0, 255),
                'description' => $parsed['description'] ?? null,
                'urgency' => in_array($parsed['urgency'] ?? '', ['low', 'medium', 'high'])
                    ? $parsed['urgency'] : 'medium',
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
        ];
    }
}
