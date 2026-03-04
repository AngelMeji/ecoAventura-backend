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

        // Un solo query con COUNT condicional en lugar de 3 o 4 queries separados
        $placeSummary = \Illuminate\Support\Facades\DB::table('places')
            ->where('user_id', $user->id)
            ->selectRaw("
                COUNT(*) as total_places,
                SUM(status = 'approved') as approved_places,
                SUM(status = 'pending') as pending_places
            ")
            ->first();

        $stats = [
            'total_places'    => (int) ($placeSummary->total_places ?? 0),
            'approved_places' => (int) ($placeSummary->approved_places ?? 0),
            'pending_places'  => (int) ($placeSummary->pending_places ?? 0),
            'average_rating'  => DB::table('reviews')
                ->join('places', 'reviews.place_id', '=', 'places.id')
                ->where('places.user_id', $user->id)
                ->avg('reviews.rating') ?? 0,

            // Top 5 lugares mejor valorados (ya estaba correcto)
            'top_places' => $user->places()
                ->with('images')
                ->withAvg('reviews', 'rating')
                ->where('status', 'approved')
                ->orderByDesc('reviews_avg_rating')
                ->take(5)
                ->get(),
        ];

        return response()->json([
            'message' => 'Dashboard de socio',
            'user'    => $user,
            'stats'   => $stats,
            // Paginamos para no cargar TODOS los lugares de una vez
            'places'  => Place::with(['category', 'user', 'images'])
                ->withAvg('reviews', 'rating')
                ->where('user_id', $user->id)
                ->latest()
                ->paginate(15),
        ]);
    }
}
