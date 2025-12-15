<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('place_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('place_id')->constrained()->onDelete('cascade');
            $table->string('path'); // Ruta relativa en storage
            $table->string('filename'); // Nombre original del archivo
            $table->boolean('is_primary')->default(false); // Imagen principal
            $table->integer('order')->default(0); // Orden de las imágenes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('place_images');
    }
};
