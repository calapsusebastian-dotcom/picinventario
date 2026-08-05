<?php

namespace App\Livewire;

use App\Models\Cliente;
use Illuminate\Validation\Rule;
use Livewire\Component;

class ClienteManager extends Component
{
    public string $search = '';

    public bool $showDrawer = false;
    public ?int $editingClienteId = null;
    public ?int $confirmDeleteId = null;

    public string $nombre = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function openCreate(): void
    {
        $this->editingClienteId = null;
        $this->nombre = '';
        $this->showDrawer = true;
    }

    public function openEdit(int $id): void
    {
        $cliente = Cliente::findOrFail($id);

        $this->editingClienteId = $id;
        $this->nombre = $cliente->nombre;
        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
    }

    public function save(): void
    {
        $validated = $this->validate([
            'nombre' => ['required', 'string', 'max:255', Rule::unique('clientes', 'nombre')->ignore($this->editingClienteId)],
        ]);

        if ($this->editingClienteId) {
            Cliente::findOrFail($this->editingClienteId)->update($validated);
        } else {
            Cliente::create($validated);
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
        Cliente::destroy($id);
        $this->confirmDeleteId = null;
    }

    public function render()
    {
        $clientes = Cliente::query()
            ->when($this->search !== '', fn ($query) => $query->where('nombre', 'like', "%{$this->search}%"))
            ->orderBy('nombre')
            ->get();

        return view('livewire.cliente-manager', [
            'clientes' => $clientes,
        ]);
    }
}
