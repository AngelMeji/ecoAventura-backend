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
            if (!Schema::hasColumn('places', 'difficulty')) {
                $table->enum('difficulty', ['baja', 'media', 'alta', 'experto'])->nullable();
            }
            if (!Schema::hasColumn('places', 'duration')) {
                $table->string('duration')->nullable();
            }
            if (!Schema::hasColumn('places', 'best_season')) {
                $table->string('best_season')->nullable();
            }
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
