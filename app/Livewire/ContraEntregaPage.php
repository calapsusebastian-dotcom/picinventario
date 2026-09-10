<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class ContraEntregaPage extends Component
{
    use WithPagination;

    protected string $paginationView = 'livewire.pagination';

    public string $search = '';

    public string $fechaDesde = '';
    public string $fechaHasta = '';

    public ?int $expandedRow = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'fechaDesde', 'fechaHasta'], true)) {
            $this->resetPage();
        }
    }

    public function limpiarFechas(): void
    {
        $this->fechaDesde = '';
        $this->fechaHasta = '';
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    /**
     * Difference recibido - enviado for a pair of nullable numeric fields.
     * Null whenever either side hasn't been filled in yet, so an
     * incomplete pair never masquerades as a zero difference.
     */
    private function diff(?string $env, ?string $rec): ?float
    {
        if ($env === null || $env === '' || $rec === null || $rec === '') {
            return null;
        }

        return (float) $rec - (float) $env;
    }

    /**
     * Base query: only remisiones with both stages filled in, plus the
     * date/search filters pushed down to SQL.
     */
    private function filteredQuery(): Builder
    {
        return InventoryRecord::query()
            ->whereNotNull('kg_enviados')
            ->whereNotNull('kg_recibidos')
            ->when($this->fechaDesde !== '', fn (Builder $q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn (Builder $q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('cliente', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term)
                        ->orWhere('ubicacion', 'like', $term);
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }

    /**
     * Turns one remisión into the envío-vs-recepción comparison row.
     *
     * @return array<string, mixed>
     */
    private function comparacionDe(InventoryRecord $r): array
    {
        $diffKg = $this->diff((string) $r->kg_enviados, (string) $r->kg_recibidos);
        $diffKgPct = $diffKg !== null && (float) $r->kg_enviados > 0
            ? $diffKg / (float) $r->kg_enviados * 100
            : null;

        return [
            'record' => $r,
            'diff_kg' => $diffKg,
            'diff_kg_pct' => $diffKgPct,
            'diff_factor' => $this->diff($r->factor_env, $r->factor_rec),
            'diff_humedad' => $this->diff($r->humedad_env, $r->humedad_rec),
            'diff_as' => $this->diff($r->as_env, $r->as_rec),
            'diff_pas' => $this->diff($r->pas_env, $r->pas_rec),
            'diff_pg' => $this->diff($r->pg_env, $r->pg_rec),
            'diff_broca' => $this->diff($r->broca_env, $r->broca_rec),
            'diff_puntaje_taza' => $this->diff($r->puntaje_taza_env, $r->puntaje_taza_rec),
            'taza_coincide' => $r->taza_env && $r->taza_rec ? $r->taza_env === $r->taza_rec : null,
        ];
    }

    /**
     * Streams the currently filtered comparison as a CSV file (Excel opens
     * these natively) — no extra package needed for a plain export like this.
     */
    public function exportar()
    {
        // Full filtered set, every page — not just what's on screen.
        $comparaciones = $this->filteredQuery()->get()->map(fn (InventoryRecord $r) => $this->comparacionDe($r));

        $filename = 'contra-entrega-'.now()->format('Y-m-d_His').'.csv';

        return response()->streamDownload(function () use ($comparaciones) {
            $out = fopen('php://output', 'w');

            // BOM so Excel detects UTF-8 and renders tildes/ñ correctly.
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, [
                'Fecha', 'Remisión', 'Cliente', 'Ubicación', 'Calidad',
                'Kg enviados', 'Kg recibidos', 'Diferencia kg', 'Diferencia %',
                'Factor envío', 'Factor recepción', 'Diferencia factor',
                'Almendra sana envío', 'Almendra sana recepción', 'Diferencia almendra sana',
                'Pasilla envío', 'Pasilla recepción', 'Diferencia pasilla',
                'Primer grupo envío', 'Primer grupo recepción', 'Diferencia primer grupo',
                'Broca envío', 'Broca recepción', 'Diferencia broca',
                'Humedad envío', 'Humedad recepción', 'Diferencia humedad',
                'Puntaje taza envío', 'Puntaje taza recepción', 'Diferencia puntaje taza',
                'Taza envío', 'Taza recepción',
            ]);

            foreach ($comparaciones as $c) {
                $r = $c['record'];

                fputcsv($out, [
                    $r->fecha?->format('Y-m-d'), $r->remision, $r->cliente, $r->ubicacion, $r->calidad_enviada,
                    $r->kg_enviados, $r->kg_recibidos, $c['diff_kg'], $c['diff_kg_pct'],
                    $r->factor_env, $r->factor_rec, $c['diff_factor'],
                    $r->as_env, $r->as_rec, $c['diff_as'],
                    $r->pas_env, $r->pas_rec, $c['diff_pas'],
                    $r->pg_env, $r->pg_rec, $c['diff_pg'],
                    $r->broca_env, $r->broca_rec, $c['diff_broca'],
                    $r->humedad_env, $r->humedad_rec, $c['diff_humedad'],
                    $r->puntaje_taza_env, $r->puntaje_taza_rec, $c['diff_puntaje_taza'],
                    $r->taza_env, $r->taza_rec,
                ]);
            }

            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function render()
    {
        $comparaciones = $this->filteredQuery()
            ->paginate(50)
            ->through(fn (InventoryRecord $r) => $this->comparacionDe($r));

        // KPIs arriba: sobre todo el conjunto filtrado, no solo la página.
        // reorder() limpia el ORDER BY — un SUM() sin GROUP BY con ORDER BY
        // sobre columna no agregada revienta bajo ONLY_FULL_GROUP_BY.
        $agg = $this->filteredQuery()
            ->toBase()
            ->reorder()
            ->selectRaw('COALESCE(SUM(kg_enviados), 0) as e, COALESCE(SUM(kg_recibidos), 0) as r')
            ->first();
        $kgEnviadosTotal = (float) $agg->e;
        $kgRecibidosTotal = (float) $agg->r;
        $diffKgTotal = $kgRecibidosTotal - $kgEnviadosTotal;

        $totales = [
            'kg_enviados' => $kgEnviadosTotal,
            'kg_recibidos' => $kgRecibidosTotal,
            'diff_kg' => $diffKgTotal,
            'diff_kg_pct' => $kgEnviadosTotal > 0 ? $diffKgTotal / $kgEnviadosTotal * 100 : null,
        ];

        return view('livewire.contra-entrega-page', [
            'comparaciones' => $comparaciones,
            'totales' => $totales,
        ]);
    }
}
