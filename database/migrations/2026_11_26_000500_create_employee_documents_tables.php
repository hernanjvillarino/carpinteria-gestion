<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Documentación del personal para entrar a los barrios cerrados:
 * qué pide cada barrio (ART, accidentes personales, DNI...) y los archivos
 * de cada empleado con su vencimiento.
 */
return new class extends Migration
{
    public function up(): void
    {
        // Personal de la carpintería. Puede tener acceso al sistema (carpintero o admin) o no (ayudantes).
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->unique()->constrained()->nullOnDelete();
            $table->string('name', 150);
            $table->string('dni', 20)->nullable();
            $table->string('cuil', 20)->nullable();
            $table->string('phone', 40)->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Tipos de documento. Si tiene barrio, es propio de ese barrio (ej. permiso o credencial del barrio).
        Schema::create('document_types', function (Blueprint $table) {
            $table->id();
            $table->string('name', 150);
            $table->boolean('requires_expiry')->default(true);
            $table->foreignId('gated_community_id')->nullable()->constrained()->cascadeOnDelete();
            $table->timestamps();
        });

        // Documentos generales que exige cada barrio.
        Schema::create('document_type_gated_community', function (Blueprint $table) {
            $table->foreignId('gated_community_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->primary(['gated_community_id', 'document_type_id']);
        });

        // Archivos de cada empleado. Se guarda el historial: vale el de vencimiento más lejano.
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('document_type_id')->constrained()->cascadeOnDelete();
            $table->string('path');
            $table->string('original_name', 255);
            $table->string('mime', 120)->nullable();
            $table->unsignedInteger('size')->default(0);
            $table->date('expires_on')->nullable();
            $table->string('notes', 255)->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('expiring_notified_at')->nullable(); // aviso "por vencer" ya enviado
            $table->timestamp('expired_notified_at')->nullable();  // aviso "vencido" ya enviado
            $table->timestamps();
            $table->index(['employee_id', 'document_type_id']);
            $table->index('expires_on');
        });

        // Lista inicial; se edita desde Barrios → Personal → Tipos de documento.
        foreach ([['ART', true], ['Seguro de accidentes personales', true], ['DNI', false], ['Certificado de antecedentes penales', true]] as [$name, $expires]) {
            DB::table('document_types')->insert(['name' => $name, 'requires_expiry' => $expires, 'created_at' => now(), 'updated_at' => now()]);
        }

        // Los carpinteros que ya existen arrancan con su ficha de personal.
        $now = now();
        DB::table('users')->where('role', 'carpintero')->orderBy('id')->get(['id', 'name', 'phone', 'is_active'])
            ->each(fn ($u) => DB::table('employees')->insert([
                'user_id' => $u->id, 'name' => $u->name, 'phone' => $u->phone, 'is_active' => $u->is_active,
                'created_at' => $now, 'updated_at' => $now,
            ]));
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('document_type_gated_community');
        Schema::dropIfExists('document_types');
        Schema::dropIfExists('employees');
    }
};
