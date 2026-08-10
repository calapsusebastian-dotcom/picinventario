<?php

namespace App\Livewire;

use App\Livewire\Forms\InventoryRecordForm;
use App\Models\Cliente;
use App\Models\InventoryRecord;
use App\Models\Producto;
use App\Models\TrillaProducto;
use App\Support\InventoryStages;
use Livewire\Component;

class InventoryBoard extends Component
{
    public string $search = '';
    public string $filterAnio = 'Todos';
    public string $filterEstatus = 'Todos';

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

    public function render()
    {
        $allRecords = InventoryRecord::with('trillas.productos')->orderByDesc('fecha')->orderByDesc('id')->get();

        $years = $allRecords->pluck('anio')->filter()->unique()->sort()->values();

        $filtered = $allRecords->filter(function (InventoryRecord $record) {
            if ($this->filterAnio !== 'Todos' && (string) $record->anio !== (string) $this->filterAnio) {
                return false;
            }

            if ($this->filterEstatus !== 'Todos' && $record->estatus !== $this->filterEstatus) {
                return false;
            }

            if ($this->search !== '') {
                $haystack = mb_strtolower(implode(' ', [
                    $record->remision, $record->cliente, $record->destino, $record->negocio, $record->calidad_enviada,
                ]));

                if (! str_contains($haystack, mb_strtolower($this->search))) {
                    return false;
                }
            }

            return true;
        })->values();

        $sections = collect(InventoryStages::ORDER)
            ->map(fn (string $stage) => ['label' => InventoryStages::label($stage), 'role' => InventoryStages::roleLabel($stage)])
            ->all();

        return view('livewire.inventory-board', [
            'records' => $filtered,
            'years' => $years,
            'summary' => $this->buildSummary($allRecords),
            'sections' => $sections,
            'productos' => Producto::orderBy('nombre')->pluck('nombre'),
            'clientes' => Cliente::orderBy('nombre')->pluck('nombre'),
        ]);
    }

    protected function buildSummary($allRecords): array
    {
        $kgEnv = $allRecords->sum(fn (InventoryRecord $r) => (float) $r->kg_enviados);

        // Kg that have gone into a trilla lote, or straight to despacho
        // skipping trilla, no longer count here — a remisión can be
        // partially trillada, so this sums whatever kg each one has left to
        // give, not an all-or-nothing per record. Split by enviado_a_trilla
        // so it's clear how much is still sitting in Bodega versus already
        // released to Trilla's pool.
        $enBodegaOTrilla = $allRecords->filter(fn (InventoryRecord $r) => ! $r->enviado_a_despacho);
        $kgEnBodega = $enBodegaOTrilla
            ->filter(fn (InventoryRecord $r) => ! $r->enviado_a_trilla)
            ->sum(fn (InventoryRecord $r) => $r->kgDisponible() ?? 0);
        $kgEnTrilla = $enBodegaOTrilla
            ->filter(fn (InventoryRecord $r) => $r->enviado_a_trilla)
            ->sum(fn (InventoryRecord $r) => $r->kgDisponible() ?? 0);
        $kgRec = $kgEnBodega + $kgEnTrilla;

        // "Sin despachar": everything still physically in the warehouse,
        // whether it's raw kg recibidos that hasn't been trillado yet or
        // trilla output that hasn't left via despacho. Despacho is the only
        // step that actually removes kg from the warehouse — trilla just
        // transforms it — so this is total kg recibidos minus total kg
        // despachado (trilla output plus materia prima despachada directo),
        // not scoped to the pre-trilla stage like kg_recibidos above.
        $kgRecibidosTotal = $allRecords->sum(fn (InventoryRecord $r) => (float) $r->kg_recibidos);
        $kgDespachadoProductos = (float) TrillaProducto::whereNotNull('remision_despacho')->sum('kg');
        $kgDespachadoDirecto = $allRecords
            ->filter(fn (InventoryRecord $r) => $r->isDespachadoDirecto())
            ->sum(fn (InventoryRecord $r) => (float) $r->kg_recibidos);
        $existencia = max(0, $kgRecibidosTotal - $kgDespachadoProductos - $kgDespachadoDirecto);

        // Trilla output that's already been produced but hasn't left via
        // despacho yet, plus materia prima sent directo a despacho that
        // hasn't shipped out either — the finished/pending-dispatch
        // counterpart of kg_en_bodega / kg_en_trilla, one stage further down.
        $kgEnDespacho = (float) TrillaProducto::whereNull('remision_despacho')->sum('kg')
            + $allRecords
                ->filter(fn (InventoryRecord $r) => $r->enviado_a_despacho && ! $r->isDespachadoDirecto())
                ->sum(fn (InventoryRecord $r) => (float) $r->kg_recibidos);

        // Factor ponderado por kg disponibles (pendientes de trilla): cada
        // remisión pesa según cuánto le queda por trillar, no por su kg
        // recibidos original — una vez trillada, ya no debe seguir pesando.
        $conFactorRec = $allRecords->filter(
            fn (InventoryRecord $r) => (float) $r->factor_rec > 0 && ($r->kgDisponible() ?? 0) > 0
        );
        $kgParaFactor = $conFactorRec->sum(fn (InventoryRecord $r) => $r->kgDisponible());
        $factorPonderado = $kgParaFactor > 0
            ? $conFactorRec->sum(fn (InventoryRecord $r) => (float) $r->factor_rec * $r->kgDisponible()) / $kgParaFactor
            : 0;

        return [
            'registros' => $allRecords->count(),
            'kg_enviados' => $kgEnv,
            'kg_recibidos' => $kgRec,
            'kg_en_bodega' => $kgEnBodega,
            'kg_en_trilla' => $kgEnTrilla,
            'kg_en_despacho' => $kgEnDespacho,
            'existencia' => $existencia,
            'factor_promedio' => $factorPonderado,
        ];
    }
}
