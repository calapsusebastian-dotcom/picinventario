<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use App\Models\TrillaProducto;
use Illuminate\Support\Facades\DB;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools mirroring the Informes page. See
 * App\Mcp\InventarioTools for the auth/scope notes shared by every tool
 * class in this directory.
 */
class InformesTools
{
    #[McpTool(
        name: 'informe_resumen',
        description: 'Totales generales de todo el histórico: kg enviados, kg recibidos, kg trillados, kg despachados y existencia disponible — los mismos números que arriba de la página de Informes.'
    )]
    public function informeResumen(): array
    {
        $records = InventoryRecord::all();
        $despachados = TrillaProducto::whereNotNull('remision_despacho')->get();

        return [
            'kg_enviados' => (float) $records->sum('kg_enviados'),
            'kg_recibidos' => (float) $records->sum('kg_recibidos'),
            'kg_trillados' => (float) DB::table('trilla_inventory_record')->sum('kg_usado'),
            'kg_despachados' => (float) $despachados->sum('kg'),
            'existencia' => (float) $records->sum(fn (InventoryRecord $r) => $r->existenciaDisponible() ?? 0),
        ];
    }

    #[McpTool(
        name: 'informe_desglose',
        description: 'Desglose del histórico: por mes (últimos 12 meses con actividad), por cliente (kg despachado por destino) y por producto (kg trillado y despachado) — igual que los gráficos de la página de Informes.'
    )]
    public function informeDesglose(): array
    {
        $records = InventoryRecord::all();
        $productos = TrillaProducto::with('trilla')->get();
        $despachados = $productos->whereNotNull('remision_despacho');

        return [
            'por_mes' => $this->buildPorMes($records),
            'por_cliente' => $this->buildPorCliente($despachados),
            'por_producto' => $this->buildPorProducto($productos),
        ];
    }

    /** @param \Illuminate\Support\Collection<int, InventoryRecord> $records */
    private function buildPorMes($records): array
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

        return array_slice(array_values($meses), -12);
    }

    private function buildPorCliente($despachados): array
    {
        return $despachados
            ->groupBy(fn (TrillaProducto $p) => $p->destino ?: 'Sin destino')
            ->map(fn ($group, $cliente) => ['cliente' => $cliente, 'kg' => (float) $group->sum('kg')])
            ->sortByDesc('kg')
            ->values()
            ->all();
    }

    private function buildPorProducto($productos): array
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
