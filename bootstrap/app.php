<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->trustProxies(at: '*');
        $middleware->alias([
            'auth.api' => \App\Http\Middleware\AuthenticateApiToken::class,
            'api.ip' => \App\Http\Middleware\RestrictApiByIp::class,
        ]);
        // Apply IP restriction to all API routes in production
        $middleware->appendToGroup('api', \App\Http\Middleware\RestrictApiByIp::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();
