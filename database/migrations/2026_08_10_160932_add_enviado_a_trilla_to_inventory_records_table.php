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
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->boolean('enviado_a_trilla')->default(false)->after('imov');
        });

        // Anything that already has trilla history was obviously sent at
        // some point — don't make it disappear from Trilla's pool now that
        // the gate exists.
        DB::table('inventory_records')
            ->whereIn('id', DB::table('trilla_inventory_record')->select('inventory_record_id'))
            ->update(['enviado_a_trilla' => true]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropColumn('enviado_a_trilla');
        });
    }
};
