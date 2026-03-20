<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Google_Client;
use Illuminate\Support\Str;

class GoogleAuthController extends Controller
{
    public function handleGoogleLogin(Request $request)
    {
        $request->validate([
            'credential' => 'required|string',
        ]);

        $clientId = env('GOOGLE_CLIENT_ID');
        if (!$clientId) {
            return response()->json([
                'message' => 'Google Client ID no configurado en el servidor.',
            ], 500);
        }

        $client = new Google_Client(['client_id' => $clientId]);
        
        // Deshabilitar verificación SSL localmente para evitar error cURL 60
        $guzzleClient = new \GuzzleHttp\Client(['verify' => false]);
        $client->setHttpClient($guzzleClient);

        $payload = $client->verifyIdToken($request->credential);

        if ($payload) {
            $googleId = $payload['sub'];
            $email = $payload['email'];
            $name = $payload['name'];
            $picture = $payload['picture'] ?? null;
            $emailVerified = $payload['email_verified'] ?? false;

            if (!$emailVerified) {
                return response()->json([
                    'message' => 'El correo de Google no está verificado.',
                ], 403);
            }

            // Buscar si el usuario ya existe por email
            $user = User::where('email', $email)->first();

            if (!$user) {
                // Crear usuario si no existe
                $user = User::create([
                    'name' => $name,
                    'email' => $email,
                    'password' => Hash::make(Str::random(24)), // Contraseña aleatoria segura
                    'role' => 'user',
                    'avatar' => $picture,
                ]);
            } else {
                // Actualizar avatar si no tiene
                if (!$user->avatar && $picture) {
                    $user->update(['avatar' => $picture]);
                }
            }

            // Revocar tokens anteriores (opcional, para mantener una sola sesión)
            $user->tokens()->delete();

            // Generar nuevo token Sanctum
            $token = $user->createToken('auth_token')->plainTextToken;

            return response()->json([
                'message' => 'Login con Google exitoso',
                'user' => $user,
                'token' => $token,
            ]);

        } else {
            // Token inválido
            return response()->json([
                'message' => 'Token de Google inválido.',
            ], 401);
        }
    }
}
