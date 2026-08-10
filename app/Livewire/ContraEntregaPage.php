<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use Livewire\Component;

class ContraEntregaPage extends Component
{
    public string $search = '';

    public ?int $expandedRow = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    /**
     * Difference recibido - enviado for a pair of nullable numeric fields.
     * Null whenever either side hasn't been filled in yet, so an
     * incomplete pair never masquerades as a zero difference.
     */
    private function diff(?string $env, ?string $rec): ?float
    {
        if ($env === null || $env === '' || $rec === null || $rec === '') {
            return null;
        }

        return (float) $rec - (float) $env;
    }

    public function render()
    {
        $comparaciones = InventoryRecord::query()
            ->whereNotNull('kg_enviados')
            ->whereNotNull('kg_recibidos')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->filter(function (InventoryRecord $r) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [$r->remision, $r->cliente, $r->calidad_enviada, $r->ubicacion]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->map(function (InventoryRecord $r) {
                $diffKg = $this->diff((string) $r->kg_enviados, (string) $r->kg_recibidos);
                $diffKgPct = $diffKg !== null && (float) $r->kg_enviados > 0
                    ? $diffKg / (float) $r->kg_enviados * 100
                    : null;

                return [
                    'record' => $r,
                    'diff_kg' => $diffKg,
                    'diff_kg_pct' => $diffKgPct,
                    'diff_factor' => $this->diff($r->factor_env, $r->factor_rec),
                    'diff_humedad' => $this->diff($r->humedad_env, $r->humedad_rec),
                    'diff_as' => $this->diff($r->as_env, $r->as_rec),
                    'diff_pas' => $this->diff($r->pas_env, $r->pas_rec),
                    'diff_pg' => $this->diff($r->pg_env, $r->pg_rec),
                    'diff_broca' => $this->diff($r->broca_env, $r->broca_rec),
                    'diff_puntaje_taza' => $this->diff($r->puntaje_taza_env, $r->puntaje_taza_rec),
                    'taza_coincide' => $r->taza_env && $r->taza_rec ? $r->taza_env === $r->taza_rec : null,
                ];
            })
            ->values();

        $kgEnviadosTotal = (float) $comparaciones->sum(fn (array $c) => (float) $c['record']->kg_enviados);
        $kgRecibidosTotal = (float) $comparaciones->sum(fn (array $c) => (float) $c['record']->kg_recibidos);
        $diffKgTotal = $kgRecibidosTotal - $kgEnviadosTotal;

        $totales = [
            'kg_enviados' => $kgEnviadosTotal,
            'kg_recibidos' => $kgRecibidosTotal,
            'diff_kg' => $diffKgTotal,
            'diff_kg_pct' => $kgEnviadosTotal > 0 ? $diffKgTotal / $kgEnviadosTotal * 100 : null,
        ];

        return view('livewire.contra-entrega-page', [
            'comparaciones' => $comparaciones,
            'totales' => $totales,
        ]);
    }
}
