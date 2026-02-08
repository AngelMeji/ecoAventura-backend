<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PlaceImage extends Model
{
    protected $fillable = ['place_id', 'image_path'];

    // Append 'full_url' to JSON
    protected $appends = ['full_url'];

    public function getFullUrlAttribute()
    {
        if (str_starts_with($this->image_path, 'http')) {
            return $this->image_path;
        }
        return asset('storage/' . $this->image_path);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
