<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * "Trabajos" se llaman work_orders porque Laravel ya usa la tabla "jobs" para las colas.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stages', function (Blueprint $table) {
            $table->id();
            $table->string('name', 60);
            $table->unsignedSmallInteger('position')->unique();
            $table->boolean('visible_to_client')->default(true);
            $table->timestamps();
        });

        Schema::create('work_orders', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique(); // T-2026-0042
            $table->foreignId('client_id')->constrained();
            $table->string('title', 150);
            $table->text('description')->nullable();
            $table->string('install_address')->nullable();
            $table->foreignId('stage_id')->constrained();
            $table->string('priority', 10)->default('normal'); // baja | normal | alta
            $table->date('estimated_delivery')->nullable();
            $table->date('delivered_at')->nullable();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('work_order_user', function (Blueprint $table) {
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->boolean('is_lead')->default(false);
            $table->timestamps();
            $table->primary(['work_order_id', 'user_id']);
        });

        Schema::create('work_order_updates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('from_stage_id')->nullable()->constrained('stages');
            $table->foreignId('to_stage_id')->nullable()->constrained('stages');
            $table->text('comment')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->timestamps();
            $table->index(['work_order_id', 'created_at']);
        });

        Schema::create('work_order_photos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_update_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('caption')->nullable();
            $table->boolean('visible_to_client')->default(false);
            $table->timestamps();
        });

        Schema::create('designs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('version');
            $table->string('skp_path')->nullable();
            $table->string('preview_path')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 20)->default('borrador'); // borrador | enviado | aprobado | rechazado
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users');
            $table->timestamps();
            $table->unique(['work_order_id', 'version']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('designs');
        Schema::dropIfExists('work_order_photos');
        Schema::dropIfExists('work_order_updates');
        Schema::dropIfExists('work_order_user');
        Schema::dropIfExists('work_orders');
        Schema::dropIfExists('stages');
    }
};
