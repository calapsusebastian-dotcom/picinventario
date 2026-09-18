<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adds the dynamic-bodegas column and migrates Bodega Especiales /
     * Bodega Almacafe into real Bodega rows, preserving every remisión's
     * current location. The old boolean columns are left untouched (not
     * dropped, not modified) — this only adds a new column and populates it.
     */
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->foreignId('bodega_actual_id')->nullable()->after('enviado_a_bodega_almacafe')
                ->constrained('bodegas')->nullOnDelete();
        });

        $especialesId = DB::table('bodegas')->insertGetId([
            'nombre' => 'Bodega Especiales',
            'slug' => 'especiales',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $almacafeId = DB::table('bodegas')->insertGetId([
            'nombre' => 'Bodega Almacafe',
            'slug' => 'almacafe',
            'activo' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('inventory_records')->where('enviado_a_bodega_especial', true)->update(['bodega_actual_id' => $especialesId]);
        DB::table('inventory_records')->where('enviado_a_bodega_almacafe', true)->update(['bodega_actual_id' => $almacafeId]);
    }

    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('bodega_actual_id');
        });

        DB::table('bodegas')->whereIn('slug', ['especiales', 'almacafe'])->delete();
    }
};
