<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use App\Models\Producto;
use App\Models\TrillaProducto;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools mirroring the Stock page's two tables. See
 * App\Mcp\InventarioTools for the auth/scope notes shared by every tool
 * class in this directory.
 */
class StockTools
{
    #[McpTool(
        name: 'stock_productos_trillados',
        description: 'Stock de productos ya trillados: kg trillado, kg despachado y kg en stock (lo que queda), por producto. Opcionalmente filtra por nombre de producto (coincidencia parcial).'
    )]
    public function stockProductosTrillados(?string $producto = null): array
    {
        $todos = TrillaProducto::all();

        $nombres = Producto::orderBy('nombre')->pluck('nombre')
            ->merge($todos->pluck('nombre'))
            ->unique()
            ->filter()
            ->sort()
            ->values();

        $filas = $nombres
            ->when($producto, fn ($c) => $c->filter(fn (string $n) => str_contains(mb_strtolower($n), mb_strtolower($producto))))
            ->map(function (string $nombre) use ($todos) {
                $lotes = $todos->where('nombre', $nombre);
                $trillado = (float) $lotes->sum('kg');
                $despachado = (float) $lotes->whereNotNull('remision_despacho')->sum('kg');

                return [
                    'producto' => $nombre,
                    'kg_trillado' => $trillado,
                    'kg_despachado' => $despachado,
                    'kg_stock' => max(0, $trillado - $despachado),
                ];
            })
            ->sortByDesc('kg_stock')
            ->values();

        return [
            'productos' => $filas->all(),
            'totales' => [
                'kg_trillado' => $filas->sum('kg_trillado'),
                'kg_despachado' => $filas->sum('kg_despachado'),
                'kg_stock' => $filas->sum('kg_stock'),
            ],
        ];
    }

    #[McpTool(
        name: 'stock_materia_prima',
        description: 'Materia prima recibida que aún no ha pasado por trilla ni fue enviada directo a despacho, agrupada por calidad, con el factor de recepción ponderado. Opcionalmente filtra por calidad (coincidencia parcial).'
    )]
    public function stockMateriaPrima(?string $calidad = null): array
    {
        $records = InventoryRecord::with('trillas')->whereNotNull('kg_recibidos')->get();

        $calidades = Producto::orderBy('nombre')->pluck('nombre')
            ->merge($records->pluck('calidad_enviada'))
            ->unique()
            ->filter()
            ->sort()
            ->values()
            ->when($calidad, fn ($c) => $c->filter(fn (string $n) => str_contains(mb_strtolower($n), mb_strtolower($calidad))));

        $filas = $calidades
            ->map(function (string $c) use ($records) {
                $remisiones = $records
                    ->where('calidad_enviada', $c)
                    ->filter(fn (InventoryRecord $r) => ! $r->enviado_a_despacho && ($r->kgDisponible() ?? 0) > 0.001)
                    ->values();

                $kgDisponible = (float) $remisiones->sum(fn (InventoryRecord $r) => $r->kgDisponible());

                $conFactor = $remisiones->filter(fn (InventoryRecord $r) => (float) $r->factor_rec > 0);
                $kgParaFactor = $conFactor->sum(fn (InventoryRecord $r) => $r->kgDisponible());
                $factorPonderado = $kgParaFactor > 0
                    ? $conFactor->sum(fn (InventoryRecord $r) => (float) $r->factor_rec * $r->kgDisponible()) / $kgParaFactor
                    : null;

                return [
                    'calidad' => $c,
                    'kg_disponible' => $kgDisponible,
                    'factor_ponderado' => $factorPonderado,
                    'remisiones' => $remisiones->count(),
                ];
            })
            ->filter(fn (array $fila) => $fila['kg_disponible'] > 0 || $fila['remisiones'] > 0)
            ->sortByDesc('kg_disponible')
            ->values();

        return [
            'calidades' => $filas->all(),
            'kg_total_disponible' => $filas->sum('kg_disponible'),
        ];
    }
}
