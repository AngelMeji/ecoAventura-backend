<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Review extends Model
{
    protected $fillable = [
        'user_id',
        'place_id',
        'rating',
        'comment',
        'approved',
        'is_hidden'
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function place(): BelongsTo
    {
        return $this->belongsTo(Place::class);
    }

    /**
     * Accessor para ocultar el comentario si está baneado por moderación.
     * Permite que la calificación (estrellas) siga siendo pública pero protege el texto.
     */
    protected function getCommentAttribute($value)
    {
        if ($this->is_hidden) {
            // Si el usuario es admin, quizás quiera ver el comentario original en el panel de admin.
            // Pero para las rutas públicas / listados normales, lo ocultamos.
            // Para simplicidad en este paso, lo ocultamos para todos excepto si explícitamente se maneja en el controlador de admin.
            return "Este comentario ha sido ocultado por moderación.";
        }
        return $value;
    }
}