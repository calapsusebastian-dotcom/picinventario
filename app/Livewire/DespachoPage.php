<?php

namespace App\Livewire;

use App\Models\Cliente;
use App\Models\InventoryRecord;
use App\Models\TrillaProducto;
use Livewire\Component;

class DespachoPage extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingProductoId = null;
    public ?int $confirmRevertId = null;

    /** @var int|null InventoryRecord id being despachada directamente (sin trilla). */
    public ?int $editingRecordId = null;
    public ?int $confirmRevertRecordId = null;

    public string $remision_despacho = '';
    public string $destino = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccessStage('despacho'), 403);
    }

    public function openEdit(int $id): void
    {
        $producto = TrillaProducto::findOrFail($id);

        $this->editingProductoId = $id;
        $this->editingRecordId = null;
        $this->remision_despacho = $producto->remision_despacho ?? '';
        $this->destino = $producto->destino ?? '';
        $this->showDrawer = true;
    }

    /**
     * Materia prima sent straight from Bodega, skipping trilla.
     */
    public function openEditRecord(int $id): void
    {
        $record = InventoryRecord::findOrFail($id);

        $this->editingRecordId = $id;
        $this->editingProductoId = null;
        $this->remision_despacho = $record->remision_despacho ?? '';
        $this->destino = $record->destino ?? '';
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        if ($this->editingRecordId) {
            $validated = $this->validate([
                'remision_despacho' => ['required', 'string', 'max:255'],
                'destino' => ['nullable', 'string', 'max:255'],
            ]);

            $record = InventoryRecord::findOrFail($this->editingRecordId);

            if (! $record->remision_despacho) {
                $validated['fecha_despacho'] = now();
            }

            $record->update($validated + ['estatus' => 'Despachado']);

            $this->showDrawer = false;

            return;
        }

        $validated = $this->validate([
            'remision_despacho' => ['required', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
        ]);

        $producto = TrillaProducto::findOrFail($this->editingProductoId);

        if (! $producto->despachado_at) {
            $validated['despachado_at'] = now();
        }

        $producto->update($validated);

        $producto->trilla?->syncRecordsEstatus();

        $this->showDrawer = false;
    }

    public function confirmRevert(int $id): void
    {
        $this->confirmRevertId = $id;
    }

    public function cancelRevert(): void
    {
        $this->confirmRevertId = null;
    }

    public function revert(int $id): void
    {
        $producto = TrillaProducto::findOrFail($id);
        $producto->update(['remision_despacho' => null, 'destino' => null, 'despachado_at' => null]);

        $producto->trilla?->syncRecordsEstatus();

        $this->confirmRevertId = null;
    }

    public function confirmRevertRecord(int $id): void
    {
        $this->confirmRevertRecordId = $id;
    }

    public function cancelRevertRecord(): void
    {
        $this->confirmRevertRecordId = null;
    }

    public function revertRecord(int $id): void
    {
        InventoryRecord::whereKey($id)->update([
            'remision_despacho' => null,
            'fecha_despacho' => null,
            'estatus' => 'En bodega',
        ]);

        $this->confirmRevertRecordId = null;
    }

    public function render()
    {
        $pending = TrillaProducto::whereNull('remision_despacho')
            ->with(['trilla.inventoryRecords'])
            ->orderByDesc('id')
            ->get()
            ->filter(function (TrillaProducto $p) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $p->nombre,
                    $p->trilla?->inventoryRecords->pluck('remision')->implode(' '),
                ]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->values();

        $despachados = TrillaProducto::whereNotNull('remision_despacho')
            ->with(['trilla.inventoryRecords'])
            ->orderByDesc('id')
            ->get()
            ->filter(function (TrillaProducto $p) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $p->nombre,
                    $p->remision_despacho,
                    $p->destino,
                    $p->trilla?->inventoryRecords->pluck('remision')->implode(' '),
                ]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->values();

        $pendingDirecto = InventoryRecord::where('enviado_a_despacho', true)
            ->whereNull('remision_despacho')
            ->orderByDesc('fecha')
            ->get()
            ->filter(function (InventoryRecord $r) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [$r->remision, $r->calidad_enviada, $r->cliente]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->values();

        $despachadosDirecto = InventoryRecord::where('enviado_a_despacho', true)
            ->whereNotNull('remision_despacho')
            ->orderByDesc('fecha_despacho')
            ->get()
            ->filter(function (InventoryRecord $r) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [$r->remision, $r->calidad_enviada, $r->cliente, $r->remision_despacho]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->values();

        $editingProducto = $this->editingProductoId
            ? TrillaProducto::with('trilla.inventoryRecords')->find($this->editingProductoId)
            : null;

        $editingRecord = $this->editingRecordId
            ? InventoryRecord::find($this->editingRecordId)
            : null;

        return view('livewire.despacho-page', [
            'pending' => $pending,
            'pendingDirecto' => $pendingDirecto,
            'pendingCount' => $pending->count() + $pendingDirecto->count(),
            'despachados' => $despachados,
            'despachadosDirecto' => $despachadosDirecto,
            'completedCount' => $despachados->count() + $despachadosDirecto->count(),
            'editingProducto' => $editingProducto,
            'editingRecord' => $editingRecord,
            'clientes' => Cliente::orderBy('nombre')->pluck('nombre'),
        ]);
    }
}
