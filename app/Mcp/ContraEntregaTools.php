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

    /** @return array<string, mixed> Full envío-vs-recepción comparison for one remisión. */
    private function comparacionDe(InventoryRecord $r): array
    {
        $diffKg = $this->diff($r->kg_enviados, $r->kg_recibidos);

        return [
            'id' => $r->id,
            'fecha' => $r->fecha?->format('Y-m-d'),
            'remision' => $r->remision,
            'cliente' => $r->cliente,
            'ubicacion' => $r->ubicacion,
            'calidad' => $r->calidad_enviada,
            'kg_enviados' => (float) $r->kg_enviados,
            'kg_recibidos' => (float) $r->kg_recibidos,
            'diferencia_kg' => $diffKg,
            'diferencia_pct' => $diffKg !== null && (float) $r->kg_enviados > 0
                ? round($diffKg / (float) $r->kg_enviados * 100, 2)
                : null,
            'diferencia_factor' => $this->diff($r->factor_env, $r->factor_rec),
            'diferencia_humedad' => $this->diff($r->humedad_env, $r->humedad_rec),
            'diferencia_as' => $this->diff($r->as_env, $r->as_rec),
            'diferencia_pas' => $this->diff($r->pas_env, $r->pas_rec),
            'diferencia_pg' => $this->diff($r->pg_env, $r->pg_rec),
            'diferencia_broca' => $this->diff($r->broca_env, $r->broca_rec),
            'diferencia_puntaje_taza' => $this->diff($r->puntaje_taza_env, $r->puntaje_taza_rec),
            'taza_coincide' => $r->taza_env && $r->taza_rec ? $r->taza_env === $r->taza_rec : null,
        ];
    }

    /** Base query: only remisiones with both envío and recepción filled in. */
    private function baseQuery(): Builder
    {
        return InventoryRecord::query()
            ->whereNotNull('kg_enviados')
            ->whereNotNull('kg_recibidos');
    }

    #[McpTool(
        name: 'contra_entrega_remision',
        description: 'Compara envío vs recepción de una remisión específica: diferencia de kg, factor, humedad, y demás variables de calidad medidas en ambas etapas.'
    )]
    public function contraEntregaRemision(string $numero): array
    {
        $registros = $this->baseQuery()->where('remision', $numero)->get();

        if ($registros->isEmpty()) {
            return [
                'encontrado' => false,
                'mensaje' => "No se encontró ninguna remisión \"{$numero}\" con envío y recepción registrados.",
            ];
        }

        return [
            'encontrado' => true,
            'comparaciones' => $registros->map(fn (InventoryRecord $r) => $this->comparacionDe($r))->all(),
        ];
    }

    #[McpTool(
        name: 'contra_entrega_listado',
        description: 'Informe completo de contra entrega: la comparación envío vs recepción de cada remisión (no solo totales), con todos sus valores de diferencia. Filtra por texto libre (remisión/cliente/calidad/ubicación) y/o rango de fechas (YYYY-MM-DD). Úsala cuando pidan "el informe de contra entrega" o revisar varias remisiones a la vez, no solo una.'
    )]
    public function contraEntregaListado(
        ?string $texto = null,
        ?string $fechaDesde = null,
        ?string $fechaHasta = null,
        int $limite = 20
    ): array {
        $limite = max(1, min($limite, 100));

        $query = $this->baseQuery()
            ->when($fechaDesde, fn (Builder $q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->when($texto, function (Builder $q) use ($texto) {
                $term = '%'.$texto.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('cliente', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term)
                        ->orWhere('ubicacion', 'like', $term);
                });
            });

        $total = $query->count();

        $registros = (clone $query)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->limit($limite)
            ->get();

        return [
            'total_coincidencias' => $total,
            'mostrados' => $registros->count(),
            'hay_mas' => $total > $registros->count(),
            'comparaciones' => $registros->map(fn (InventoryRecord $r) => $this->comparacionDe($r))->all(),
        ];
    }

    #[McpTool(
        name: 'contra_entrega_por_cliente',
        description: 'Informe de contra entrega agrupado por cliente: cantidad de remisiones, kg enviados, kg recibidos, diferencia total y % de diferencia promedio de cada cliente. Útil para ver qué clientes tienen más mermas. Opcionalmente filtra por rango de fechas (YYYY-MM-DD).'
    )]
    public function contraEntregaPorCliente(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $registros = $this->baseQuery()
            ->when($fechaDesde, fn (Builder $q) => $q->whereDate('fecha', '>=', $fechaDesde))
            ->when($fechaHasta, fn (Builder $q) => $q->whereDate('fecha', '<=', $fechaHasta))
            ->get();

        $porCliente = $registros
            ->groupBy(fn (InventoryRecord $r) => $r->cliente ?: 'Sin cliente')
            ->map(function ($grupo, $cliente) {
                $kgEnviados = (float) $grupo->sum('kg_enviados');
                $kgRecibidos = (float) $grupo->sum('kg_recibidos');
                $diff = $kgRecibidos - $kgEnviados;

                return [
                    'cliente' => $cliente,
                    'remisiones' => $grupo->count(),
                    'kg_enviados' => $kgEnviados,
                    'kg_recibidos' => $kgRecibidos,
                    'diferencia_kg' => round($diff, 2),
                    'diferencia_pct' => $kgEnviados > 0 ? round($diff / $kgEnviados * 100, 2) : null,
                ];
            })
            ->sortBy('diferencia_kg')
            ->values();

        return ['clientes' => $porCliente->all()];
    }

    #[McpTool(
        name: 'contra_entrega_por_mes',
        description: 'Informe de contra entrega agrupado por mes (según la fecha de la remisión): remisiones, kg enviados, kg recibidos, diferencia total y % de diferencia promedio de cada mes. Útil para ver la tendencia de mermas en el tiempo.'
    )]
    public function contraEntregaPorMes(): array
    {
        $registros = $this->baseQuery()->whereNotNull('fecha')->get();

        $porMes = $registros
            ->groupBy(fn (InventoryRecord $r) => $r->fecha->format('Y-m'))
            ->map(function ($grupo, $mes) {
                $kgEnviados = (float) $grupo->sum('kg_enviados');
                $kgRecibidos = (float) $grupo->sum('kg_recibidos');
                $diff = $kgRecibidos - $kgEnviados;

                return [
                    'mes' => $mes,
                    'remisiones' => $grupo->count(),
                    'kg_enviados' => $kgEnviados,
                    'kg_recibidos' => $kgRecibidos,
                    'diferencia_kg' => round($diff, 2),
                    'diferencia_pct' => $kgEnviados > 0 ? round($diff / $kgEnviados * 100, 2) : null,
                ];
            })
            ->sortKeys()
            ->values();

        return ['meses' => $porMes->all()];
    }

    #[McpTool(
        name: 'contra_entrega_resumen',
        description: 'Totales de envío vs recepción (kg enviados, kg recibidos, diferencia y % de diferencia) sobre todas las remisiones con ambas etapas registradas, opcionalmente filtrado por rango de fechas (YYYY-MM-DD).'
    )]
    public function contraEntregaResumen(?string $fechaDesde = null, ?string $fechaHasta = null): array
    {
        $agg = $this->baseQuery()
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
