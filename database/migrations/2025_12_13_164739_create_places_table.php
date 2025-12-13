<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    
    public function up(): void
    {
        Schema::create('places', function (Blueprint $table) {
            $table->id();

            // Relación con el usuario que creó el lugar
            $table->foreignId('user_id')->constrained()->onDelete('cascade');

            // Relación con la categoría
            $table->foreignId('category_id')->constrained()->onDelete('cascade');

            // Información principal
            $table->string('name');
            $table->string('slug')->unique();
            $table->string('short_description');
            $table->text('description')->nullable();
            $table->string('address')->nullable();

            // Coordenadas para el mapa
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();

            // Estados y flags
            $table->boolean('is_featured')->default(false);
            $table->enum('status', ['pending', 'approved', 'rejected', 'needs_fix'])
                  ->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('places');
    }
};
