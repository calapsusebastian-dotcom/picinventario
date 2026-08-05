<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->timestamp('despachado_at')->nullable()->after('destino');
        });

        // Best-effort backfill for products already despachados before this
        // column existed: their updated_at is the closest approximation.
        DB::table('trilla_productos')
            ->whereNotNull('remision_despacho')
            ->update(['despachado_at' => DB::raw('updated_at')]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('trilla_productos', function (Blueprint $table) {
            $table->dropColumn('despachado_at');
        });
    }
};
