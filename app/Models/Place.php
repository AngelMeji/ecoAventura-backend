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
    public function getIsFavoriteAttribute(): bool
    {
        // 1. Si viene del query con alias (PlaceController)
        if (array_key_exists('is_favorite', $this->attributes)) {
            return (bool) $this->attributes['is_favorite'];
        }

        // 2. Si viene del query standard (withExists)
        if (array_key_exists('favorites_exists', $this->attributes)) {
            return (bool) $this->attributes['favorites_exists'];
        }

        // 3. Fallback: verificar autenticación (soporte Sanctum y Web)
        $user = auth('sanctum')->user() ?? auth()->user();

        if (!$user) {
            return false;
        }

        // 4. Si la relación ya está cargada
        if ($this->relationLoaded('favorites')) {
            return $this->favorites->contains('user_id', $user->id);
        }

        // 5. Consulta directa
        return $this->favorites()
            ->where('user_id', $user->id)
            ->exists();
    }

    /* Promedio de calificación del lugar */
    /* Promedio de calificación del lugar */
    public function getAverageRatingAttribute(): float
    {
        // 1. Si viene del query con withAvg (PlaceController / AdminController)
        if (array_key_exists('reviews_avg_rating', $this->attributes)) {
            return round((float) $this->attributes['reviews_avg_rating'], 1);
        }

        // 2. Si la relación ya está cargada
        if ($this->relationLoaded('reviews')) {
            return round($this->reviews->avg('rating'), 1);
        }

        // 3. Fallback: consulta directa (evitar si es posible)
        return round($this->reviews()->avg('rating') ?? 0, 1);
    }
}