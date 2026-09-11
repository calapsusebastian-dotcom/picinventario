<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use App\Models\TrillaProducto;
use App\Support\InventorySummary;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools exposing Bodega PIC's inventory data to external MCP
 * clients (see /mcp, guarded by App\Http\Middleware\EnsureMcpToken). None of
 * these tools write to the database.
 */
class InventarioTools
{
    #[McpTool(
        name: 'buscar_remision',
        description: 'Busca una remisión de café por su número exacto y devuelve su estado completo en el flujo de bodega: envío, recepción, ubicación actual (en bodega, en trilla, despachado, etc.) y saldo disponible.'
    )]
    public function buscarRemision(string $numero): array
    {
        $registros = InventoryRecord::where('remision', $numero)
            ->with('trillas')
            ->orderByDesc('fecha')
            ->get();

        if ($registros->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No se encontró ninguna remisión con el número \"{$numero}\".",
            ];
        }

        return [
            'encontrado' => true,
            'remisiones' => $registros->map(fn (InventoryRecord $r) => [
                'id' => $r->id,
                'fecha' => $r->fecha?->format('Y-m-d'),
                'calidad' => $r->calidad_enviada,
                'cliente' => $r->cliente,
                'destino' => $r->destino,
                'kg_enviados' => $r->kg_enviados !== null ? (float) $r->kg_enviados : null,
                'kg_recibidos' => $r->kg_recibidos !== null ? (float) $r->kg_recibidos : null,
                'factor_rec' => $r->factor_rec !== null ? (float) $r->factor_rec : null,
                'ubicacion' => $r->ubicacionLabel(),
                'estatus' => $r->estatus,
                'kg_disponible_en_bodega' => $r->kgDisponible(),
                'remision_despacho' => $r->remision_despacho,
                'numero_factura' => $r->numero_factura,
            ])->all(),
        ];
    }

    #[McpTool(
        name: 'saldo_bodega',
        description: 'Suma los kg actualmente disponibles en bodega (aún no enviados a trilla, despacho, bodega especial ni bodega almacafe). Opcionalmente filtra por cliente.'
    )]
    public function saldoBodega(?string $cliente = null): array
    {
        $registros = InventoryRecord::query()
            ->where('enviado_a_trilla', false)
            ->where('enviado_a_despacho', false)
            ->where('enviado_a_bodega_especial', false)
            ->where('enviado_a_bodega_almacafe', false)
            ->when($cliente, fn ($q) => $q->where('cliente', $cliente))
            ->with('trillas')
            ->get();

        $totalKg = $registros->sum(fn (InventoryRecord $r) => $r->kgDisponible() ?? 0);

        return [
            'cliente' => $cliente,
            'remisiones' => $registros->count(),
            'kg_disponibles_en_bodega' => round($totalKg, 2),
        ];
    }

    #[McpTool(
        name: 'resumen_kpis',
        description: 'Devuelve el mismo resumen de KPIs que se ve en el Tablero: kg en bodega, en bodega especial, en bodega almacafe, en trilla, en despacho, existencia total y el factor de recepción ponderado.'
    )]
    public function resumenKpis(): array
    {
        return InventorySummary::build();
    }

    #[McpTool(
        name: 'listar_pendientes_despacho',
        description: 'Lista lo que está pendiente de despacho: productos ya trillados sin remisión de despacho, y materia prima enviada directo a despacho sin remisión de despacho asignada.'
    )]
    public function listarPendientesDespacho(): array
    {
        $productos = TrillaProducto::whereNull('remision_despacho')
            ->with('trilla')
            ->get();

        $directos = InventoryRecord::where('enviado_a_despacho', true)
            ->whereNull('remision_despacho')
            ->get();

        return [
            'productos_pendientes' => $productos->map(fn (TrillaProducto $p) => [
                'id' => $p->id,
                'nombre' => $p->nombre,
                'kg' => $p->kg !== null ? (float) $p->kg : null,
                'factor' => $p->factor !== null ? (float) $p->factor : null,
                'lote_trilla_id' => $p->trilla_id,
                'fecha_trilla' => $p->trilla?->fecha?->format('Y-m-d'),
            ])->all(),
            'materia_prima_pendiente' => $directos->map(fn (InventoryRecord $r) => [
                'id' => $r->id,
                'remision' => $r->remision,
                'cliente' => $r->cliente,
                'destino' => $r->destino,
                'kg_recibidos' => $r->kg_recibidos !== null ? (float) $r->kg_recibidos : null,
            ])->all(),
            'total_pendientes' => $productos->count() + $directos->count(),
        ];
    }
}
