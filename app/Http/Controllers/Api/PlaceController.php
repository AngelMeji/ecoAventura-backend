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
            ]);

        // Si es Admin o el Dueño consultando sus propios lugares, no filtramos por 'approved'
        $user = auth('sanctum')->user();
        $isFilteringBySelf = $request->filled('user_id') && $user && (int) $request->user_id === (int) $user->id;

        if (!$isFilteringBySelf && (!$user || !$user->isAdmin())) {
            $query->where('status', 'approved');
        }

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

        // Filipar por ID de Usuario (?user_id=5)
        // Útil para el dashboard de socios
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
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
            'short_description' => 'required|string|max:1000',
            'description' => 'required|string|min:50|max:5000', // Descripción completa requerida, mínimo 50 caracteres
            'address' => 'required|string|max:255',
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'images' => 'required|array|min:1', // Al menos una imagen es obligatoria
            'images.*' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // Max 5MB
            'difficulty' => 'required|in:baja,media,alta,experto',
            'duration' => 'required|string|max:255',
            'best_season' => 'required|string|max:255',
        ], [
            // Mensajes personalizados en español
            'name.required' => 'El nombre del lugar es obligatorio',
            'category_id.required' => 'Debes seleccionar una categoría',
            'category_id.exists' => 'La categoría seleccionada no existe',
            'short_description.required' => 'La descripción corta es obligatoria',
            'description.required' => 'La descripción completa es obligatoria',
            'description.min' => 'La descripción debe tener al menos 50 caracteres',
            'address.required' => 'La dirección es obligatoria',
            'latitude.required' => 'La latitud es obligatoria',
            'latitude.between' => 'La latitud debe estar entre -90 y 90',
            'longitude.required' => 'La longitud es obligatoria',
            'longitude.between' => 'La longitud debe estar entre -180 y 180',
            'images.required' => 'Debes subir al menos una imagen',
            'images.min' => 'Debes subir al menos una imagen',
            'images.*.image' => 'Todos los archivos deben ser imágenes',
            'images.*.mimes' => 'Las imágenes deben ser de tipo: jpeg, png, jpg, gif o webp',
            'images.*.max' => 'Cada imagen no puede superar los 5MB',
            'difficulty.required' => 'La dificultad es obligatoria',
            'difficulty.in' => 'La dificultad debe ser: baja, media, alta o experto',
            'duration.required' => 'La duración es obligatoria',
            'best_season.required' => 'La mejor temporada es obligatoria',
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

        // Autorización manual
        if (
            !$request->user()->isAdmin() &&
            (int) $place->user_id !== (int) $request->user()->id
        ) {
            return response()->json([
                'message' => 'No autorizado para editar este lugar'
            ], 403);
        }

        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'category_id' => 'sometimes|required|exists:categories,id',
            'short_description' => 'sometimes|required|string|max:1000',
            'description' => 'sometimes|required|string|min:50|max:5000',
            'address' => 'sometimes|required|string|max:255',
            'latitude' => 'sometimes|required|numeric|between:-90,90',
            'longitude' => 'sometimes|required|numeric|between:-180,180',
            'is_featured' => 'sometimes|boolean',
            'difficulty' => 'sometimes|required|in:baja,media,alta,experto',
            'duration' => 'sometimes|required|string|max:255',
            'best_season' => 'sometimes|required|string|max:255',
            'status' => 'sometimes|in:pending,approved,rejected,needs_fix',
        ]);

        /* Si cambia el nombre, cambia el slug */
        if ($request->has('name')) {
            $place->slug = Str::slug($request->name) . '-' . uniqid();
        }

        $data = $request->only([
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
        ]);

        // Solo admin puede cambiar status directamente en update
        if ($request->user()->isAdmin() && $request->has('status')) {
            $data['status'] = $request->status;
        } elseif (!$request->user()->isAdmin()) {
            // Si es socio, al editar vuelve a pendiente para revisión
            $data['status'] = 'pending';
        }

        $place->update($data);

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
    public function approve(Request $request, int $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $place = Place::findOrFail($id);
        $place->update(['status' => 'approved']);

        return response()->json([
            'message' => 'Lugar aprobado'
        ]);
    }

    /**
     * RECHAZAR LUGAR (admin)
     */
    public function reject(Request $request, int $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $place = Place::findOrFail($id);
        $place->update(['status' => 'rejected']);

        return response()->json([
            'message' => 'Lugar rechazado'
        ]);
    }

    /**
     * PEDIR CORRECCIÓN (admin)
     */
    public function needsFix(Request $request, int $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $place = Place::findOrFail($id);
        $place->update(['status' => 'needs_fix']);

        return response()->json([
            'message' => 'Lugar marcado para corrección'
        ]);
    }

    /**
     * MARCAR COMO PENDIENTE (admin)
     */
    public function setPending(Request $request, int $id)
    {
        $place = Place::findOrFail($id);

        // Permitir si es Admin O si es el dueño del lugar
        if (!$request->user()->isAdmin() && (int) $request->user()->id !== (int) $place->user_id) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        $place->update(['status' => 'pending']);

        return response()->json([
            'message' => 'Lugar marcado como pendiente'
        ]);
    }
}
