<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Purely additive: lets a remisión optionally belong to a trilla lote
     * (grouping several remisiones together) without touching any of the
     * existing general/envío/recepción/destino fields.
     */
    public function up(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->foreignId('trilla_id')->nullable()->after('id')->constrained('trillas')->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trilla_id');
        });
    }
};
