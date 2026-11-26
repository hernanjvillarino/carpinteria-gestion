<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Devolución del cliente al terminar el trabajo: un puntaje de 1 a 5 por rubro.
        Schema::create('work_order_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->unique()->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained(); // el cliente que calificó
            $table->unsignedTinyInteger('score_work');
            $table->unsignedTinyInteger('score_materials');
            $table->unsignedTinyInteger('score_installation');
            $table->unsignedTinyInteger('score_staff');
            $table->unsignedTinyInteger('score_service');
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        Schema::create('work_order_rating_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_rating_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_rating_photos');
        Schema::dropIfExists('work_order_ratings');
    }
};
