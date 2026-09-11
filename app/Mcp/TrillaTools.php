<?php

namespace App\Mcp;

use App\Models\Trilla;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools for the Trilla module. See App\Mcp\InventarioTools for
 * the auth/scope notes shared by every tool class in this directory.
 */
class TrillaTools
{
    #[McpTool(
        name: 'listar_lotes_trilla',
        description: 'Lista los lotes de trilla (más recientes primero), con cuántas remisiones usó cada uno, los kg totales usados, y cuántos productos generó (y si ya se despacharon). Opcionalmente filtra por rango de fechas (YYYY-MM-DD).'
    )]
    public function listarLotesTrilla(?string $fechaDesde = null, ?string $fechaHasta = null, int $limite = 20): array
    {
        $limite = max(1, min($limite, 100));

        $lotes = Trilla::query()
            ->when($fechaDesde, fn ($q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn ($q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->withCount('productos')
            ->with(['inventoryRecords', 'productos'])
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        return [
            'total' => $lotes->count(),
            'lotes' => $lotes->map(fn (Trilla $t) => [
                'id' => $t->id,
                'fecha' => $t->fecha?->format('Y-m-d'),
                'notas' => $t->notas,
                'remisiones_usadas' => $t->inventoryRecords->count(),
                'kg_usado_total' => round((float) $t->inventoryRecords->sum(fn ($r) => (float) $r->pivot->kg_usado), 2),
                'productos_generados' => $t->productos_count,
                'kg_producto_total' => round((float) $t->productos->sum(fn ($p) => (float) $p->kg), 2),
                'totalmente_despachado' => $t->isFullyDespachada(),
            ])->all(),
        ];
    }

    #[McpTool(
        name: 'detalle_lote_trilla',
        description: 'Devuelve el detalle completo de un lote de trilla por su id: qué remisiones usó (y cuántos kg de cada una) y qué productos generó (con su kg, factor, y si ya tienen remisión de despacho).'
    )]
    public function detalleLoteTrilla(int $id): array
    {
        $lote = Trilla::with(['inventoryRecords', 'productos'])->find($id);

        if (! $lote) {
            return [
                'encontrado' => false,
                'mensaje' => "No se encontró ningún lote de trilla con id {$id}.",
            ];
        }

        return [
            'encontrado' => true,
            'id' => $lote->id,
            'fecha' => $lote->fecha?->format('Y-m-d'),
            'notas' => $lote->notas,
            'remisiones_usadas' => $lote->inventoryRecords->map(fn ($r) => [
                'id' => $r->id,
                'remision' => $r->remision,
                'cliente' => $r->cliente,
                'kg_usado' => (float) $r->pivot->kg_usado,
            ])->all(),
            'productos' => $lote->productos->map(fn ($p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'kg' => $p->kg !== null ? (float) $p->kg : null,
                'factor' => $p->factor !== null ? (float) $p->factor : null,
                'despachado' => $p->isDespachado(),
                'remision_despacho' => $p->remision_despacho,
                'destino' => $p->destino,
            ])->all(),
        ];
    }
}
