<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Review;
use App\Models\Place;
use Illuminate\Http\Request;

class ReviewController extends Controller
{
    /**
     * Obtener reviews de un lugar
     */
    public function index(Place $place)
    {
        $reviews = $place->reviews()
            ->with('user:id,name,avatar')
            ->where('approved', true)
            ->latest()
            ->get();

        return response()->json([
            'data' => $reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                    'avatar' => $review->user->avatar,
                ] : null,
                'created_at' => $review->created_at?->toIso8601String(),
            ]),
        ]);
    }

    /**
     * Crear una review
     */
    public function store(Request $request, Place $place)
    {
        $validated = $request->validate([
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        // Verificar si el usuario ya hizo una review
        $existingReview = Review::where('user_id', $request->user()->id)
            ->where('place_id', $place->id)
            ->first();

        if ($existingReview) {
            return response()->json([
                'message' => 'Ya has dejado una reseña para este lugar',
            ], 422);
        }

        $review = Review::create([
            'user_id' => $request->user()->id,
            'place_id' => $place->id,
            'rating' => $validated['rating'],
            'comment' => $validated['comment'] ?? null,
            'approved' => true, // Auto-aprobar por ahora
        ]);

        $review->load('user:id,name,avatar');

        return response()->json([
            'message' => 'Reseña creada exitosamente',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
                'created_at' => $review->created_at?->toIso8601String(),
            ],
        ], 201);
    }

    /**
     * Actualizar review
     */
    public function update(Request $request, Review $review)
    {
        // Solo el autor puede editar
        if ($review->user_id !== $request->user()->id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $validated = $request->validate([
            'rating' => 'sometimes|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ]);

        $review->update($validated);

        return response()->json([
            'message' => 'Reseña actualizada exitosamente',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
            ],
        ]);
    }

    /**
     * Eliminar review
     */
    public function destroy(Request $request, Review $review)
    {
        // Solo el autor o admin puede eliminar
        if ($review->user_id !== $request->user()->id && !$request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $review->delete();

        return response()->json([
            'message' => 'Reseña eliminada exitosamente',
        ]);
    }
}
