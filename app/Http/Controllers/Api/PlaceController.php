<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Place;
use Illuminate\Support\Str;

class PlaceController extends Controller
{
    /**
     * LISTAR LUGARES PÚBLICOS (approved)
     * GET /api/places
     */
    public function index(Request $request)
    {
        $query = Place::with(['category', 'user', 'images'])
            ->withAvg('reviews', 'rating')
            ->withExists([
                'favorites as is_favorite' => function ($q) {
                    $q->where('user_id', auth('sanctum')->id());
                }
            ])
            ->where('status', 'approved');

        // Filtrar por categoría (?category=cascadas)
        if ($request->filled('category')) {
            $query->whereHas('category', function ($q) use ($request) {
                $q->where('slug', $request->category);
            });
        }

        // Filtrar destacados (?featured=1)
        if ($request->filled('featured')) {
            $query->where('is_featured', $request->featured);
        }

        // Buscar por nombre (?search=cascada)
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        return response()->json($query->latest()->paginate(10));
    }

    /**
     * VER DETALLE DE UN LUGAR
     * GET /api/places/{slug}
     */
    public function show($identifier)
    {
        $query = Place::with(['category', 'user', 'reviews.user', 'images'])
            ->withAvg('reviews', 'rating')
            ->withExists([
                'favorites as is_favorite' => function ($q) {
                    $q->where('user_id', auth('sanctum')->id());
                }
            ]);

        // Si es ID
        if (is_numeric($identifier)) {
            $query->where('id', $identifier);
        } else {
            $query->where('slug', $identifier);
        }

        $place = $query->firstOrFail();

        // Verificar visibilidad
        // Si no es aprobado, SOLO admin o dueño pueden verlo
        if ($place->status !== 'approved') {
            $user = auth('sanctum')->user(); // Obtener usuario si hay token

            if (!$user) {
                abort(404, 'Lugar no encontrado');
            }

            if (!$user->isAdmin() && $user->id !== $place->user_id) {
                abort(403, 'No tienes permiso para ver este lugar pendiente.');
            }
        }

        return response()->json($place);
    }

    /**
     * CREAR LUGAR (partner / admin)
     * POST /api/places
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'short_description' => 'required|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'images.*' => 'image|mimes:jpeg,png,jpg,gif|max:5048',
            'difficulty' => 'nullable|in:baja,media,alta,experto',
            'duration' => 'nullable|string|max:255',
            'best_season' => 'nullable|string|max:255',
        ]);

        $place = Place::create([
            'user_id' => $request->user()->id,
            'category_id' => $request->category_id,
            'name' => $request->name,
            'slug' => Str::slug($request->name) . '-' . uniqid(),
            'short_description' => $request->short_description,
            'description' => $request->description,
            'address' => $request->address,
            'latitude' => $request->latitude,
            'longitude' => $request->longitude,
            'status' => $request->user()->isAdmin()
                ? 'approved'
                : 'pending',
            'difficulty' => $request->difficulty,
            'duration' => $request->duration,
            'best_season' => $request->best_season,
        ]);

        // Subir imágenes
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $path = $image->store('places', 'public');
                $place->images()->create(['image_path' => $path]);
            }
        }

        return response()->json([
            'message' => 'Lugar creado correctamente',
            'place' => $place->load('images')
        ], 201);
    }

    /**
     * ACTUALIZAR LUGAR (dueño o admin)
     * PUT /api/places/{id}
     */
    public function update(Request $request, int $id)
    {
        $place = Place::findOrFail($id);

        // Autorización manual (por ahora)
        if (
            !$request->user()->isAdmin() &&
            $place->user_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'No autorizado'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|string|max:255',
            'category_id' => 'sometimes|exists:categories,id',
            'short_description' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:255',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
            'is_featured' => 'boolean',
            'difficulty' => 'nullable|in:baja,media,alta,experto',
            'duration' => 'nullable|string|max:255',
            'best_season' => 'nullable|string|max:255',
        ]);

        /* Si cambia el nombre, cambia el slug */
        if ($request->has('name')) {
            $place->slug = Str::slug($request->name) . '-' . uniqid();
        }

        $place->update($request->only([
            'name',
            'category_id',
            'short_description',
            'description',
            'address',
            'latitude',
            'longitude',
            'is_featured',
            'difficulty',
            'duration',
            'best_season',
        ]));

        return response()->json([
            'message' => 'Lugar actualizado correctamente',
            'place' => $place
        ]);
    }

    /**
     * ELIMINAR LUGAR (solo admin)
     * DELETE /api/places/{id}
     */
    public function destroy(Request $request, int $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Solo el administrador puede eliminar lugares'
            ], 403);
        }

        $place = Place::findOrFail($id);
        $place->delete();

        return response()->json([
            'message' => 'Lugar eliminado correctamente'
        ]);
    }

    /**
     * LISTAR LUGARES PENDIENTES (admin)
     * GET /api/admin/places/pending
     */
    public function pending()
    {
        $places = Place::with(['category', 'user'])
            ->where('status', 'pending')
            ->latest()
            ->paginate(10);

        return response()->json($places);
    }

    /**
     * APROBAR LUGAR (admin)
     */
    public function approve(int $id)
    {
        $place = Place::findOrFail($id);
        $place->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Lugar aprobado'
        ]);
    }

    /**
     * RECHAZAR LUGAR (admin)
     */
    public function reject(int $id)
    {
        $place = Place::findOrFail($id);
        $place->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Lugar rechazado'
        ]);
    }

    /**
     * PEDIR CORRECCIÓN (admin)
     */
    public function needsFix(int $id)
    {
        $place = Place::findOrFail($id);
        $place->update(['status' => 'needs_fix']);

        return response()->json([
            'message' => 'Lugar marcado para corrección'
        ]);
    }
}
