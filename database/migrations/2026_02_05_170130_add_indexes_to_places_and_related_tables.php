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
            $table->index('user_id');
            $table->index('category_id');
            $table->index('status');
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->index('place_id');
            $table->index('user_id');
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('place_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
        });

        Schema::table('reviews', function (Blueprint $table) {
            $table->dropIndex(['place_id']);
            $table->dropIndex(['user_id']);
        });

        Schema::table('favorites', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['place_id']);
        });
    }
};
