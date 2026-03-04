<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('places', function (Blueprint $table) {
            if (!Schema::hasColumn('places', 'average_rating')) {
                $table->decimal('average_rating', 3, 1)->default(0)->after('status');
            }
            if (!Schema::hasColumn('places', 'is_favorite')) {
                $table->boolean('is_favorite')->default(false)->after('is_featured');
            }
        });
    }

    public function down(): void
    {
        Schema::table('places', function (Blueprint $table) {
            $table->dropColumn(['average_rating', 'is_favorite']);
        });
    }
};
