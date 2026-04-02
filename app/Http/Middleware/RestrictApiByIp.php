<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictApiByIp
{
    /**
     * Allowed IPs for API access.
     *
     * Add your home IP, VPN IP, or any trusted server IP here.
     * Also reads from ALLOWED_API_IPS env var (comma-separated) so you
     * can manage it without a code change.
     */
    protected function allowedIps(): array
    {
        $fromEnv = env('ALLOWED_API_IPS', '');
        $envIps = array_filter(array_map('trim', explode(',', $fromEnv)));

        // Always allow localhost for local dev and server-side requests
        $defaults = ['127.0.0.1', '::1'];

        return array_merge($defaults, $envIps);
    }

    public function handle(Request $request, Closure $next): Response
    {
        // Skip IP check in local environment
        if (app()->environment('local')) {
            return $next($request);
        }

        $clientIp = $request->ip();
        $allowed = $this->allowedIps();

        if (empty(array_filter($allowed, fn($ip) => $ip !== '127.0.0.1' && $ip !== '::1'))) {
            // No IPs configured beyond localhost — deny by default
            logger()->warning('RestrictApiByIp: ALLOWED_API_IPS is empty. Set this in .env to allow external access.');
            return response()->json(['error' => 'Forbidden — ALLOWED_API_IPS not configured'], 403);
        }

        if (!in_array($clientIp, $allowed, true)) {
            return response()->json(['error' => 'Forbidden'], 403);
        }

        return $next($request);
    }
}
