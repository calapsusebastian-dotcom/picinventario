<?php

namespace App\Livewire;

use App\Livewire\Forms\InventoryRecordForm;
use App\Models\Cliente;
use App\Models\InventoryRecord;
use App\Models\Producto;
use App\Models\Ubicacion;
use App\Support\InventoryStages;
use App\Support\InventorySummary;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\Eloquent\Builder;
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

    /**
     * PDF export of the currently filtered result set (not just the current
     * page) plus the same totals shown in the table's footer row.
     */
    public function descargarPdf()
    {
        $registros = $this->filteredQuery()
            ->with('trillas')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get();

        $factorRow = $this->filteredQuery()
            ->where('factor_rec', '>', 0)
            ->where('kg_recibidos', '>', 0)
            ->selectRaw('SUM(factor_rec * kg_recibidos) as num, SUM(kg_recibidos) as den')
            ->first();

        $totales = [
            'count' => $registros->count(),
            'kg_enviados' => (float) $registros->sum('kg_enviados'),
            'kg_recibidos' => (float) $registros->sum('kg_recibidos'),
            'factor_rec_ponderado' => ($factorRow && (float) $factorRow->den > 0)
                ? (float) $factorRow->num / (float) $factorRow->den
                : 0,
        ];

        $pdf = Pdf::loadView('pdf.inventario-tablero', [
            'registros' => $registros,
            'totales' => $totales,
            'filtros' => $this->filtrosAplicados(),
            'generadoEn' => now(),
            'usuario' => auth()->user()->name,
        ])->setPaper('a4', 'landscape');

        $contenido = $pdf->output();

        return response()->streamDownload(
            fn () => print ($contenido),
            'tablero-inventario-'.now()->format('Y-m-d-His').'.pdf',
            ['Content-Type' => 'application/pdf']
        );
    }

    /**
     * Human-readable summary of the active filters, for the PDF header.
     */
    protected function filtrosAplicados(): array
    {
        $filtros = [];

        if ($this->filterAnio !== 'Todos') {
            $filtros[] = 'Año: '.$this->filterAnio;
        }

        if ($this->filterEstatus !== 'Todos') {
            $filtros[] = 'Estatus: '.$this->filterEstatus;
        }

        if ($this->filterCliente !== 'Todos') {
            $filtros[] = 'Cliente: '.$this->filterCliente;
        }

        if ($this->fechaDesde !== '') {
            $filtros[] = 'Desde: '.$this->fechaDesde;
        }

        if ($this->fechaHasta !== '') {
            $filtros[] = 'Hasta: '.$this->fechaHasta;
        }

        if ($this->search !== '') {
            $filtros[] = 'Búsqueda: "'.$this->search.'"';
        }

        return $filtros;
    }

    protected function buildSummary(): array
    {
        return InventorySummary::build();
    }
}
