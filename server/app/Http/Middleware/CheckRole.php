<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Gestisce l'accesso in base al ruolo dell'utente autenticato.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $role
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        $user = $request->user();

        // Utente non autenticato
        if (!$user) {
            return response()->json(['message' => 'Non autenticato'], 401);
        }

        // Ruolo non autorizzato
        if (!$user->hasRole($role)) {
            return response()->json([
                'message' => 'Accesso non autorizzato - ruolo richiesto: ' . $role
            ], 403);
        }

        return $next($request);
    }
}

