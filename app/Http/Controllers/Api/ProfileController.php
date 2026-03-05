<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfileController extends Controller
{
    /**
     * Mostrar perfil del usuario autenticado
     * GET /api/profile
     */
    public function show(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'data' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'bio' => $user->bio,
                'avatar' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
                'notifications' => $user->notifications,
                'created_at' => $user->created_at?->toIso8601String(),
            ],
        ]);
    }

    /**
     * Actualizar perfil (nombre, email, bio, avatar)
     * PUT /api/profile
     */
    public function update(Request $request)
    {
        $user = $request->user();

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'bio' => 'nullable|string|max:500',
            'notifications' => 'sometimes|boolean',
        ]);

        if ($request->has('name')) {
            $user->name = $request->name;
        }

        if ($request->has('bio')) {
            $user->bio = $request->bio;
        }

        if ($request->has('notifications')) {
            $user->notifications = $request->notifications;
        }

        $user->save();

        // Devolver usuario directamente (sin wrapper) para compatibilidad con frontend
        return response()->json([
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'role' => $user->role,
            'bio' => $user->bio,
            'avatar' => $user->avatar ? Storage::disk('public')->url($user->avatar) : null,
            'notifications' => $user->notifications,
            'created_at' => $user->created_at?->toIso8601String(),
        ]);
    }

    /**
     * Actualizar avatar del usuario
     * POST /api/profile/avatar
     */
    public function updateAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        $user = $request->user();

        // Eliminar avatar anterior si existe
        if ($user->avatar) {
            Storage::disk('public')->delete($user->avatar);
        }

        // Guardar nuevo avatar
        $file = $request->file('avatar');
        $filename = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $path = $file->storeAs('avatars', $filename, 'public');

        $user->avatar = $path;
        $user->save();

        return response()->json([
            'message' => 'Avatar actualizado correctamente',
            'avatar_url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Actualizar contraseña
     * PUT /api/profile/password
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
