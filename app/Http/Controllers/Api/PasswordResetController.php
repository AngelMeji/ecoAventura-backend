<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;

class PasswordResetController extends Controller
{
    /**
     * Enviar enlace de restablecimiento de contraseña.
     * POST /api/password/email
     */
    public function sendResetLink(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ], [
            'email.required' => 'El correo electrónico es obligatorio.',
            'email.email'    => 'El correo electrónico no es válido.',
        ]);

        $status = Password::sendResetLink(
            $request->only('email')
        );

        if ($status === Password::RESET_LINK_SENT) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Se ha enviado el enlace de restablecimiento de contraseña a tu correo electrónico.',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'No pudimos encontrar un usuario con ese correo electrónico.',
            'errors'  => ['email' => [__($status)]],
        ], 422);
    }

    /**
     * Restablecer la contraseña con el token.
     * POST /api/password/reset
     */
    public function reset(Request $request): JsonResponse
    {
        $request->validate([
            'token'    => ['required'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'confirmed', 'min:6'],
        ], [
            'token.required'     => 'El token de restablecimiento es obligatorio.',
            'email.required'     => 'El correo electrónico es obligatorio.',
            'email.email'        => 'El correo electrónico no es válido.',
            'password.required'  => 'La nueva contraseña es obligatoria.',
            'password.confirmed' => 'La confirmación de contraseña no coincide.',
            'password.min'       => 'La nueva contraseña debe tener al menos 6 caracteres.',
        ]);

        $status = Password::reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (User $user) use ($request) {
                $user->forceFill([
                    'password'       => Hash::make($request->password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($user));
            }
        );

        if ($status === Password::PASSWORD_RESET) {
            return response()->json([
                'status'  => 'success',
                'message' => 'Tu contraseña ha sido restablecida correctamente.',
            ]);
        }

        return response()->json([
            'status'  => 'error',
            'message' => 'No se pudo restablecer la contraseña.',
            'errors'  => ['email' => [__($status)]],
        ], 422);
    }
}
