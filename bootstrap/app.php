<?php

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Support\Str;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
        channels: __DIR__.'/../routes/channels.php',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'token.cookie' => \App\Http\Middleware\TokenFromCookie::class,
            'set.locale' => \App\Http\Middleware\SetLocale::class,
            'token.user' => \App\Http\Middleware\EnsureSanctumTokenIsUser::class,
            'token.client' => \App\Http\Middleware\EnsureSanctumTokenIsClient::class,
        ]);

        $middleware->prepend(\App\Http\Middleware\TokenFromCookie::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (QueryException $exception, $request) {
            if (! $request->expectsJson()) {
                return null;
            }

            $status = 500;
            if ($exception->getCode() === '23505') {
                $status = 409;
            }

            $message = trim($exception->getMessage());
            if (Str::contains($message, '(Connection:')) {
                $message = trim(Str::before($message, '(Connection:'));
            }

            return response()->json([
                'error' => [
                    'code' => 'database_error',
                    'message' => $message,
                ],
            ], $status);
        });
    })->create();
