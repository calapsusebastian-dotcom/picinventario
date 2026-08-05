<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\TrillaProducto;
use Illuminate\Support\Facades\DB;
use Livewire\Component;

class InformesPage extends Component
{
    public string $search = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function render()
    {
        $records = InventoryRecord::with('trillas')->get();
        $productos = TrillaProducto::with('trilla.inventoryRecords')->get();
        $despachados = $productos->whereNotNull('remision_despacho');

        $totales = [
            'kg_enviados' => (float) $records->sum('kg_enviados'),
            'kg_recibidos' => (float) $records->sum('kg_recibidos'),
            'kg_trillados' => (float) DB::table('trilla_inventory_record')->sum('kg_usado'),
            'kg_despachados' => (float) $despachados->sum('kg'),
            'existencia' => (float) $records->sum(fn (InventoryRecord $r) => $r->existenciaDisponible() ?? 0),
        ];

        $remisiones = $records
            ->filter(function (InventoryRecord $r) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $r->remision, $r->cliente, $r->destino, $r->calidad_enviada,
                ]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->sortByDesc(fn (InventoryRecord $r) => ($r->fecha?->format('Y-m-d') ?? '').'-'.str_pad((string) $r->id, 10, '0', STR_PAD_LEFT))
            ->values();

        return view('livewire.informes-page', [
            'totales' => $totales,
            'porMes' => $this->buildPorMes($records),
            'porCliente' => $this->buildPorCliente($despachados),
            'porProducto' => $this->buildPorProducto($productos),
            'remisiones' => $remisiones,
        ]);
    }

    /**
     * Kg enviados/recibidos by the remisión's own fecha, kg trillados by the
     * trilla lote's fecha, kg despachados by when the despacho was recorded —
     * each metric moves by whichever event actually happened that month.
     */
    protected function buildPorMes($records): array
    {
        $meses = [];

        $touch = function (string $key) use (&$meses) {
            $meses[$key] ??= ['mes' => $key, 'kg_enviados' => 0.0, 'kg_recibidos' => 0.0, 'kg_trillados' => 0.0, 'kg_despachados' => 0.0];
        };

        foreach ($records as $r) {
            if (! $r->fecha) {
                continue;
            }

            $key = $r->fecha->format('Y-m');
            $touch($key);
            $meses[$key]['kg_enviados'] += (float) $r->kg_enviados;
            $meses[$key]['kg_recibidos'] += (float) $r->kg_recibidos;
        }

        $trillaKg = DB::table('trilla_inventory_record')
            ->join('trillas', 'trillas.id', '=', 'trilla_inventory_record.trilla_id')
            ->whereNotNull('trillas.fecha')
            ->selectRaw("DATE_FORMAT(trillas.fecha, '%Y-%m') as mes, SUM(trilla_inventory_record.kg_usado) as kg")
            ->groupBy('mes')
            ->get();

        foreach ($trillaKg as $row) {
            $touch($row->mes);
            $meses[$row->mes]['kg_trillados'] += (float) $row->kg;
        }

        $despachoKg = DB::table('trilla_productos')
            ->whereNotNull('despachado_at')
            ->selectRaw("DATE_FORMAT(despachado_at, '%Y-%m') as mes, SUM(kg) as kg")
            ->groupBy('mes')
            ->get();

        foreach ($despachoKg as $row) {
            $touch($row->mes);
            $meses[$row->mes]['kg_despachados'] += (float) $row->kg;
        }

        ksort($meses);

        // Keep only the last 12 months that have any activity, so the chart
        // doesn't stretch across years of near-empty history.
        return array_slice(array_values($meses), -12);
    }

    protected function buildPorCliente($despachados): array
    {
        return $despachados
            ->groupBy(fn (TrillaProducto $p) => $p->destino ?: 'Sin destino')
            ->map(fn ($group, $cliente) => ['cliente' => $cliente, 'kg' => (float) $group->sum('kg')])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    protected function buildPorProducto($productos): array
    {
        return $productos
            ->groupBy(fn (TrillaProducto $p) => $p->nombre ?: 'Sin nombre')
            ->map(function ($group, $nombre) {
                return [
                    'producto' => $nombre,
                    'kg_trillado' => (float) $group->sum('kg'),
                    'kg_despachado' => (float) $group->whereNotNull('remision_despacho')->sum('kg'),
                ];
            })
            ->sortByDesc('kg_trillado')
            ->values()
            ->all();
    }
}
