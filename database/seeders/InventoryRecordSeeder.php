<?php

namespace Database\Seeders;

use App\Models\InventoryRecord;
use Illuminate\Database\Seeder;

class InventoryRecordSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Deliberately spans every stage of completion so each role's pending
     * queue (/inventario/{stage}) has at least one record to show.
     */
    public function run(): void
    {
        // Fully completed, all 4 stages done.
        InventoryRecord::query()->create([
            'anio' => 2026, 'mes' => 'Julio', 'fecha' => '2026-07-12', 'remision' => 'R-1042',
            'tulas' => 18, 'costal' => 2,
            'calidad_enviada' => 'Excelso', 'kg_enviados' => 1080, 'analisis_enviado_por' => 'Jorge',
            'as_env' => 92, 'pas_env' => 5, 'pg_env' => 3, 'broca_env' => 1.2, 'humedad_env' => 11.4,
            'factor_env' => 92.5, 'taza_env' => 'Cítrico, panela', 'puntaje_taza_env' => 86,
            'analisis_recibido_por' => 'Trilladora', 'kg_recibidos' => 1075,
            'as_rec' => 91, 'pas_rec' => 6, 'pg_rec' => 3, 'broca_rec' => 1.5, 'humedad_rec' => 11.6,
            'factor_rec' => 92.9, 'taza_rec' => 'Cítrico, panela', 'puntaje_taza_rec' => 85.5,
            'destino' => 'Planta Garzón', 'cliente' => 'Cooperativa del Huila', 'negocio' => 'Exportación',
            'estatus' => 'Despachado', 'existencia' => 0,
        ]);

        // General + envío + recepción done — pending Destino.
        InventoryRecord::query()->create([
            'anio' => 2026, 'mes' => 'Julio', 'fecha' => '2026-07-25', 'remision' => 'R-1051',
            'tulas' => 22, 'costal' => 0,
            'calidad_enviada' => 'Supremo', 'kg_enviados' => 1320, 'analisis_enviado_por' => 'Evelyn',
            'as_env' => 94, 'pas_env' => 4, 'pg_env' => 1.5, 'broca_env' => 0.8, 'humedad_env' => 10.9,
            'factor_env' => 90.1, 'taza_env' => 'Chocolate, caramelo', 'puntaje_taza_env' => 87.5,
            'analisis_recibido_por' => 'Calidades', 'kg_recibidos' => 1318,
            'as_rec' => 94, 'pas_rec' => 4, 'pg_rec' => 1.5, 'broca_rec' => 0.9, 'humedad_rec' => 11.0,
            'factor_rec' => 90.3, 'taza_rec' => 'Chocolate, caramelo', 'puntaje_taza_rec' => 87,
            'estatus' => 'En bodega', 'existencia' => 1318,
        ]);

        // General + envío done — pending Recepción and Destino.
        InventoryRecord::query()->create([
            'anio' => 2026, 'mes' => 'Agosto', 'fecha' => '2026-08-02', 'remision' => 'R-1070',
            'tulas' => 20, 'costal' => 1,
            'calidad_enviada' => 'Extra', 'kg_enviados' => 960, 'analisis_enviado_por' => 'Jorge',
            'as_env' => 90, 'pas_env' => 6, 'pg_env' => 2.5, 'broca_env' => 1.5, 'humedad_env' => 11.8,
            'factor_env' => 93.2, 'taza_env' => 'Frutal, dulce', 'puntaje_taza_env' => 85,
            'estatus' => 'En tránsito',
        ]);

        // Only General done — pending Envío, Recepción and Destino.
        InventoryRecord::query()->create([
            'anio' => 2026, 'mes' => 'Agosto', 'fecha' => '2026-08-03', 'remision' => 'R-1078',
            'tulas' => 10, 'costal' => 0,
            'estatus' => 'Reservado',
        ]);
    }
}
