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
        Schema::create('trilla_productos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('trilla_id')->constrained('trillas')->cascadeOnDelete();
            $table->string('nombre');
            $table->decimal('kg', 10, 2)->nullable();
            $table->decimal('factor', 6, 2)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('trilla_productos');
    }
};
