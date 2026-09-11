<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->string('numero_factura')->nullable()->after('destino');
        });

        Schema::table('inventory_records', function (Blueprint $table) {
            $table->string('numero_factura')->nullable()->after('remision_despacho');
        });
    }

    public function down(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->dropColumn('numero_factura');
        });

        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropColumn('numero_factura');
        });
    }
};
