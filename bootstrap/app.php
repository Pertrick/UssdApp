<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->web(append: [
            \App\Http\Middleware\HandleInertiaRequests::class,
            \Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets::class,
            \App\Http\Middleware\RefreshCsrfToken::class,
        ])
        ->validateCsrfTokens(except: [
            'ussd.simulator.start',
            'ussd.simulator.input',
            'ussd.simulator.logs',
        ]);

        // Register admin middleware
            $middleware->alias([
        'admin' => \App\Http\Middleware\AdminMiddleware::class,
        'verified.business' => \App\Http\Middleware\VerifiedBusinessMiddleware::class,
    ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
