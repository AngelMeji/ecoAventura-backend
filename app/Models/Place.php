<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'category_id',
        'name',
        'slug',
        'short_description',
        'description',
        'address',
        'latitude',
        'longitude',
        'is_featured',
        'status',
        'difficulty',
        'duration',
        'best_season',
    ];

    /* Atributos calculados que se agregan al JSON */
    protected $appends = ['is_favorite', 'average_rating'];

    /* Relaciones Eloquent */

    // Creador del lugar (socio / admin)
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // Categoría del lugar
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    // Reseñas del lugar
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    // Favoritos del lugar
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // Favoritos del lugar (Pivot logic for stats)
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites', 'place_id', 'user_id');
    }

    // Imágenes del lugar
    public function images(): HasMany
    {
        return $this->hasMany(PlaceImage::class)->orderBy('is_primary', 'desc');
    }

    /* Accessors */

    /* Indica si el lugar es favorito del usuario autenticado */
    public function getIsFavoriteAttribute($value): bool
    {
        // Si viene del query con alias (favorites as is_favorite)
        // El valor de $value ya será el resultado de la subconsulta (1 o 0)
        // O será el valor de la columna de la base de datos (0 por defecto)

        // Si la relación ya está cargada y no hay un alias que lo sobreescriba explícitamente a true
        if (!$value && $this->relationLoaded('favorites')) {
            $user = auth('sanctum')->user() ?? auth()->user();
            if ($user) {
                return $this->favorites->contains('user_id', $user->id);
            }
        }

        return (bool) $value;
    }

    /* Promedio de calificación del lugar */
    public function getAverageRatingAttribute($value): float
    {
        // Si viene del query con withAvg (reviews_avg_rating)
        if (array_key_exists('reviews_avg_rating', $this->attributes) && $this->attributes['reviews_avg_rating'] !== null) {
            return round((float) $this->attributes['reviews_avg_rating'], 1);
        }

        // Si la relación ya está cargada
        if ($this->relationLoaded('reviews')) {
            $avg = $this->reviews->avg('rating');
            return round((float) ($avg ?? 0), 1);
        }

        // Fallback al valor real de la columna en la BD
        return round((float) ($value ?? 0), 1);
    }
}