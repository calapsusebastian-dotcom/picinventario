<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->string('taza_destino')->nullable()->after('existencia');
            $table->decimal('puntaje_taza_destino', 5, 2)->nullable()->after('taza_destino');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropColumn(['taza_destino', 'puntaje_taza_destino']);
        });
    }
};
