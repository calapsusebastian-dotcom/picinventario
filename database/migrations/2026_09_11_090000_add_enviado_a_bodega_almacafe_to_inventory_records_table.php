<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->boolean('enviado_a_bodega_almacafe')->default(false)->after('enviado_a_bodega_especial');
            $table->index('enviado_a_bodega_almacafe');
        });
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropIndex(['enviado_a_bodega_almacafe']);
            $table->dropColumn('enviado_a_bodega_almacafe');
        });
    }
};
