<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * MIGRACIÓN DE ÍNDICES COMPUESTOS PARA RENDIMIENTO
 *
 * Estos índices aceleran las consultas más frecuentes del sistema.
 * Un índice compuesto es MUCHO más eficiente que dos índices simples
 * cuando ambas columnas van juntas en una cláusula WHERE u ORDER BY.
 */
return new class extends Migration
{
    public function up(): void
    {
        // -------------------------------------------------------
        // TABLA: places
        // -------------------------------------------------------
        Schema::table('places', function (Blueprint $table) {

            // Índice más importante: listado público
            // WHERE status = 'approved' ORDER BY created_at DESC
            // Cubre /api/places (home), /api/places?category=... etc.
            $table->index(['status', 'created_at'], 'places_status_created_at_idx');

            // Índice para dashboard de socio
            // WHERE user_id = X AND status = 'approved'
            $table->index(['user_id', 'status'], 'places_user_status_idx');

            // Índice para lugares destacados
            // WHERE is_featured = 1 AND status = 'approved'
            $table->index(['is_featured', 'status'], 'places_featured_status_idx');

            // Índice para búsqueda por slug (ya es único, por si acaso)
            // WHERE slug = '...'
            // (slug ya tiene unique, que implica un índice, no se necesita duplicar)
        });

        // -------------------------------------------------------
        // TABLA: partner_requests
        // -------------------------------------------------------
        Schema::table('partner_requests', function (Blueprint $table) {

            // Para notificaciones del usuario: WHERE user_id = X AND status != 'pending' AND user_read = false
            $table->index(['user_id', 'status', 'user_read'], 'partner_requests_user_status_read_idx');

            // Para notificaciones del admin: WHERE status = 'pending' (COUNT)
            $table->index(['status'], 'partner_requests_status_idx');
        });

        // -------------------------------------------------------
        // TABLA: favorites
        // -------------------------------------------------------
        Schema::table('favorites', function (Blueprint $table) {
            // Índice compuesto para verificar si un lugar es favorito de un usuario
            // WHERE user_id = X AND place_id = Y
            // Ya hay índices simples, pero el compuesto es más eficiente para esta consulta
            $table->index(['user_id', 'place_id'], 'favorites_user_place_idx');
        });

        // -------------------------------------------------------
        // TABLA: reviews
        // -------------------------------------------------------
        Schema::table('reviews', function (Blueprint $table) {
            // Para el promedio de rating por lugar
            // WHERE place_id = X (AVG de rating)
            $table->index(['place_id', 'rating'], 'reviews_place_rating_idx');

            // Para ocultar comentarios: WHERE place_id = X AND is_hidden = false
            $table->index(['place_id', 'is_hidden'], 'reviews_place_hidden_idx');
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex('places_status_created_at_idx');
            $table->dropIndex('places_user_status_idx');
            $table->dropIndex('places_featured_status_idx');
        });

        Schema::table('partner_requests', function (Blueprint $table) {
            $table->dropIndex('partner_requests_user_status_read_idx');
            $table->dropIndex('partner_requests_status_idx');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex('favorites_user_place_idx');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex('reviews_place_rating_idx');
            $table->dropIndex('reviews_place_hidden_idx');
        });
    }
};
