<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use App\Models\TrillaProducto;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools for the Despacho module. See App\Mcp\InventarioTools
 * for the auth/scope notes shared by every tool class in this directory.
 */
class DespachoTools
{
    #[McpTool(
        name: 'buscar_despacho',
        description: 'Busca un despacho por su número de remisión de despacho (no el número de remisión original). Busca tanto en productos de trilla despachados como en materia prima despachada directo desde bodega.'
    )]
    public function buscarDespacho(string $remisionDespacho): array
    {
        $productos = TrillaProducto::where('remision_despacho', $remisionDespacho)
            ->with('trilla')
            ->get();

        $directos = InventoryRecord::where('remision_despacho', $remisionDespacho)->get();

        if ($productos->isEmpty() && $directos->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No se encontró ningún despacho con la remisión \"{$remisionDespacho}\".",
            ];
        }

        return [
            'encontrado' => true,
            'productos_despachados' => $productos->map(fn (TrillaProducto $p) => [
                'id' => $p->id,
                'producto' => $p->nombre,
                'kg' => $p->kg !== null ? (float) $p->kg : null,
                'destino' => $p->destino,
                'numero_factura' => $p->numero_factura,
                'fecha_despacho' => $p->despachado_at?->format('Y-m-d'),
                'lote_trilla_id' => $p->trilla_id,
                'fecha_trilla' => $p->trilla?->fecha?->format('Y-m-d'),
            ])->all(),
            'materia_prima_despachada' => $directos->map(fn (InventoryRecord $r) => [
                'id' => $r->id,
                'remision_original' => $r->remision,
                'cliente' => $r->cliente,
                'destino' => $r->destino,
                'kg_recibidos' => $r->kg_recibidos !== null ? (float) $r->kg_recibidos : null,
                'numero_factura' => $r->numero_factura,
                'fecha_despacho' => $r->fecha_despacho?->format('Y-m-d'),
            ])->all(),
        ];
    }
}
