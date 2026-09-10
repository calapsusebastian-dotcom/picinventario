<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->index('fecha');
            $table->index('cliente');
            $table->index('remision');
            $table->index('anio');
            $table->index('estatus');
            $table->index('enviado_a_trilla');
            $table->index('enviado_a_despacho');
            $table->index('enviado_a_bodega_especial');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropIndex(['fecha']);
            $table->dropIndex(['cliente']);
            $table->dropIndex(['remision']);
            $table->dropIndex(['anio']);
            $table->dropIndex(['estatus']);
            $table->dropIndex(['enviado_a_trilla']);
            $table->dropIndex(['enviado_a_despacho']);
            $table->dropIndex(['enviado_a_bodega_especial']);
        });
    }
};
