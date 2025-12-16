<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use Illuminate\Support\Facades\DB;

class PartnerController extends Controller
{
    /**
     * Dashboard del Socio (Partner)
     * Muestra estadísticas sobre sus lugares y reseñas.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Obtener lugares del socio
        $myPlaces = Place::where('user_id', $user->id);

        $stats = [
            'total_places' => $myPlaces->count(),
            'approved_places' => (clone $myPlaces)->where('status', 'approved')->count(),
            'pending_places' => (clone $myPlaces)->where('status', 'pending')->count(),
            'average_rating' => DB::table('reviews')
                ->join('places', 'reviews.place_id', '=', 'places.id')
                ->where('places.user_id', $user->id)
                ->avg('reviews.rating') ?? 0,

            // Mis lugares mejor valorados
            'top_places' => $user->places()
                ->with('images')
                ->where('status', 'approved')
                ->orderByDesc('average_rating')
                ->take(5)
                ->get(['id', 'name', 'average_rating', 'slug']),
        ];

        return response()->json([
            'message' => 'Dashboard de socio',
            'user' => $user,
            'stats' => $stats,
        ]);
    }
}
