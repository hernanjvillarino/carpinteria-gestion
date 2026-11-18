<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->string('phone', 40)->nullable();
            $table->string('email', 190)->nullable();
            $table->timestamps();
        });

        Schema::create('material_categories', function (Blueprint $table) {
            $table->id();
            $table->string('name', 80)->unique(); // placas, cantos, herrajes, insumos
            $table->timestamps();
        });

        Schema::create('materials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_category_id')->constrained();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->string('sku', 40)->nullable()->unique();
            $table->string('name', 150);
            $table->string('unit', 10); // placa | m | m2 | unidad | kg | litro
            $table->decimal('thickness_mm', 5, 1)->nullable();
            $table->unsignedInteger('sheet_length_mm')->nullable();
            $table->unsignedInteger('sheet_width_mm')->nullable();
            $table->boolean('has_grain')->default(false);
            $table->string('cutlist_alias', 150)->nullable()->index(); // nombre del material en SketchUp / OpenCutList
            $table->decimal('stock_qty', 12, 3)->default(0);
            $table->decimal('min_stock', 12, 3)->default(0);
            $table->decimal('current_price', 14, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('material_prices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 14, 2);
            $table->date('valid_from');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index(['material_id', 'valid_from']);
        });

        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('material_id')->constrained();
            $table->foreignId('work_order_id')->nullable()->constrained()->nullOnDelete();
            $table->string('type', 10); // ingreso | consumo | ajuste
            $table->decimal('qty', 12, 3); // positivo ingresa, negativo egresa
            $table->decimal('unit_cost', 14, 2)->nullable();
            $table->string('note')->nullable();
            $table->foreignId('user_id')->constrained();
            $table->timestamps();
            $table->index(['material_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
        Schema::dropIfExists('material_prices');
        Schema::dropIfExists('materials');
        Schema::dropIfExists('material_categories');
        Schema::dropIfExists('suppliers');
    }
};
