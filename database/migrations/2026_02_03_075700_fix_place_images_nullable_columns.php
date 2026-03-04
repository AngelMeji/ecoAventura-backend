<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations - hacer columnas nullable para evitar errores
     */
    public function up(): void
    {
        Schema::table('place_images', function (Blueprint $table) {
            // Hacer las columnas extra nullable
            if (Schema::hasColumn('place_images', 'path')) {
                $table->string('path')->nullable()->change();
            }
            if (Schema::hasColumn('place_images', 'filename')) {
                $table->string('filename')->nullable()->change();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('place_images', function (Blueprint $table) {
            if (Schema::hasColumn('place_images', 'path')) {
                $table->string('path')->nullable(false)->change();
            }
            if (Schema::hasColumn('place_images', 'filename')) {
                $table->string('filename')->nullable(false)->change();
            }
        });
    }
};
