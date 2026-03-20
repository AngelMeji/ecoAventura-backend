<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Illuminate\Database\Eloquent\Relations\HasMany;
use App\Notifications\ResetPasswordNotification;

class User extends Authenticatable implements MustVerifyEmail
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

    protected $appends = ['full_avatar'];

    public function getFullAvatarAttribute()
    {
        if (!$this->avatar)
            return null;
        if (str_starts_with($this->avatar, 'http'))
            return $this->avatar;
        return asset('storage/' . $this->avatar);
    }

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

    /* =======================
       Password Reset
       ======================= */

    // Sobrescribir para que el email apunte al frontend
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    // Sobrescribir para que el email de verificación apunte al frontend
    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new \App\Notifications\VerifyEmailNotification);
    }
}