<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Tymon\JWTAuth\Facades\JWTAuth;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        // 1. Validazione
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|email|unique:users,email',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. Creazione utente
        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // 3. Generazione token JWT
        $token = JWTAuth::fromUser($user);

        // 4. Risposta JSON
        return response()->json([
            'message' => 'Utente registrato con successo',
            'user'    => $user,
            'token'   => $token
        ], 201);
    }

    public function login(Request $request)
    {
        $credentials = $request->only('email', 'password');

        // Validazione base
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string|min:6',
        ]);

        // Tentativo di login
        if (!$token = Auth::attempt($credentials)) {
            return response()->json(['error' => 'Credenziali non valide'], 401);
        }

        // Recupera utente autenticato
        $user = Auth::user();

        return response()->json([
            'message' => 'Login effettuato con successo',
            'user' => $user,
            'token' => $token
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'message' => 'Utente autenticato',
            'user' => Auth::user()
        ]);
    }
}
