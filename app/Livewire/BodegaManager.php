<?php

namespace App\Livewire;

use App\Models\Bodega;
use Illuminate\Validation\Rule;
use Livewire\Component;

/**
 * Create/rename/activate/deactivate bodegas — each one gets a page at
 * /bodega/{slug} (App\Livewire\BodegaDetallePage) that behaves like Bodega
 * Especiales always did. No hard delete: a bodega might already have
 * remisiones routed into it, so it's only ever deactivated (hidden from the
 * sidebar and the "Enviar a bodega" menu, its page and data stay intact).
 */
class BodegaManager extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingBodegaId = null;

    public string $nombre = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->editingBodegaId = null;
        $this->nombre = '';
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $bodega = Bodega::findOrFail($id);

        $this->editingBodegaId = $id;
        $this->nombre = $bodega->nombre;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('bodegas', 'nombre')->ignore($this->editingBodegaId)],
        ]);

        if ($this->editingBodegaId) {
            Bodega::findOrFail($this->editingBodegaId)->update($validated);
        } else {
            Bodega::create($validated);
        }

        $this->showDrawer = false;
    }

    public function toggleActivo(int $id): void
    {
        $bodega = Bodega::findOrFail($id);
        $bodega->update(['activo' => ! $bodega->activo]);
    }

    public function render()
    {
        $bodegas = Bodega::query()
            ->withCount('inventoryRecords')
            ->when($this->search !== '', fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();

        return view('livewire.bodega-manager', [
            'bodegas' => $bodegas,
        ]);
    }
}
