<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class BodegaPage extends Component
{
    use WithPagination;

    protected string $paginationView = 'livewire.pagination';

    public string $search = '';

    /** @var string 'Todos' or one of the Ubicación badge labels. */
    public string $filterUbicacion = 'Todos';

    public ?int $expandedRow = null;

    /** @var array<int, int|string> Selected InventoryRecord ids to release to Trilla. */
    public array $selected = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterUbicacion'], true)) {
            $this->resetPage();
        }
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    /**
     * Release the selected remisiones into Trilla's pool. Trilla itself
     * still decides how much of each to actually use and what comes out —
     * this just makes them visible/selectable over there.
     */
    public function enviarATrilla(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_trilla' => true]);

        $this->selected = [];
    }

    /**
     * Release the selected remisiones straight to Despacho, skipping trilla
     * entirely — for materia prima that goes out as-is.
     */
    public function enviarADespacho(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_despacho' => true]);

        $this->selected = [];
    }

    /**
     * Release the selected remisiones into Bodega Especiales — a separate
     * holding area. From there they get routed onward to Trilla or
     * Despacho just like from this Bodega.
     */
    public function enviarABodegaEspecial(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_bodega_especial' => true]);

        $this->selected = [];
    }

    /**
     * Release the selected remisiones into Bodega Almacafe — another
     * separate holding area, same idea as Bodega Especiales.
     */
    public function enviarABodegaAlmacafe(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_bodega_almacafe' => true]);

        $this->selected = [];
    }

    /** SQL expression for kg still available (recibidos − usado en trillas), clamped at 0. */
    private const DISP_EXPR = 'GREATEST(COALESCE(inventory_records.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)';

    /** SQL expression: the remisión is fully trillada (has lote history and no saldo left). */
    private const TRILLADO_EXPR = '(piv.usado IS NOT NULL AND '.self::DISP_EXPR.' <= 0.001)';

    /**
     * Base query with the pivot's kg_usado joined in, so saldo and the
     * Ubicación filter can be resolved in SQL instead of PHP.
     */
    private function filteredQuery(): Builder
    {
        $trillado = self::TRILLADO_EXPR;

        return InventoryRecord::query()
            ->leftJoinSub(
                DB::table('trilla_inventory_record')
                    ->select('inventory_record_id')
                    ->selectRaw('SUM(kg_usado) as usado')
                    ->groupBy('inventory_record_id'),
                'piv',
                'piv.inventory_record_id',
                '=',
                'inventory_records.id'
            )
            ->with('trillas')
            ->whereNotNull('kg_recibidos')
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term)
                        ->orWhere('cliente', 'like', $term);
                });
            })
            ->when($this->filterUbicacion === 'Despachado', fn (Builder $q) => $q->whereNotNull('remision_despacho'))
            ->when($this->filterUbicacion === 'En despacho', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', true))
            ->when($this->filterUbicacion === 'Trillado', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', false)->whereRaw($trillado))
            ->when($this->filterUbicacion === 'En trilla', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', false)
                ->whereRaw("NOT $trillado")->where('enviado_a_trilla', true))
            ->when($this->filterUbicacion === 'En bodega especial', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', false)
                ->whereRaw("NOT $trillado")->where('enviado_a_trilla', false)->where('enviado_a_bodega_especial', true))
            ->when($this->filterUbicacion === 'En bodega almacafe', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', false)
                ->whereRaw("NOT $trillado")->where('enviado_a_trilla', false)
                ->where('enviado_a_bodega_especial', false)->where('enviado_a_bodega_almacafe', true))
            ->when($this->filterUbicacion === 'En bodega', fn (Builder $q) => $q
                ->whereNull('remision_despacho')->where('enviado_a_despacho', false)
                ->whereRaw("NOT $trillado")->where('enviado_a_trilla', false)
                ->where('enviado_a_bodega_especial', false)->where('enviado_a_bodega_almacafe', false))
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }

    public function render()
    {
        $movimientos = $this->filteredQuery()
            ->select('inventory_records.*')
            ->selectRaw('COALESCE(piv.usado, 0) as kg_usado_trilla')
            ->selectRaw(self::DISP_EXPR.' as saldo')
            ->paginate(50)
            ->through(fn (InventoryRecord $r) => [
                'record' => $r,
                'kg_recibido' => (float) $r->kg_recibidos,
                'kg_usado_trilla' => (float) $r->kg_usado_trilla,
                'saldo' => (float) $r->saldo,
            ]);

        // KPIs sobre todo el conjunto filtrado (todas las páginas).
        $agg = $this->filteredQuery()
            ->toBase()
            ->reorder()
            ->select(
                DB::raw('COALESCE(SUM(inventory_records.kg_recibidos), 0) as kg_recibido'),
                DB::raw('COALESCE(SUM(COALESCE(piv.usado, 0)), 0) as kg_usado_trilla'),
                DB::raw('COALESCE(SUM('.self::DISP_EXPR.'), 0) as saldo'),
            )
            ->first();

        $totales = [
            'kg_recibido' => (float) $agg->kg_recibido,
            'kg_usado_trilla' => (float) $agg->kg_usado_trilla,
            'saldo' => (float) $agg->saldo,
        ];

        return view('livewire.bodega-page', [
            'movimientos' => $movimientos,
            'totales' => $totales,
        ]);
    }
}
