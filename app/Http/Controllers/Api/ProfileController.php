<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Actualizar perfil (nombre, email, bio, avatar)
     * PUT /api/me/profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        // Validación básica
        $request->validate([
            'name' => 'required',
            'avatar' => 'nullable|image' // Validar que sea imagen si viene
        ]);
        $user->name = $request->name;
        if ($request->has('bio'))
            $user->bio = $request->bio;
        // SUBIDA DE IMAGEN
        if ($request->hasFile('avatar')) {
            // Guardar en 'public/avatars'
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }
        $user->save();
        return response()->json($user);
    }

    /**
     * Actualizar contraseña
     * PUT /api/me/password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed',
        ], [
            'password.min' => 'La nueva contraseña debe tener al menos 6 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $user = $request->user();

        // Verificar manualmente la contraseña actual
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'message' => 'La contraseña actual no es correcta.',
                'errors' => [
                    'current_password' => ['La contraseña actual no es correcta.']
                ]
            ], 422);
        }

        // Actualizar contraseña
        $user->update([
            'password' => Hash::make($request->password),
        ]);

        // Revocar todos los tokens existentes por seguridad
        $user->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Cambio de contraseña exitoso. Por favor, ingrese nuevamente para iniciar sesión con sus nuevas credenciales.',
            'logout' => true
        ]);
    }
}
