<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('place_images', function (Blueprint $table) {
            if (!Schema::hasColumn('place_images', 'image_path')) {
                $table->string('image_path')->after('place_id');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('place_images', function (Blueprint $table) {
            if (Schema::hasColumn('place_images', 'image_path')) {
                $table->dropColumn('image_path');
            }
        });
    }
};
