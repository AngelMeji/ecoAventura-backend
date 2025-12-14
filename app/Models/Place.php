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
        'status'
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

    /* Accessors */

    /* Indica si el lugar es favorito del usuario autenticado */
    public function getIsFavoriteAttribute(): bool
    {
        if (!auth()->check()) {
            return false;
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