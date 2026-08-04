<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->string('destino')->nullable()->after('remision_despacho');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->dropColumn('destino');
        });
    }
};
