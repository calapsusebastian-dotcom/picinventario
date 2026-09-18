<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class InventoryRecord extends Model
{
    protected $fillable = [
        'anio', 'mes', 'fecha', 'remision', 'tulas', 'costal', 'ubicacion', 'observacion',
        'calidad_enviada', 'kg_enviados', 'analisis_enviado_por',
        'as_env', 'pas_env', 'pg_env', 'broca_env', 'humedad_env', 'factor_env', 'taza_env', 'puntaje_taza_env',
        'analisis_recibido_por', 'kg_recibidos',
        'as_rec', 'pas_rec', 'pg_rec', 'broca_rec', 'humedad_rec', 'factor_rec', 'taza_rec', 'puntaje_taza_rec',
        'destino', 'cliente', 'negocio', 'estatus', 'existencia', 'taza_destino', 'puntaje_taza_destino',
        'imov', 'enviado_a_trilla', 'remision_envio_trilla',
        'enviado_a_despacho', 'remision_despacho', 'numero_factura', 'fecha_despacho',
        'enviado_a_bodega_especial', 'enviado_a_bodega_almacafe', 'bodega_actual_id',
    ];

    protected $casts = [
        'fecha' => 'date',
        'anio' => 'integer',
        'tulas' => 'integer',
        'costal' => 'integer',
        'imov' => 'integer',
        'enviado_a_trilla' => 'boolean',
        'enviado_a_despacho' => 'boolean',
        'fecha_despacho' => 'date',
        'enviado_a_bodega_especial' => 'boolean',
        'enviado_a_bodega_almacafe' => 'boolean',
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
        'puntaje_taza_destino' => 'decimal:2',
    ];

    /**
     * The trilla lote(s) this remisión has contributed kg to. A remisión can
     * be split across several lotes over time, independent of the
     * general/envío/recepción/destino workflow below.
     */
    public function trillas(): BelongsToMany
    {
        return $this->belongsToMany(Trilla::class, 'trilla_inventory_record')
            ->withPivot('kg_usado')
            ->withTimestamps();
    }

    /**
     * The intermediate bodega this remisión is currently sitting in (Bodega
     * Especiales, Bodega Almacafe, or any bodega created afterward). Null
     * means it's still in the main Bodega, not yet routed anywhere.
     */
    public function bodegaActual(): BelongsTo
    {
        return $this->belongsTo(Bodega::class, 'bodega_actual_id');
    }

    /**
     * Kg recibidos minus whatever has already been assigned to trilla lotes.
     * Null if this remisión hasn't gone through recepción yet.
     */
    public function kgDisponible(): ?float
    {
        if ($this->kg_recibidos === null) {
            return null;
        }

        return max(0, (float) $this->kg_recibidos - $this->kgUsadoEnTrillas());
    }

    /**
     * Existencia minus whatever has already been assigned to trilla lotes,
     * mirroring kgDisponible() so both KPIs move together as a remisión
     * gets (partially) trillada. Null if existencia hasn't been set yet.
     */
    public function existenciaDisponible(): ?float
    {
        if ($this->existencia === null) {
            return null;
        }

        return max(0, (float) $this->existencia - $this->kgUsadoEnTrillas());
    }

    /**
     * True once this remisión has been dispatched directly (skipping
     * trilla) via a remisión de despacho of its own.
     */
    public function isDespachadoDirecto(): bool
    {
        return (bool) $this->remision_despacho;
    }

    /**
     * The "Ubicación" pipeline badge shown across the Tablero and bodega
     * pages — label plus the badge colors, so this single method drives
     * every place that renders it instead of each view re-deriving its own
     * copy of this same cascade.
     *
     * @return array{label: string, bg: ?string, fg: ?string}
     */
    public function ubicacionBadge(): array
    {
        if ($this->isDespachadoDirecto()) {
            return ['label' => 'Despachado', 'bg' => '#DCF3EC', 'fg' => '#0B6B54'];
        }

        if ($this->enviado_a_despacho) {
            return ['label' => 'En despacho', 'bg' => '#E1EFFB', 'fg' => '#1D5FA8'];
        }

        $saldo = $this->kgDisponible();

        if ($saldo === null || $saldo <= 0.001) {
            return $this->trillas->isNotEmpty()
                ? ['label' => 'Trillado', 'bg' => 'var(--pic-accent-soft)', 'fg' => 'var(--pic-accent-deep)']
                : ['label' => '—', 'bg' => null, 'fg' => null];
        }

        if ($this->enviado_a_trilla) {
            return ['label' => 'En trilla', 'bg' => 'var(--pic-purple-soft)', 'fg' => 'var(--pic-purple)'];
        }

        if ($this->bodega_actual_id) {
            return [
                'label' => $this->bodegaActual?->nombre ?? 'En bodega',
                'bg' => 'var(--pic-purple-soft)',
                'fg' => 'var(--pic-purple)',
            ];
        }

        return ['label' => 'En bodega', 'bg' => 'var(--pic-amber-soft)', 'fg' => 'var(--pic-amber)'];
    }

    /** Plain-text version of ubicacionBadge() — for PDF exports and the MCP tools. */
    public function ubicacionLabel(): string
    {
        return $this->ubicacionBadge()['label'];
    }

    protected function kgUsadoEnTrillas(): float
    {
        return $this->relationLoaded('trillas')
            ? $this->trillas->sum(fn (Trilla $t) => (float) $t->pivot->kg_usado)
            : (float) $this->trillas()->sum('kg_usado');
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
