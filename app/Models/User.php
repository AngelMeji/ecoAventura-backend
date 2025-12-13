<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'bio',
        'avatar',
        'notifications',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'notifications' => 'boolean',
        ];
    }

    /* =======================
       Relaciones Eloquent
       ======================= */

    // Lugares creados (socio / admin)
    public function places(): HasMany
    {
        return $this->hasMany(Place::class);
    }

    // Favoritos del usuario
    public function favorites(): HasMany
    {
        return $this->hasMany(Favorite::class);
    }

    // Reseñas escritas
    public function reviews(): HasMany
    {
        return $this->hasMany(Review::class);
    }

    /* =======================
       Helpers de rol
       ======================= */

    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    public function isPartner(): bool
    {
        return $this->role === 'partner';
    }

    public function isUser(): bool
    {
        return $this->role === 'user';
    }
}