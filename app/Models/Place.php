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
        return $this->hasMany(PlaceImage::class);
    }

    /* Accessors */

    /* Indica si el lugar es favorito del usuario autenticado */
    public function getIsFavoriteAttribute(): bool
    {
        // Optimización: si se usó withExists('favorites')
        if (array_key_exists('favorites_exists', $this->attributes)) {
            return $this->attributes['favorites_exists'];
        }

        if (!auth()->check()) {
            return false;
        }

        // Optimización: si la relación ya está cargada
        if ($this->relationLoaded('favorites')) {
            return $this->favorites->contains('user_id', auth()->id());
        }

        return $this->favorites()
            ->where('user_id', auth()->id())
            ->exists();
    }

    /* Promedio de calificación del lugar */
    public function getAverageRatingAttribute(): float
    {
        if ($this->relationLoaded('reviews')) {
            return round($this->reviews->avg('rating'), 1);
        }

        return round($this->reviews()->avg('rating') ?? 0, 1);
    }
}