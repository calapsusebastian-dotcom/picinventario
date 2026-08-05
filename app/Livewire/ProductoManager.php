<?php

namespace App\Livewire;

use App\Models\Producto;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ProductoManager extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingProductoId = null;
    public ?int $confirmDeleteId = null;

    public string $nombre = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->editingProductoId = null;
        $this->nombre = '';
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $producto = Producto::findOrFail($id);

        $this->editingProductoId = $id;
        $this->nombre = $producto->nombre;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('productos', 'nombre')->ignore($this->editingProductoId)],
        ]);

        if ($this->editingProductoId) {
            Producto::findOrFail($this->editingProductoId)->update($validated);
        } else {
            Producto::create($validated);
        }

        $this->showDrawer = false;
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
        Producto::destroy($id);
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $productos = Producto::query()
            ->when($this->search !== '', fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();

        return view('livewire.producto-manager', [
            'productos' => $productos,
        ]);
    }
}
