<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        return $request->user()
            ->favorites()
            ->with('place.category')
            ->latest()
            ->get();
    }

    public function store(Request $request)
    {
        $request->validate([
            'place_id' => 'required|exists:places,id'
        ]);

        $request->user()->favorites()->firstOrCreate([
            'place_id' => $request->place_id
        ]);

        return response()->json([
            'message' => 'Agregado a favoritos'
        ], 201);
    }

    public function destroy(Request $request, int $placeId)
    {
        $request->user()->favorites()
            ->where('place_id', $placeId)
            ->delete();

        return response()->json([
            'message' => 'Eliminado de favoritos'
        ]);
    }
}
