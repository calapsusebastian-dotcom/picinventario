<?php

namespace App\Mcp;

use App\Models\InventoryRecord;
use Illuminate\Database\Eloquent\Builder;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tools for Contra Entrega (envío vs recepción comparison).
 * See App\Mcp\InventarioTools for the auth/scope notes shared by every tool
 * class in this directory.
 */
class ContraEntregaTools
{
    /** Difference recibido - enviado; null if either side is missing. */
    private function diff(mixed $env, mixed $rec): ?float
    {
        if ($env === null || $env === '' || $rec === null || $rec === '') {
            return null;
        }

        return (float) $rec - (float) $env;
    }

    #[McpTool(
        name: 'contra_entrega_remision',
        description: 'Compara envío vs recepción de una remisión específica: diferencia de kg, factor, humedad, y demás variables de calidad medidas en ambas etapas.'
    )]
    public function contraEntregaRemision(string $numero): array
    {
        $registros = InventoryRecord::where('remision', $numero)
            ->whereNotNull('kg_enviados')
            ->whereNotNull('kg_recibidos')
            ->get();

        if ($registros->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No se encontró ninguna remisión \"{$numero}\" con envío y recepción registrados.",
            ];
        }

        return [
            'encontrado' => true,
            'comparaciones' => $registros->map(function (InventoryRecord $r) {
                $diffKg = $this->diff($r->kg_enviados, $r->kg_recibidos);

                return [
                    'id' => $r->id,
                    'fecha' => $r->fecha?->format('Y-m-d'),
                    'cliente' => $r->cliente,
                    'kg_enviados' => (float) $r->kg_enviados,
                    'kg_recibidos' => (float) $r->kg_recibidos,
                    'diferencia_kg' => $diffKg,
                    'diferencia_pct' => $diffKg !== null && (float) $r->kg_enviados > 0
                        ? round($diffKg / (float) $r->kg_enviados * 100, 2)
                        : null,
                    'diferencia_factor' => $this->diff($r->factor_env, $r->factor_rec),
                    'diferencia_humedad' => $this->diff($r->humedad_env, $r->humedad_rec),
                    'diferencia_broca' => $this->diff($r->broca_env, $r->broca_rec),
                    'taza_coincide' => $r->taza_env && $r->taza_rec ? $r->taza_env === $r->taza_rec : null,
                ];
            })->all(),
        ];
    }

    #[McpTool(
        name: 'contra_entrega_resumen',
        description: 'Totales de envío vs recepción (kg enviados, kg recibidos, diferencia y % de diferencia) sobre todas las remisiones con ambas etapas registradas, opcionalmente filtrado por rango de fechas (YYYY-MM-DD).'
    )]
    public function contraEntregaResumen(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $agg = InventoryRecord::query()
            ->whereNotNull('kg_enviados')
            ->whereNotNull('kg_recibidos')
            ->when($fechaDesde, fn (Builder $q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->toBase()
            ->selectRaw('COUNT(*) as remisiones, COALESCE(SUM(kg_enviados), 0) as kg_enviados, COALESCE(SUM(kg_recibidos), 0) as kg_recibidos')
            ->first();

        $kgEnviados = (float) $agg->kg_enviados;
        $kgRecibidos = (float) $agg->kg_recibidos;
        $diff = $kgRecibidos - $kgEnviados;

        return [
            'remisiones' => (int) $agg->remisiones,
            'kg_enviados' => $kgEnviados,
            'kg_recibidos' => $kgRecibidos,
            'diferencia_kg' => round($diff, 2),
            'diferencia_pct' => $kgEnviados > 0 ? round($diff / $kgEnviados * 100, 2) : null,
        ];
    }
}
