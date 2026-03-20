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
        $middleware->alias([
            'mobile.auth' => \App\Http\Middleware\AuthenticateMobileToken::class,
            'role' => \App\Http\Middleware\EnsureUserRole::class,
            'subscription.active' => \App\Http\Middleware\EnsureActiveSubscription::class,
            'permit.approved' => \App\Http\Middleware\EnsureFarmOwnerApproved::class,
        ]);

        // Exclude PayMongo webhook from CSRF verification
        $middleware->validateCsrfTokens(except: [
            'webhooks/paymongo',
        ]);
        
        // Keep Laravel defaults (including CORS) and append custom optimization middleware.
        $middleware->append(\App\Http\Middleware\OptimizeDatabase::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (Throwable $e) {
            if ($e instanceof \Illuminate\Auth\AuthorizationException) {
                return response()->view('errors.403', [], 403);
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return back()->withErrors($e->errors())->withInput();
            }

            if ($e instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
                return response()->view('errors.404', [], 404);
            }
        });
    })->create();
