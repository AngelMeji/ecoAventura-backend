<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Place extends Model
{
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

    protected $casts = [
        'is_featured' => 'boolean',
        'latitude' => 'decimal:7',
        'longitude' => 'decimal:7',
    ];

    /* =======================
       Relaciones Eloquent
       ======================= */

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /**
     * Registros de favoritos de este lugar
     */
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    /**
     * Usuarios que marcaron este lugar como favorito
     */
    public function favoritedBy()
    {
        return $this->belongsToMany(User::class, 'favorites');
    }

    /**
     * Todas las imágenes del lugar
     */
    public function images(): HasMany
    {
        return $this->hasMany(PlaceImage::class)->orderBy('order');
    }

    /**
     * Imagen principal del lugar
     */
    public function primaryImage(): HasOne
    {
        return $this->hasOne(PlaceImage::class)->where('is_primary', true);
    }

    /* =======================
       Accessors
       ======================= */

    /**
     * Obtener la URL de la imagen principal o la primera imagen
     */
    public function getPrimaryImageUrlAttribute(): ?string
    {
        $primary = $this->primaryImage ?? $this->images->first();
        return $primary?->url;
    }

    /**
     * Obtener todas las URLs de las imágenes
     */
    public function getImageUrlsAttribute(): array
    {
        return $this->images->pluck('url')->toArray();
    }
}