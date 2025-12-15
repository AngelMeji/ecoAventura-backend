<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->enum('difficulty', ['baja', 'media', 'alta', 'experto'])->nullable();
            $table->string('duration')->nullable(); // Ej: "3 horas", "2 días"
            $table->string('best_season')->nullable(); // Ej: "Verano", "Todo el año"
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['difficulty', 'duration', 'best_season']);
        });
    }
};
