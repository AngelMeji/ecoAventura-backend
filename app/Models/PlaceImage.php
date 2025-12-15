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
        return asset('storage/' . $this->image_path);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }
}
