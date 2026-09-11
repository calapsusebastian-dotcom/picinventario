<?php

namespace App\Support;

use App\Models\TrillaProducto;
use Illuminate\Support\Facades\DB;

/**
 * The Tablero's global KPI numbers — kg in each stage of the pipeline plus
 * existencia and the weighted reception factor. Shared between the Tablero
 * itself and the read-only MCP tools so both report the exact same numbers.
 */
class InventorySummary
{
    public static function build(): array
    {
        // One lightweight pass over plain rows (no model hydration, no
        // relations): each remisión's "disponible" = kg recibidos minus what
        // it has already given to trilla lotes, via a joined pivot subquery.
        $rows = DB::table('inventory_records as ir')
            ->leftJoinSub(
                DB::table('trilla_inventory_record')
                    ->select('inventory_record_id')
                    ->selectRaw('SUM(kg_usado) as usado')
                    ->groupBy('inventory_record_id'),
                'piv',
                'piv.inventory_record_id',
                '=',
                'ir.id'
            )
            ->selectRaw('
                COUNT(*) as registros,
                COALESCE(SUM(ir.kg_enviados), 0) as kg_enviados,
                COALESCE(SUM(ir.kg_recibidos), 0) as kg_recibidos_total,
                COALESCE(SUM(GREATEST(COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)
                    * (ir.enviado_a_despacho = 0 AND ir.enviado_a_trilla = 0 AND ir.enviado_a_bodega_especial = 0 AND ir.enviado_a_bodega_almacafe = 0)), 0) as kg_en_bodega,
                COALESCE(SUM(GREATEST(COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)
                    * (ir.enviado_a_despacho = 0 AND ir.enviado_a_trilla = 0 AND ir.enviado_a_bodega_especial = 1)), 0) as kg_en_bodega_especial,
                COALESCE(SUM(GREATEST(COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)
                    * (ir.enviado_a_despacho = 0 AND ir.enviado_a_trilla = 0 AND ir.enviado_a_bodega_especial = 0 AND ir.enviado_a_bodega_almacafe = 1)), 0) as kg_en_bodega_almacafe,
                COALESCE(SUM(GREATEST(COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)
                    * (ir.enviado_a_despacho = 0 AND ir.enviado_a_trilla = 1)), 0) as kg_en_trilla,
                COALESCE(SUM((ir.remision_despacho IS NOT NULL) * COALESCE(ir.kg_recibidos, 0)), 0) as kg_despachado_directo,
                COALESCE(SUM((ir.enviado_a_despacho = 1 AND ir.remision_despacho IS NULL) * COALESCE(ir.kg_recibidos, 0)), 0) as kg_pendiente_despacho_directo,
                COALESCE(SUM(CASE WHEN ir.factor_rec > 0 AND (COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0)) > 0
                    THEN ir.factor_rec * (COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0)) ELSE 0 END), 0) as factor_num,
                COALESCE(SUM(CASE WHEN ir.factor_rec > 0 AND (COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0)) > 0
                    THEN (COALESCE(ir.kg_recibidos, 0) - COALESCE(piv.usado, 0)) ELSE 0 END), 0) as factor_den
            ')
            ->first();

        $kgDespachadoProductos = (float) TrillaProducto::whereNotNull('remision_despacho')->sum('kg');
        $kgProductoPendiente = (float) TrillaProducto::whereNull('remision_despacho')->sum('kg');

        $kgEnBodega = (float) $rows->kg_en_bodega;
        $kgEnBodegaEspecial = (float) $rows->kg_en_bodega_especial;
        $kgEnBodegaAlmacafe = (float) $rows->kg_en_bodega_almacafe;
        $kgEnTrilla = (float) $rows->kg_en_trilla;

        return [
            'registros' => (int) $rows->registros,
            'kg_enviados' => (float) $rows->kg_enviados,
            'kg_recibidos' => $kgEnBodega + $kgEnBodegaEspecial + $kgEnBodegaAlmacafe + $kgEnTrilla,
            'kg_en_bodega' => $kgEnBodega,
            'kg_en_bodega_especial' => $kgEnBodegaEspecial,
            'kg_en_bodega_almacafe' => $kgEnBodegaAlmacafe,
            'kg_en_trilla' => $kgEnTrilla,
            'kg_en_despacho' => $kgProductoPendiente + (float) $rows->kg_pendiente_despacho_directo,
            'existencia' => max(0, (float) $rows->kg_recibidos_total - $kgDespachadoProductos - (float) $rows->kg_despachado_directo),
            'factor_promedio' => (float) $rows->factor_den > 0 ? (float) $rows->factor_num / (float) $rows->factor_den : 0,
        ];
    }
}
