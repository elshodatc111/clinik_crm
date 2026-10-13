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
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'clinic' => \App\Http\Middleware\ClinicAccess::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\SetLocale::class, \App\Http\Middleware\RecordMetrics::class]);
        $middleware->validateCsrfTokens(except: ['payme', 'payme/c/*']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            \App\Services\SystemMonitor::exception($e);
        });
    })->create();
