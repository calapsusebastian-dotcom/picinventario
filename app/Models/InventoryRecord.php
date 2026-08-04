<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryRecord extends Model
{
    protected $fillable = [
        'anio', 'mes', 'fecha', 'remision', 'tulas', 'costal',
        'calidad_enviada', 'kg_enviados', 'analisis_enviado_por',
        'as_env', 'pas_env', 'pg_env', 'broca_env', 'humedad_env', 'factor_env', 'taza_env', 'puntaje_taza_env',
        'analisis_recibido_por', 'kg_recibidos',
        'as_rec', 'pas_rec', 'pg_rec', 'broca_rec', 'humedad_rec', 'factor_rec', 'taza_rec', 'puntaje_taza_rec',
        'destino', 'cliente', 'negocio', 'estatus', 'existencia',
        'imov',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anio' => 'integer',
        'tulas' => 'integer',
        'costal' => 'integer',
        'imov' => 'integer',
        'kg_enviados' => 'decimal:2',
        'as_env' => 'decimal:2',
        'pas_env' => 'decimal:2',
        'pg_env' => 'decimal:2',
        'broca_env' => 'decimal:2',
        'humedad_env' => 'decimal:2',
        'factor_env' => 'decimal:2',
        'puntaje_taza_env' => 'decimal:2',
        'kg_recibidos' => 'decimal:2',
        'as_rec' => 'decimal:2',
        'pas_rec' => 'decimal:2',
        'pg_rec' => 'decimal:2',
        'broca_rec' => 'decimal:2',
        'humedad_rec' => 'decimal:2',
        'factor_rec' => 'decimal:2',
        'puntaje_taza_rec' => 'decimal:2',
        'existencia' => 'decimal:2',
    ];

    /**
     * The trilla lote this remisión was grouped into, if any. Independent
     * of the general/envío/recepción/destino workflow below.
     */
    public function trilla(): BelongsTo
    {
        return $this->belongsTo(Trilla::class);
    }

    /**
     * Which of the 5 workflow stages (general/envio/recepcion/destino/imov) are complete.
     */
    public function stageStatus(): array
    {
        return [
            (bool) ($this->remision && $this->fecha),
            (bool) $this->kg_enviados,
            (bool) $this->kg_recibidos,
            (bool) $this->cliente,
            (bool) $this->imov,
        ];
    }
}
