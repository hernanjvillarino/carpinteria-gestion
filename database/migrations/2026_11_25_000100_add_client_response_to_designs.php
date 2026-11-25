<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->timestamp('sent_at')->nullable()->after('status');
            $table->text('client_feedback')->nullable()->after('sent_at'); // cambios que pide el cliente
            $table->timestamp('responded_at')->nullable()->after('client_feedback');
        });
    }

    public function down(): void
    {
        Schema::table('designs', function (Blueprint $table) {
            $table->dropColumn(['sent_at', 'client_feedback', 'responded_at']);
        });
    }
};
