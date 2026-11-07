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
        // Proksi ortida: haqiqiy mijoz IP'si va https sxemasi (Cloudflare → NPM → konteyner)
        $middleware->prepend(\App\Http\Middleware\RealClientIp::class);
        $middleware->trustProxies(at: '*', headers: \Illuminate\Http\Request::HEADER_X_FORWARDED_FOR | \Illuminate\Http\Request::HEADER_X_FORWARDED_HOST | \Illuminate\Http\Request::HEADER_X_FORWARDED_PORT | \Illuminate\Http\Request::HEADER_X_FORWARDED_PROTO);
        $middleware->alias([
            'role' => \App\Http\Middleware\EnsureRole::class,
            'clinic' => \App\Http\Middleware\ClinicAccess::class,
            'offer' => \App\Http\Middleware\EnsureOfferAccepted::class,
        ]);
        $middleware->web(append: [\App\Http\Middleware\SecurityHeaders::class, \App\Http\Middleware\SetLocale::class, \App\Http\Middleware\RecordMetrics::class, \App\Http\Middleware\RobotsTag::class]);
        $middleware->validateCsrfTokens(except: ['payme', 'payme/c/*', 'telegram/webhook']);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->report(function (\Throwable $e) {
            \App\Services\SystemMonitor::exception($e);
        });
    })->create();
