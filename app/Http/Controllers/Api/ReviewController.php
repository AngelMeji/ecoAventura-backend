<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Review;
use App\Models\Place;
use Illuminate\Support\Facades\DB;
use Waad\ProfanityFilter\Facades\ProfanityFilter;

class ReviewController extends Controller
{
    /**
     * Listar reseñas.
     * GET /api/reviews
     */
    public function index(Request $request)
    {
        // Si es admin, puede ver todas (o filtrar por lugar)
        if ($request->user()->isAdmin()) {
            $query = Review::with(['user', 'place']);
            if ($request->filled('place_id')) {
                $query->where('place_id', $request->place_id);
            }
            return response()->json($query->latest()->get());
        }

        // Si es user, ver sus propias reseñas
        return response()->json($request->user()->reviews()->with('place')->latest()->get());
    }

    /**
     * Crear una reseña para un lugar.
     * POST /api/places/{placeId}/reviews
     */
    public function store(Request $request, $placeId)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => [
                'required', 
                'string', 
                'min:5', 
                'max:1000',
                function ($attribute, $value, $fail) {
                    if (ProfanityFilter::hasProfanity($value)) {
                        $fail('El comentario contiene palabras ofensivas.');
                    }
                }
            ],
        ]);

        $place = Place::findOrFail($placeId);

        // Verificar si el usuario ya ha reseñado este lugar
        $existingReview = Review::where('place_id', $place->id)
            ->where('user_id', $request->user()->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Ya has publicado una reseña para este lugar.'
            ], 422);
        }

        // Crear la reseña
        $review = Review::create([
            'user_id' => $request->user()->id,
            'place_id' => $place->id,
            'rating' => $request->rating,
            'comment' => $request->comment,
        ]);

        return response()->json([
            'message' => 'Reseña publicada correctamente',
            'review' => $review
        ], 201);
    }

    /**
     * Actualizar una reseña.
     * PUT /api/reviews/{id}
     */
    public function update(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        // Autorización: Dueño o Admin
        if ($review->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => [
                'required', 
                'string', 
                'min:5', 
                'max:500',
                function ($attribute, $value, $fail) {
                    if (ProfanityFilter::hasProfanity($value)) {
                        $fail('El comentario contiene palabras ofensivas.');
                    }
                }
            ],
        ]);

        $review->update([
            'rating' => $request->rating,
            'comment' => $request->comment
        ]);

        return response()->json(['message' => 'Reseña actualizada', 'review' => $review]);
    }

    /**
     * Eliminar una reseña.
     * DELETE /api/reviews/{id}
     */
    public function destroy(Request $request, $id)
    {
        $review = Review::findOrFail($id);

        // Solo el dueño de la reseña o un admin pueden eliminarla
        if ($review->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'No autorizado para eliminar esta reseña'
            ], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada correctamente'
        ]);
    }
}
