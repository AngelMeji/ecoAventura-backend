<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Place;
use App\Models\PlaceImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PlaceController extends Controller
{
    /**
     * Listar todos los lugares aprobados (público)
     */
    public function index(Request $request)
    {
        try {
            $query = Place::with(['category', 'images', 'primaryImage', 'user:id,name'])
                ->where('status', 'approved');

            // Filtros opcionales
            if ($request->has('category_id')) {
                $query->where('category_id', $request->category_id);
            }

            if ($request->has('featured')) {
                $query->where('is_featured', $request->boolean('featured'));
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%")
                      ->orWhere('address', 'like', "%{$search}%");
                });
            }

            $places = $query->latest()->paginate($request->per_page ?? 12);

            return response()->json($this->formatPlacesResponse($places));
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Error al cargar lugares',
                'error' => $e->getMessage(),
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => 12,
                    'total' => 0,
                ],
            ], 500);
        }
    }

    /**
     * Mostrar un lugar específico (público)
     */
    public function show(string $slug)
    {
        $place = Place::with(['category', 'images', 'user:id,name', 'reviews.user:id,name'])
            ->where('slug', $slug)
            ->where('status', 'approved')
            ->firstOrFail();

        return response()->json([
            'data' => $this->formatPlace($place, true),
        ]);
    }

    /**
     * Crear un nuevo lugar (partner/admin)
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'name' => 'required|string|max:255',
            'short_description' => 'required|string|max:500',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'images' => 'required|array|min:1|max:10',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120', // 5MB max
            'primary_image_index' => 'nullable|integer|min:0',
        ]);

        // Generar slug único
        $slug = Str::slug($validated['name']);
        $originalSlug = $slug;
        $counter = 1;
        while (Place::where('slug', $slug)->exists()) {
            $slug = "{$originalSlug}-{$counter}";
            $counter++;
        }

        // Crear el lugar
        $place = Place::create([
            'user_id' => $request->user()->id,
            'category_id' => $validated['category_id'],
            'name' => $validated['name'],
            'slug' => $slug,
            'short_description' => $validated['short_description'],
            'description' => $validated['description'] ?? null,
            'address' => $validated['address'] ?? null,
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => $request->user()->isAdmin() ? 'approved' : 'pending',
        ]);

        // Subir y guardar imágenes
        $primaryIndex = $request->input('primary_image_index', 0);
        $this->uploadImages($place, $request->file('images'), $primaryIndex);

        $place->load(['category', 'images']);

        return response()->json([
            'message' => 'Lugar creado exitosamente',
            'data' => $this->formatPlace($place),
        ], 201);
    }

    /**
     * Actualizar un lugar existente
     */
    public function update(Request $request, Place $place)
    {
        // Verificar permisos
        $this->authorizePlace($request->user(), $place);

        $validated = $request->validate([
            'category_id' => 'sometimes|exists:categories,id',
            'name' => 'sometimes|string|max:255',
            'short_description' => 'sometimes|string|max:500',
            'description' => 'nullable|string',
            'address' => 'nullable|string|max:500',
            'latitude' => 'nullable|numeric|between:-90,90',
            'longitude' => 'nullable|numeric|between:-180,180',
            'new_images' => 'nullable|array|max:10',
            'new_images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'delete_images' => 'nullable|array',
            'delete_images.*' => 'integer|exists:place_images,id',
            'primary_image_id' => 'nullable|integer|exists:place_images,id',
            'is_featured' => 'sometimes|boolean',
            'status' => [
                'sometimes',
                Rule::in(['pending', 'approved', 'rejected', 'needs_fix']),
            ],
        ]);

        // Actualizar slug si cambió el nombre
        if (isset($validated['name']) && $validated['name'] !== $place->name) {
            $slug = Str::slug($validated['name']);
            $originalSlug = $slug;
            $counter = 1;
            while (Place::where('slug', $slug)->where('id', '!=', $place->id)->exists()) {
                $slug = "{$originalSlug}-{$counter}";
                $counter++;
            }
            $validated['slug'] = $slug;
        }

        // Solo admin puede cambiar featured y status
        if (!$request->user()->isAdmin()) {
            unset($validated['is_featured'], $validated['status']);
        }

        // Eliminar imágenes seleccionadas
        if (!empty($validated['delete_images'])) {
            $imagesToDelete = PlaceImage::where('place_id', $place->id)
                ->whereIn('id', $validated['delete_images'])
                ->get();

            foreach ($imagesToDelete as $image) {
                $image->delete(); // Esto también elimina el archivo (ver modelo PlaceImage)
            }
        }

        // Cambiar imagen principal
        if (isset($validated['primary_image_id'])) {
            // Quitar primary de todas las demás
            PlaceImage::where('place_id', $place->id)->update(['is_primary' => false]);
            PlaceImage::where('id', $validated['primary_image_id'])
                ->where('place_id', $place->id)
                ->update(['is_primary' => true]);
        }

        // Subir nuevas imágenes
        if ($request->hasFile('new_images')) {
            $this->uploadImages($place, $request->file('new_images'), -1);
        }

        // Actualizar datos del lugar
        $place->update(collect($validated)->except([
            'new_images', 'delete_images', 'primary_image_id'
        ])->toArray());

        $place->load(['category', 'images']);

        return response()->json([
            'message' => 'Lugar actualizado exitosamente',
            'data' => $this->formatPlace($place),
        ]);
    }

    /**
     * Eliminar un lugar
     */
    public function destroy(Request $request, Place $place)
    {
        $this->authorizePlace($request->user(), $place);

        // Las imágenes se eliminan automáticamente por cascade y el evento deleting
        $place->images->each->delete();
        $place->delete();

        return response()->json([
            'message' => 'Lugar eliminado exitosamente',
        ]);
    }

    /**
     * Listar lugares del usuario autenticado (mis lugares)
     */
    public function myPlaces(Request $request)
    {
        $places = Place::with(['category', 'images'])
            ->where('user_id', $request->user()->id)
            ->latest()
            ->paginate($request->per_page ?? 10);

        return response()->json($this->formatPlacesResponse($places));
    }

    /**
     * Listar todos los lugares (admin)
     */
    public function adminIndex(Request $request)
    {
        $query = Place::with(['category', 'images', 'user:id,name,email']);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        if ($request->has('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        $places = $query->latest()->paginate($request->per_page ?? 15);

        return response()->json($this->formatPlacesResponse($places));
    }

    /**
     * Cambiar estado de un lugar (admin)
     */
    public function updateStatus(Request $request, Place $place)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected', 'needs_fix'])],
        ]);

        $place->update(['status' => $validated['status']]);

        return response()->json([
            'message' => 'Estado actualizado exitosamente',
            'data' => $this->formatPlace($place->load(['category', 'images'])),
        ]);
    }

    /* =======================
       Métodos privados
       ======================= */

    /**
     * Subir imágenes al storage
     */
    private function uploadImages(Place $place, array $files, int $primaryIndex = 0): void
    {
        $currentOrder = $place->images()->max('order') ?? -1;

        foreach ($files as $index => $file) {
            $currentOrder++;
            
            // Generar nombre único
            $extension = $file->getClientOriginalExtension();
            $filename = Str::uuid() . '.' . $extension;
            
            // Guardar en storage/app/public/places/{place_id}/
            $path = $file->storeAs(
                "places/{$place->id}",
                $filename,
                'public'
            );

            PlaceImage::create([
                'place_id' => $place->id,
                'path' => $path,
                'filename' => $file->getClientOriginalName(),
                'is_primary' => $index === $primaryIndex && $primaryIndex >= 0,
                'order' => $currentOrder,
            ]);
        }

        // Si no hay imagen principal, establecer la primera
        if (!$place->images()->where('is_primary', true)->exists()) {
            $first = $place->images()->orderBy('order')->first();
            if ($first) {
                $first->update(['is_primary' => true]);
            }
        }
    }

    /**
     * Verificar autorización sobre un lugar
     */
    private function authorizePlace($user, Place $place): void
    {
        if (!$user->isAdmin() && $place->user_id !== $user->id) {
            abort(403, 'No tienes permiso para modificar este lugar');
        }
    }

    /**
     * Formatear respuesta de lugar
     */
    private function formatPlace(Place $place, bool $includeReviews = false): array
    {
        $data = [
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
            ] : null,
            'images' => $place->images->map(fn ($img) => [
                'id' => $img->id,
                'url' => $img->url,
                'filename' => $img->filename,
                'is_primary' => $img->is_primary,
                'order' => $img->order,
            ])->values(),
            'primary_image_url' => $place->primary_image_url,
            'created_at' => $place->created_at?->toIso8601String(),
            'updated_at' => $place->updated_at?->toIso8601String(),
        ];

        if ($includeReviews && $place->relationLoaded('reviews')) {
            $data['reviews'] = $place->reviews->map(fn ($review) => [
                'id' => $review->id,
                'rating' => $review->rating,
                'comment' => $review->comment,
                'user' => $review->user ? [
                    'id' => $review->user->id,
                    'name' => $review->user->name,
                ] : null,
                'created_at' => $review->created_at?->toIso8601String(),
            ])->values();
            
            $data['average_rating'] = $place->reviews->avg('rating');
            $data['reviews_count'] = $place->reviews->count();
        }

        return $data;
    }

    /**
     * Formatear respuesta paginada de lugares
     */
    private function formatPlacesResponse($paginator): array
    {
        return [
            'data' => collect($paginator->items())->map(fn ($place) => $this->formatPlace($place)),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ];
    }
}
