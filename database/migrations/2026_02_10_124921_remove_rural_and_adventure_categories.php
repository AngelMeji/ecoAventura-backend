<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\Category;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Category::whereIn('slug', ['turismo-rural', 'aventura'])->delete();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // No se requiere reversión para esta eliminación específica solicitada
    }
};
