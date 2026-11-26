<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Plazo pactado con el cliente: la entrega estimada se calcula como started_on + lead_time_days.
        Schema::table('work_orders', function (Blueprint $table) {
            $table->unsignedSmallInteger('lead_time_days')->default(30)->after('priority');
            $table->date('started_on')->nullable()->after('lead_time_days');
        });

        // Indicaciones que el dueño precarga una vez y tilda al asignar un trabajo.
        Schema::create('instruction_templates', function (Blueprint $table) {
            $table->id();
            $table->string('text', 255);
            $table->unsignedSmallInteger('position')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tareas de cada trabajo: salen de las indicaciones tildadas o de texto libre.
        Schema::create('work_order_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('work_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('instruction_template_id')->nullable()->constrained()->nullOnDelete();
            $table->string('text', 500);
            $table->unsignedSmallInteger('position')->default(0);
            $table->timestamp('done_at')->nullable();
            $table->foreignId('done_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('work_order_tasks');
        Schema::dropIfExists('instruction_templates');
        Schema::table('work_orders', function (Blueprint $table) {
            $table->dropColumn(['lead_time_days', 'started_on']);
        });
    }
};
