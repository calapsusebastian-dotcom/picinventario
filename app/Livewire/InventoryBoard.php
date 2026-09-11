<?php

namespace App\Livewire;

use App\Livewire\Forms\InventoryRecordForm;
use App\Models\Cliente;
use App\Models\InventoryRecord;
use App\Models\Producto;
use App\Models\TrillaProducto;
use App\Models\Ubicacion;
use App\Support\InventoryStages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class InventoryBoard extends Component
{
    use WithPagination;

    protected string $paginationView = 'livewire.pagination';

    public string $search = '';
    public string $filterAnio = 'Todos';
    public string $filterEstatus = 'Todos';
    public string $filterCliente = 'Todos';
    public string $fechaDesde = '';
    public string $fechaHasta = '';

    public ?int $expandedRow = null;
    public ?int $confirmDeleteId = null;

    public bool $showDrawer = false;
    public ?int $editingId = null;
    public int $activeSection = 0;

    public InventoryRecordForm $form;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->form->reset();
        $this->editingId = null;
        $this->activeSection = 0;
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $record = InventoryRecord::findOrFail($id);
        $this->form->setFromModel($record);
        $this->editingId = $id;
        $this->activeSection = 0;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $stage = InventoryStages::ORDER[$this->activeSection];
        $this->form->validate($this->form->rulesForStage($stage));

        $data = $this->form->toDatabaseArray();

        if ($this->editingId) {
            InventoryRecord::findOrFail($this->editingId)->update($data);
        } else {
            InventoryRecord::create($data);
        }

        $this->showDrawer = false;
    }

    public function limpiarFechas(): void
    {
        $this->fechaDesde = '';
        $this->fechaHasta = '';
    }

    /**
     * Any filter change should send the table back to page 1, otherwise a
     * narrower result set can leave you stranded on an empty page.
     */
    public function updated(string $property): void
    {
        if (in_array($property, ['search', 'filterAnio', 'filterEstatus', 'filterCliente', 'fechaDesde', 'fechaHasta'], true)) {
            $this->resetPage();
        }
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
        $this->confirmDeleteId = null;
    }

    public function confirmDelete(int $id): void
    {
        $this->confirmDeleteId = $id;
    }

    public function cancelDelete(): void
    {
        $this->confirmDeleteId = null;
    }

    public function delete(int $id): void
    {
        InventoryRecord::destroy($id);

        if ($this->expandedRow === $id) {
            $this->expandedRow = null;
        }

        $this->confirmDeleteId = null;
    }

    public function prevSection(): void
    {
        $this->activeSection = max(0, $this->activeSection - 1);
    }

    public function nextSection(): void
    {
        $this->attemptSwitch(min(count(InventoryStages::ORDER) - 1, $this->activeSection + 1));
    }

    public function attemptSwitch(int $idx): void
    {
        $status = $this->form->stageStatus();

        if ($this->isTabLocked($idx, $status)) {
            $target = InventoryStages::label(InventoryStages::ORDER[$idx]);
            $this->dispatch(
                'lock-attempted',
                message: "Completa \"General\" antes de continuar con \"{$target}\" — todos los roles dependen del registro general."
            );

            return;
        }

        $this->activeSection = $idx;
    }

    /**
     * Every stage other than general depends only on general being
     * complete — not on the stage immediately before it.
     */
    public function isTabLocked(int $idx, array $status): bool
    {
        return $idx > 0 && ! $status[0];
    }

    /**
     * The records query with every active filter pushed down to SQL, so
     * pagination and the totals aggregate over the real result set instead
     * of loading the whole table into memory.
     */
    protected function filteredQuery(): Builder
    {
        return InventoryRecord::query()
            ->when($this->filterAnio !== 'Todos', fn (Builder $q) => $q->where('anio', $this->filterAnio))
            ->when($this->filterEstatus !== 'Todos', fn (Builder $q) => $q->where('estatus', $this->filterEstatus))
            ->when($this->filterCliente !== 'Todos', fn (Builder $q) => $q->where('cliente', $this->filterCliente))
            ->when($this->fechaDesde !== '', fn (Builder $q) => $q->whereDate('fecha', '>=', $this->fechaDesde))
            ->when($this->fechaHasta !== '', fn (Builder $q) => $q->whereDate('fecha', '<=', $this->fechaHasta))
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('cliente', 'like', $term)
                        ->orWhere('destino', 'like', $term)
                        ->orWhere('negocio', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term);
                });
            });
    }

    public function render()
    {
        $records = $this->filteredQuery()
            ->with('trillas.productos')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->paginate(50);

        // Totales de lo filtrado — sobre todo el conjunto, no solo la página.
        $factorRow = $this->filteredQuery()
            ->where('factor_rec', '>', 0)
            ->where('kg_recibidos', '>', 0)
            ->selectRaw('SUM(factor_rec * kg_recibidos) as num, SUM(kg_recibidos) as den')
            ->first();

        $filaTotales = [
            'count' => $records->total(),
            'kg_enviados' => (float) $this->filteredQuery()->sum('kg_enviados'),
            'kg_recibidos' => (float) $this->filteredQuery()->sum('kg_recibidos'),
            'factor_rec_ponderado' => ($factorRow && (float) $factorRow->den > 0)
                ? (float) $factorRow->num / (float) $factorRow->den
                : 0,
        ];

        $sections = collect(InventoryStages::ORDER)
            ->map(fn (string $stage) => ['label' => InventoryStages::label($stage), 'role' => InventoryStages::roleLabel($stage)])
            ->all();

        return view('livewire.inventory-board', [
            'records' => $records,
            'years' => InventoryRecord::query()->whereNotNull('anio')->distinct()->orderBy('anio')->pluck('anio'),
            'clientesFiltro' => InventoryRecord::query()->whereNotNull('cliente')->where('cliente', '!=', '')->distinct()->orderBy('cliente')->pluck('cliente'),
            'filaTotales' => $filaTotales,
            'summary' => $this->buildSummary(),
            'sections' => $sections,
            'productos' => Producto::orderBy('nombre')->pluck('nombre'),
            'clientes' => Cliente::orderBy('nombre')->pluck('nombre'),
            'ubicaciones' => Ubicacion::orderBy('nombre')->pluck('nombre'),
        ]);
    }

    protected function buildSummary(): array
    {
        // The KPI cards are global — they don't move with the table filters.
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
