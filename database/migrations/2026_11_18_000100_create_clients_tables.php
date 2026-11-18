<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gated_communities', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('city', 120)->nullable();
            $table->text('access_notes')->nullable(); // requisitos de ingreso: ART, seguro, horarios
            $table->boolean('show_on_website')->default(false);
            $table->timestamps();
        });

        Schema::create('clients', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('tax_id', 20)->nullable(); // CUIT / DNI
            $table->string('address')->nullable();
            $table->foreignId('gated_community_id')->nullable()->constrained()->nullOnDelete();
            $table->string('lot', 30)->nullable(); // lote / unidad
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('email', 190)->nullable();
            $table->string('phone', 40)->nullable();
            $table->foreignId('gated_community_id')->nullable()->constrained()->nullOnDelete();
            $table->string('furniture_type', 80)->nullable();
            $table->text('message');
            $table->string('status', 20)->default('nuevo'); // nuevo | contactado | convertido | descartado
            $table->foreignId('client_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
        Schema::dropIfExists('clients');
        Schema::dropIfExists('gated_communities');
    }
};
