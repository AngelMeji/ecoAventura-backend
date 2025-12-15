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
     * Muestra estadísticas básicas del sistema.
     */
    public function dashboard(Request $request)
    {
        return response()->json([
            'message' => 'Dashboard de administrador',
            'user' => $request->user(),
        ]);
    }

    /**
     * Estadísticas completas para el panel admin
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
    public function allPlaces(Request $request)
    {
        $query = Place::with(['user', 'category', 'images']);

        // Filtros opcionales
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $places = $query->latest()->get();

        return response()->json([
            'data' => $places->map(function ($place) {
                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'slug' => $place->slug,
                    'short_description' => $place->short_description,
                    'description' => $place->description,
                    'address' => $place->address,
                    'latitude' => $place->latitude,
                    'longitude' => $place->longitude,
                    'is_featured' => $place->is_featured,
                    'status' => $place->status,
                    'category' => $place->category ? [
                        'id' => $place->category->id,
                        'name' => $place->category->name,
                    ] : null,
                    'user' => $place->user ? [
                        'id' => $place->user->id,
                        'name' => $place->user->name,
                        'email' => $place->user->email,
                    ] : null,
                    'images' => $place->images->map(fn ($img) => [
                        'id' => $img->id,
                        'url' => $img->url,
                        'filename' => $img->filename,
                        'is_primary' => $img->is_primary,
                        'order' => $img->order,
                    ])->values(),
                    'primary_image_url' => $place->primaryImage?->url ?? $place->images->first()?->url,
                    'created_at' => $place->created_at?->toIso8601String(),
                    'updated_at' => $place->updated_at?->toIso8601String(),
                ];
            }),
            'total' => $places->count(),
        ]);
    }

    // TABLA: PENDIENTES
    public function pendingPlaces()
    {
        $places = Place::where('status', 'pending')
            ->with(['user', 'category', 'images'])
            ->latest()
            ->get();

        return response()->json([
            'data' => $places->map(function ($place) {
                return [
                    'id' => $place->id,
                    'name' => $place->name,
                    'slug' => $place->slug,
                    'short_description' => $place->short_description,
                    'status' => $place->status,
                    'category' => $place->category ? [
                        'id' => $place->category->id,
                        'name' => $place->category->name,
                    ] : null,
                    'user' => $place->user ? [
                        'id' => $place->user->id,
                        'name' => $place->user->name,
                    ] : null,
                    'primary_image_url' => $place->primaryImage?->url ?? $place->images->first()?->url,
                    'created_at' => $place->created_at?->toIso8601String(),
                ];
            }),
            'total' => $places->count(),
        ]);
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
}
