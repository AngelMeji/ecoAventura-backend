<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use App\Models\User;

class VerificationController extends Controller
{
    /**
     * Marcar el email del usuario como verificado.
     * En una API, recibimos el ID y el Hash de la URL firmada.
     */
    public function verify(Request $request, $id, $hash)
    {
        $user = User::findOrFail($id);

        if (!hash_equals((string) $hash, sha1($user->getEmailForVerification()))) {
            return response()->json([
                'message' => 'Enlace de verificación inválido.'
            ], 403);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'El correo electrónico ya ha sido verificado.'
            ], 200);
        }

        if ($user->markEmailAsVerified()) {
            event(new \Illuminate\Auth\Events\Verified($user));
        }

        return response()->json([
            'message' => 'Correo electrónico verificado con éxito.'
        ], 200);
    }

    /**
     * Reenviar el enlace de verificación de correo electrónico.
     */
    public function resend(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();

        if (!$user) {
            return response()->json(['message' => 'Usuario no encontrado.'], 404);
        }

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'El correo electrónico ya ha sido verificado.'
            ], 400);
        }

        $user->sendEmailVerificationNotification();

        return response()->json([
            'message' => 'Se ha enviado un nuevo enlace de verificación a su dirección de correo electrónico.'
        ], 200);
    }
}
