<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Place;
use App\Models\Review;

class AdminController extends Controller
{
    /**
     * Dashboard del Administrador
     * Muestra estadísticas globales del sistema.
     */
    // ESTADÍSTICAS REALES
    public function stats()
    {
        // TOP VALORADO
        $topRated = Place::withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->orderByDesc('reviews_avg_rating')
            ->first();

        // MÁS POPULAR (Más favoritos)
        $mostPopular = Place::withCount('favoritedBy')
            ->orderByDesc('favorited_by_count')
            ->first();

        // CATEGORÍA TOP
        $topCategory = \Illuminate\Support\Facades\DB::table('places')
            ->join('categories', 'places.category_id', '=', 'categories.id')
            ->select('categories.name', \Illuminate\Support\Facades\DB::raw('count(*) as total'))
            ->groupBy('categories.name')
            ->orderByDesc('total')
            ->first();

        return response()->json([
            'stats' => [
                'total_users' => User::count(),
                'total_places' => Place::count(),
                'pending_places' => Place::where('status', 'pending')->count(),
                'approved_places' => Place::where('status', 'approved')->count(),
                'reviews_count' => Review::count(),
                // Objetos detallados para el Dashboard
                'top_rated' => $topRated ? [
                    'name' => $topRated->name,
                    'rating' => $topRated->reviews_avg_rating,
                    'count' => $topRated->reviews_count
                ] : null,
                'most_popular' => $mostPopular ? [
                    'name' => $mostPopular->name,
                    'favorites' => $mostPopular->favorited_by_count
                ] : null,
                'top_category' => $topCategory ? [
                    'name' => $topCategory->name,
                    'count' => $topCategory->total
                ] : null
            ]
        ]);
    }

    // TABLA: TODOS LOS LUGARES (Para Admin)
    public function allPlaces()
    {
        // Retorna TODO con relaciones necesarias para la tabla visual
        return Place::with(['user', 'category', 'images'])->latest()->get();
    }

    // TABLA: PENDIENTES
    public function pendingPlaces()
    {
        return Place::where('status', 'pending')->with(['user', 'category', 'images'])->get();
    }

    /* =================================
       GESTIÓN DE USUARIOS (CRUD)
       ================================= */

    public function indexUsers()
    {
        return response()->json(User::all());
    }

    public function createUser(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:6',
            'role' => 'required|in:admin,partner,user',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => $request->role,
        ]);

        return response()->json([
            'message' => 'Usuario creado correctamente',
            'user' => $user
        ], 201);
    }

    public function updateUser(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'email' => 'sometimes|string|email|max:255|unique:users,email,' . $user->id,
            'role' => 'sometimes|in:admin,partner,user',
            'password' => 'nullable|string|min:6', // Opcional
        ]);

        $data = $request->only(['name', 'email', 'role']);

        if ($request->filled('password')) {
            $data['password'] = bcrypt($request->password);
        }

        $user->update($data);

        return response()->json([
            'message' => 'Usuario actualizado correctamente',
            'user' => $user
        ]);
    }

    public function destroyUser($id)
    {
        $user = User::findOrFail($id);

        // Evitar auto-eliminación
        if ($user->id === auth()->id()) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta'
            ], 403);
        }

        $user->delete();

        return response()->json([
            'message' => 'Usuario eliminado correctamente'
        ]);
    }

    /* =================================
       GESTIÓN DE RESEÑAS (Moderación)
       ================================= */

    /**
     * Listar todas las reseñas (para moderación)
     */
    public function indexReviews()
    {
        $reviews = Review::with(['user:id,name,email', 'place:id,name,slug'])
            ->latest()
            ->get()
            ->map(function ($review) {
                $review->raw_comment = $review->getRawOriginal('comment');
                return $review;
            });

        return response()->json($reviews);
    }

    /**
     * Ocultar/Mostrar el comentario de una reseña (toggle)
     * Mantiene la calificación (rating) visible.
     */
    public function toggleHideReview($id)
    {
        $review = Review::findOrFail($id);

        $review->is_hidden = !$review->is_hidden;
        $review->save();

        return response()->json([
            'message' => $review->is_hidden
                ? 'Comentario ocultado correctamente'
                : 'Comentario restaurado correctamente',
            'review' => array_merge($review->toArray(), ['raw_comment' => $review->getRawOriginal('comment')])
        ]);
    }
}
