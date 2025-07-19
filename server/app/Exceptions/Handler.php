<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Throwable;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Exceptions\TokenExpiredException;
use Tymon\JWTAuth\Exceptions\TokenInvalidException;


class Handler extends ExceptionHandler
{
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $e)
    {
        // Per le richieste API
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    protected function handleApiException($request, Throwable $e): JsonResponse
    {
        if ($e instanceof TokenExpiredException) {
            return response()->json(['error' => 'Token scaduto'], 401);
        }

        if ($e instanceof TokenInvalidException) {
            return response()->json(['error' => 'Token non valido'], 401);
        }

        if ($e instanceof JWTException) {
            return response()->json(['error' => 'Token mancante'], 401);
        }

        return response()->json([
            'error' => 'Errore del server',
            'message' => config('app.debug') ? $e->getMessage() : null
        ], 500);
    }
}
