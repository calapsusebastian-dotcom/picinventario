<?php

namespace App\Livewire;

use App\Models\TrillaProducto;
use Livewire\Component;

class DespachoPage extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingProductoId = null;

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
        $this->remision_despacho = $producto->remision_despacho ?? '';
        $this->destino = $producto->destino ?? '';
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'remision_despacho' => ['required', 'string', 'max:255'],
            'destino' => ['nullable', 'string', 'max:255'],
        ]);

        $producto = TrillaProducto::findOrFail($this->editingProductoId);
        $producto->update($validated);

        $producto->trilla?->syncRecordsEstatus();

        $this->showDrawer = false;
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

        $editingProducto = $this->editingProductoId
            ? TrillaProducto::with('trilla.inventoryRecords')->find($this->editingProductoId)
            : null;

        return view('livewire.despacho-page', [
            'pending' => $pending,
            'pendingCount' => $pending->count(),
            'despachados' => $despachados,
            'completedCount' => $despachados->count(),
            'editingProducto' => $editingProducto,
        ]);
    }
}
