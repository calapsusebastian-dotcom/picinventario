<?php

namespace App\Livewire;

use App\Models\Ubicacion;
use Illuminate\Validation\Rule;
use Livewire\Component;

class UbicacionManager extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingUbicacionId = null;
    public ?int $confirmDeleteId = null;

    public string $nombre = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->editingUbicacionId = null;
        $this->nombre = '';
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $ubicacion = Ubicacion::findOrFail($id);

        $this->editingUbicacionId = $id;
        $this->nombre = $ubicacion->nombre;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('ubicaciones', 'nombre')->ignore($this->editingUbicacionId)],
        ]);

        if ($this->editingUbicacionId) {
            Ubicacion::findOrFail($this->editingUbicacionId)->update($validated);
        } else {
            Ubicacion::create($validated);
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
        Ubicacion::destroy($id);
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $ubicaciones = Ubicacion::query()
            ->when($this->search !== '', fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();

        return view('livewire.ubicacion-manager', [
            'ubicaciones' => $ubicaciones,
        ]);
    }
}
