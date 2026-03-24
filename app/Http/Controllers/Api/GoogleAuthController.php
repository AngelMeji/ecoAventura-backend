<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function handleGoogleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'nullable|string',
            'access_token' => 'nullable|string',
            'intent' => 'nullable|string|in:login,register',
        ]);

        $intent = $request->input('intent', 'login'); // 'login' o 'register'

        if (!$request->credential && !$request->access_token) {
            return response()->json([
                'message' => 'Se requiere un token de Google.',
            ], 400);
        }

        $clientId = env('GOOGLE_CLIENT_ID');
        if (!$clientId && $request->credential) {
            return response()->json([
                'message' => 'Google Client ID no configurado en el servidor.',
            ], 500);
        }

        $payload = null;

        if ($request->access_token) {
            // Validar access_token obteniendo el perfil de `oauth2/v3/userinfo`
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()
                ->withHeaders(['Authorization' => 'Bearer ' . $request->access_token])
                ->get('https://www.googleapis.com/oauth2/v3/userinfo');

            if ($response->successful()) {
                $payload = $response->json();
            } else {
                return response()->json([
                    'message' => 'Token de acceso de Google inválido.',
                ], 401);
            }
        } elseif ($request->credential) {
            // Validar credential (ID token) con `tokeninfo`
            $response = \Illuminate\Support\Facades\Http::withoutVerifying()->get('https://oauth2.googleapis.com/tokeninfo', [
                'id_token' => $request->credential
            ]);

            if ($response->successful()) {
                $payload = $response->json();
                
                if ($payload['aud'] !== $clientId) {
                    return response()->json([
                        'message' => 'Token no pertenece a esta aplicación.',
                    ], 401);
                }
            } else {
                return response()->json([
                    'message' => 'Token de Google inválido (ID Token).',
                ], 401);
            }
        }

        if ($payload) {
            $googleId = $payload['sub'];
            $email = $payload['email'] ?? null;
            $name = $payload['name'] ?? null;
            $picture = $payload['picture'] ?? null;
            $emailVerified = $payload['email_verified'] ?? false;

            if (!$emailVerified || !$email) {
                return response()->json([
                    'message' => 'El correo de Google no está verificado.',
                ], 403);
            }

            $user = User::where('email', $email)->first();

            if ($intent === 'register') {
                if ($user) {
                    return response()->json([
                        'message' => 'Esta cuenta ya está creada. Por favor, inicia sesión.',
                    ], 409);
                }
                
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)),
                    'role' => 'user',
                    'avatar' => $picture,
                ]);

                // Como el correo viene de Google, lo marcamos como verificado automáticamente
                $user->email_verified_at = now();
                $user->save();
                
            } else {
                // Modo login
                if (!$user) {
                    return response()->json([
                        'message' => 'No se encontró una cuenta con este correo. Por favor, regístrate primero.',
                    ], 404);
                }
                
                if (!$user->avatar && $picture) {
                    $user->update(['avatar' => $picture]);
                }
            }

            $user->tokens()->delete();
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login con Google exitoso',
                'user' => $user,
                'token' => $token,
            ]);
        }

        return response()->json([
            'message' => 'Google Login fallido.',
        ], 401);
    }
}
