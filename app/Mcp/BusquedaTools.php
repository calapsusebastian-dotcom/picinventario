<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use Illuminate\Database\Eloquent\Builder;
use PhpMcp\Server\Attributes\McpTool;

/**
 * General-purpose, filterable listing over InventoryRecord — the MCP
 * equivalent of the Tablero's search box + filters, for when the caller
 * doesn't have an exact remisión number (unlike buscar_remision in
 * App\Mcp\InventarioTools). See that class for the auth/scope notes shared
 * by every tool class in this directory.
 */
class BusquedaTools
{
    #[McpTool(
        name: 'listar_remisiones',
        description: 'Busca y lista remisiones con filtros — texto libre (remisión, cliente, destino, calidad), año, estatus, cliente exacto, y rango de fechas (YYYY-MM-DD). Úsala cuando no tengas el número exacto de remisión o quieras varias a la vez. Devuelve como máximo "limite" resultados (por defecto 20, máximo 100), más recientes primero.'
    )]
    public function listarRemisiones(
        ?string $texto = null,
        ?string $anio = null,
        ?string $estatus = null,
        ?string $cliente = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        int $limite = 20
    ): array {
        $limite = max(1, min($limite, 100));

        $query = InventoryRecord::query()
            ->when($texto, function (Builder $q) use ($texto) {
                $term = '%'.$texto.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('cliente', 'like', $term)
                        ->orWhere('destino', 'like', $term)
                        ->orWhere('negocio', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term);
                });
            })
            ->when($anio, fn (Builder $q) => $q->where('anio', $anio))
            ->when($estatus, fn (Builder $q) => $q->where('estatus', $estatus))
            ->when($cliente, fn (Builder $q) => $q->where('cliente', $cliente))
            ->when($fechaDesde, fn (Builder $q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $fechaHasta));

        $total = $query->count();

        $registros = (clone $query)
            ->with('trillas')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        return [
            'total_coincidencias' => $total,
            'mostrados' => $registros->count(),
            'hay_mas' => $total > $registros->count(),
            'remisiones' => $registros->map(fn (InventoryRecord $r) => [
                'id' => $r->id,
                'fecha' => $r->fecha?->format('Y-m-d'),
                'remision' => $r->remision,
                'calidad' => $r->calidad_enviada,
                'cliente' => $r->cliente,
                'destino' => $r->destino,
                'kg_enviados' => $r->kg_enviados !== null ? (float) $r->kg_enviados : null,
                'kg_recibidos' => $r->kg_recibidos !== null ? (float) $r->kg_recibidos : null,
                'ubicacion' => $r->ubicacionLabel(),
                'estatus' => $r->estatus,
            ])->all(),
        ];
    }
}
