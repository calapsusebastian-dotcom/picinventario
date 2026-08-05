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
        Schema::create('trilla_inventory_record', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trilla_id')->constrained()->cascadeOnDelete();
            $table->foreignId('inventory_record_id')->constrained()->cascadeOnDelete();
            $table->decimal('kg_usado', 10, 2);
            $table->timestamps();

            $table->unique(['trilla_id', 'inventory_record_id']);
        });

        // Every remision currently linked to a trilla was fully consumed by
        // it, so carry that over as a pivot row using its full kg_recibidos.
        DB::table('inventory_records')
            ->whereNotNull('trilla_id')
            ->select('id', 'trilla_id', 'kg_recibidos')
            ->orderBy('id')
            ->each(function ($record) {
                DB::table('trilla_inventory_record')->insert([
                    'trilla_id' => $record->trilla_id,
                    'inventory_record_id' => $record->id,
                    'kg_usado' => $record->kg_recibidos ?? 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            });

        Schema::table('inventory_records', function (Blueprint $table) {
            $table->dropConstrainedForeignId('trilla_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('inventory_records', function (Blueprint $table) {
            $table->foreignId('trilla_id')->nullable()->after('id')->constrained('trillas')->nullOnDelete();
        });

        DB::table('trilla_inventory_record')
            ->select('trilla_id', 'inventory_record_id')
            ->orderBy('id')
            ->each(function ($pivot) {
                DB::table('inventory_records')
                    ->where('id', $pivot->inventory_record_id)
                    ->update(['trilla_id' => $pivot->trilla_id]);
            });

        Schema::dropIfExists('trilla_inventory_record');
    }
};
