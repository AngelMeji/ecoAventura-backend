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
            'current_password' => 'required|current_password',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'current_password.current_password' => 'La contraseña actual no es correcta.',
            'password.min' => 'La nueva contraseña debe tener al menos 8 caracteres.',
            'password.confirmed' => 'La confirmación de la contraseña no coincide.',
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => '¡Éxito! Tu contraseña ha sido actualizada correctamente.',
        ]);
    }
}
