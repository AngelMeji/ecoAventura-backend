<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Dashboard del Usuario
     * Muestra estadísticas personales (favoritos, reseñas).
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        $stats = [
            'favorites_count' => $user->favorites()->count(),
            'reviews_count' => $user->reviews()->count(),
        ];

        return response()->json([
            'message' => 'Dashboard de usuario',
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
