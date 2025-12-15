<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class PlaceImage extends Model
{
    protected $fillable = [
        'place_id',
        'path',
        'filename',
        'is_primary',
        'order',
    ];

    protected $casts = [
        'is_primary' => 'boolean',
        'order' => 'integer',
    ];

    /**
     * Relación con el lugar
     */
    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Obtener la URL completa de la imagen
     */
    public function getUrlAttribute(): string
    {
        return Storage::disk('public')->url($this->path);
    }

    /**
     * Eliminar el archivo de storage al borrar el registro
     */
    protected static function booted(): void
    {
        static::deleting(function (PlaceImage $image) {
            Storage::disk('public')->delete($image->path);
        });
    }
}
