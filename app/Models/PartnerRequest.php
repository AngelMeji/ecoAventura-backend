<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PartnerRequest extends Model
{
    protected $fillable = [
        'user_id',
        'place_name',
        'place_address',
        'status',
        'user_read',
    ];

    protected $casts = [
        'user_read' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
