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
    ->withMiddleware(function (Middleware $middleware) {
        // Payment gateways POST back without CSRF tokens; must match actual request paths (incl. subdirs).
        $middleware->validateCsrfTokens(except: [
            'upgrade/success',
            'upgrade/fail',
            'payment/aamarpay/callback',
            '*payment/aamarpay/callback',
            'payment/dgepay/callback',
            '*payment/dgepay/callback',
            'payment/callback',
            '*payment/callback',
        ]);

        $middleware->alias([
            'admin' => \App\Http\Middleware\AdminMiddleware::class,
            'approved' => \App\Http\Middleware\ApprovedUserMiddleware::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();
