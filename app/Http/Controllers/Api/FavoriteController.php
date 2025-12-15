<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use App\Models\Place;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    /**
     * Listar favoritos del usuario
     */
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with(['place.category', 'place.images', 'place.user:id,name'])
            ->latest()
            ->paginate($request->per_page ?? 12);

        return response()->json([
            'data' => $favorites->getCollection()->map(function ($fav) {
                $place = $fav->place;
                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'slug' => $place->slug,
                    'short_description' => $place->short_description,
                    'address' => $place->address,
                    'category' => $place->category ? [
                        'id' => $place->category->id,
                        'name' => $place->category->name,
                    ] : null,
                    'primary_image_url' => $place->primaryImage?->url ?? $place->images->first()?->url,
                    'favorited_at' => $fav->created_at?->toIso8601String(),
                ];
            }),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    /**
     * Agregar a favoritos
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'place_id' => 'required|exists:places,id',
        ]);

        // Verificar si ya existe
        $existing = Favorite::where('user_id', $request->user()->id)
            ->where('place_id', $validated['place_id'])
            ->first();

        if ($existing) {
            return response()->json([
                'message' => 'Este lugar ya está en tus favoritos',
            ], 422);
        }

        $favorite = Favorite::create([
            'user_id' => $request->user()->id,
            'place_id' => $validated['place_id'],
        ]);

        return response()->json([
            'message' => 'Agregado a favoritos',
            'data' => [
                'id' => $favorite->id,
                'place_id' => $favorite->place_id,
            ],
        ], 201);
    }

    /**
     * Remover de favoritos
     */
    public function destroy(Request $request, $placeId)
    {
        $favorite = Favorite::where('user_id', $request->user()->id)
            ->where('place_id', $placeId)
            ->first();

        if (!$favorite) {
            return response()->json([
                'message' => 'Este lugar no está en tus favoritos',
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Removido de favoritos',
        ]);
    }

    /**
     * Verificar si un lugar está en favoritos
     */
    public function check(Request $request, $placeId)
    {
        $isFavorite = Favorite::where('user_id', $request->user()->id)
            ->where('place_id', $placeId)
            ->exists();

        return response()->json([
            'is_favorite' => $isFavorite,
        ]);
    }
}
