<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Listar todas las categorías
     */
    public function index()
    {
        $categories = Category::withCount('places')
            ->orderBy('name')
            ->get();

        return response()->json([
            'data' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug ?? null,
                'places_count' => $cat->places_count,
            ]),
        ]);
    }

    /**
     * Mostrar una categoría específica con sus lugares
     */
    public function show(Category $category)
    {
        $category->loadCount('places');

        return response()->json([
            'data' => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug ?? null,
                'places_count' => $category->places_count,
            ],
        ]);
    }
}
