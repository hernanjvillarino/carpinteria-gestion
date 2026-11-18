<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('status', 20)->default('borrador'); // borrador | enviado | aceptado | rechazado | vencido
            $table->decimal('labor_cost', 14, 2)->default(0);
            $table->decimal('margin_pct', 5, 2)->default(0);
            $table->decimal('tax_pct', 5, 2)->default(21);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->date('valid_until')->nullable();
            $table->text('notes')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('accepted_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->unique(['work_order_id', 'version']);
        });

        Schema::create('quote_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete(); // NULL = ítem libre
            $table->string('description');
            $table->decimal('qty', 12, 3);
            $table->string('unit', 20);
            $table->decimal('unit_price', 14, 2); // copiado al emitir
            $table->decimal('line_total', 14, 2);
            $table->timestamps();
        });

        Schema::create('cutlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('design_id')->constrained()->cascadeOnDelete();
            $table->string('source', 20); // opencutlist_csv | manual | extension
            $table->string('source_file')->nullable();
            $table->string('status', 20)->default('borrador'); // borrador | confirmado | exportado
            $table->timestamp('exported_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });

        Schema::create('cutlist_parts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cutlist_id')->constrained()->cascadeOnDelete();
            $table->foreignId('material_id')->nullable()->constrained()->nullOnDelete();
            $table->string('source_material', 150); // nombre tal como vino del CSV
            $table->string('name', 150);
            $table->decimal('length_mm', 7, 1); // en sentido de la veta
            $table->decimal('width_mm', 7, 1);
            $table->decimal('thickness_mm', 5, 1);
            $table->unsignedSmallInteger('qty');
            $table->boolean('grain_locked')->default(false);
            foreach (['edge_l1_id', 'edge_l2_id', 'edge_w1_id', 'edge_w2_id'] as $edge) {
                $table->foreignId($edge)->nullable()->constrained('materials')->nullOnDelete();
            }
            $table->string('notes')->nullable();
            $table->timestamps();
            $table->index(['cutlist_id', 'material_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cutlist_parts');
        Schema::dropIfExists('cutlists');
        Schema::dropIfExists('quote_items');
        Schema::dropIfExists('quotes');
    }
};
