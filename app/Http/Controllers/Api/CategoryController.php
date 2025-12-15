<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Category;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    /**
     * LISTAR CATEGORÍAS (PÚBLICO)
     * GET /api/categories
     */
    public function index()
    {
        $icons = [
            'montana' => '🏔️',
            'playa' => '🏖️',
            'familia' => '👨‍👩‍👧‍👦',
            'pareja' => '💑',
            'aventura' => '🧗',
            'gastronomia' => '🍽️',
            'cultura' => '🏛️',
            'relax' => '🧘',
        ];

        // 1. Quitamos withAvg('places', 'rating') que causa el error 500
        $categories = Category::withCount('places')->get();

        return $categories->map(function ($category) use ($icons) {
            return [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
                'count' => $category->places_count, // El frontend debe leer esto
                'avgRating' => 4.5, // Valor temporal para evitar errores hasta que arregles la relación
                'icon' => $icons[$category->slug] ?? '🗺️'
            ];
        });
    }

    /**
     * CREAR CATEGORÍA (ADMIN)
     * POST /api/categories
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name'
        ]);

        $category = Category::create([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return response()->json([
            'message' => 'Categoría creada correctamente',
            'category' => $category
        ], 201);
    }

    /**
     * ACTUALIZAR CATEGORÍA (ADMIN)
     * PUT /api/categories/{id}
     */
    public function update(Request $request, int $id)
    {
        $category = Category::findOrFail($id);

        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id
        ]);

        $category->update([
            'name' => $request->name,
            'slug' => Str::slug($request->name),
        ]);

        return response()->json([
            'message' => 'Categoría actualizada',
            'category' => $category
        ]);
    }

    /**
     * ELIMINAR CATEGORÍA (ADMIN)
     * DELETE /api/categories/{id}
     */
    public function destroy(Request $request, int $id)
    {
        if (!$request->user()->isAdmin()) {
            return response()->json([
                'message' => 'Solo el administrador puede eliminar categorías'
            ], 403);
        }

        Category::findOrFail($id)->delete();

        return response()->json([
            'message' => 'Categoría eliminada'
        ]);
    }
}
