<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Validation\ValidationException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        api: __DIR__ . '/../routes/api.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Alias middleware personalizzati per i ruoli
        $middleware->alias([
            'role' => \App\Http\Middleware\CheckRole::class,
            'admin' => \App\Http\Middleware\CheckRole::class . ':admin',
            'user' => \App\Http\Middleware\CheckRole::class . ':user',
            'checkCart' => \App\Http\Middleware\CheckCart::class,
        ]);

        // Se vuoi aggiungerli a gruppi, puoi farlo così:
        // $middleware->api(append: ['role']);
    })

    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Cookie\Middleware\EncryptCookies::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->renderable(function (Throwable $e, $request) {
            if ($e instanceof AuthenticationException) {
                return response()->json([
                    'message' => 'Token mancante o non valido'
                ], 401);
            }

            if ($e instanceof NotFoundHttpException) {
                return response()->json([
                    'message' => 'Endpoint non trovato'
                ], 404);
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Errore di validazione',
                    'errors' => $e->errors(),
                ], 422);
            }

            // fallback generico
            return response()->json([
                'message' => 'Errore interno',
                'error' => $e->getMessage(),
            ], 500);
        });
    })
    ->create();
