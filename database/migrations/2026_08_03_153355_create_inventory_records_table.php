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
        Schema::create('inventory_records', function (Blueprint $table) {
            $table->id();

            // General
            $table->unsignedSmallInteger('anio')->nullable();
            $table->string('mes')->nullable();
            $table->date('fecha')->nullable();
            $table->string('remision')->nullable();
            $table->string('empaque')->nullable();
            $table->unsignedInteger('tulas')->nullable();
            $table->unsignedInteger('costal')->nullable();

            // Envio
            $table->string('calidad_enviada')->nullable();
            $table->decimal('kg_enviados', 10, 2)->nullable();
            $table->string('analisis_enviado_por')->nullable();
            $table->decimal('as_env', 5, 2)->nullable();
            $table->decimal('pas_env', 5, 2)->nullable();
            $table->decimal('pg_env', 5, 2)->nullable();
            $table->decimal('broca_env', 5, 2)->nullable();
            $table->decimal('humedad_env', 5, 2)->nullable();
            $table->decimal('factor_env', 6, 2)->nullable();
            $table->string('taza_env')->nullable();
            $table->decimal('puntaje_taza_env', 5, 2)->nullable();

            // Recepcion
            $table->string('analisis_recibido_por')->nullable();
            $table->decimal('kg_recibidos', 10, 2)->nullable();
            $table->decimal('as_rec', 5, 2)->nullable();
            $table->decimal('pas_rec', 5, 2)->nullable();
            $table->decimal('pg_rec', 5, 2)->nullable();
            $table->decimal('broca_rec', 5, 2)->nullable();
            $table->decimal('humedad_rec', 5, 2)->nullable();
            $table->decimal('factor_rec', 6, 2)->nullable();
            $table->string('taza_rec')->nullable();
            $table->decimal('puntaje_taza_rec', 5, 2)->nullable();

            // Destino
            $table->string('destino')->nullable();
            $table->string('cliente')->nullable();
            $table->string('negocio')->nullable();
            $table->string('estatus')->default('En bodega');
            $table->decimal('existencia', 10, 2)->nullable();

            // Despacho
            $table->date('fecha_despacho')->nullable();
            $table->string('remision_despacho')->nullable();
            $table->decimal('factor_despachado', 6, 2)->nullable();
            $table->string('imov_agencia')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_records');
    }
};
