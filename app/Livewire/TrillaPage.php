<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Trilla;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TrillaPage extends Component
{
    public string $search = '';

    /** @var array<int, int|string> Selected InventoryRecord ids available to trillar. */
    public array $selected = [];

    public ?int $expandedRow = null;
    public ?int $expandedTrilla = null;
    public ?int $confirmRevertId = null;

    public bool $showDrawer = false;
    public ?int $editingTrillaId = null;

    public string $fecha = '';
    public string $notas = '';

    /** @var array<int, array{id: int|null, nombre: string, kg: string, factor: string}> */
    public array $productos = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->canAccessStage('trilla'), 403);
    }

    public function openProcess(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->editingTrillaId = null;
        $this->fecha = now()->format('Y-m-d');
        $this->notas = '';
        $this->productos = [
            ['id' => null, 'nombre' => '', 'kg' => '', 'factor' => ''],
        ];
        $this->showDrawer = true;
    }

    public function editTrilla(int $id): void
    {
        $trilla = Trilla::with('productos')->findOrFail($id);

        $this->editingTrillaId = $id;
        $this->fecha = $trilla->fecha?->format('Y-m-d') ?? '';
        $this->notas = $trilla->notas ?? '';
        $this->productos = $trilla->productos->map(fn ($p) => [
            'id' => $p->id,
            'nombre' => $p->nombre,
            'kg' => (string) $p->kg,
            'factor' => (string) $p->factor,
        ])->all();

        if (empty($this->productos)) {
            $this->productos = [['id' => null, 'nombre' => '', 'kg' => '', 'factor' => '']];
        }

        $this->showDrawer = true;
    }

    public function closeDrawer(): void
    {
        $this->showDrawer = false;
        $this->editingTrillaId = null;
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    public function toggleExpandTrilla(int $id): void
    {
        $this->expandedTrilla = $this->expandedTrilla === $id ? null : $id;
        $this->confirmRevertId = null;
    }

    public function confirmRevert(int $id): void
    {
        $this->confirmRevertId = $id;
    }

    public function cancelRevert(): void
    {
        $this->confirmRevertId = null;
    }

    /**
     * Undo a trilla lote entirely: the remisiones go back to being
     * available, and the lote (with its productos) is deleted.
     */
    public function revertTrilla(int $id): void
    {
        InventoryRecord::where('trilla_id', $id)->update(['trilla_id' => null, 'estatus' => 'En bodega']);
        Trilla::destroy($id);

        if ($this->expandedTrilla === $id) {
            $this->expandedTrilla = null;
        }

        $this->confirmRevertId = null;
    }

    public function addProducto(): void
    {
        $this->productos[] = ['id' => null, 'nombre' => '', 'kg' => '', 'factor' => ''];
    }

    public function removeProducto(int $index): void
    {
        if (count($this->productos) <= 1) {
            return;
        }

        unset($this->productos[$index]);
        $this->productos = array_values($this->productos);
    }

    public function save(): void
    {
        if (! $this->editingTrillaId && empty($this->selected)) {
            $this->showDrawer = false;

            return;
        }

        $validated = Validator::make([
            'fecha' => $this->fecha,
            'notas' => $this->notas,
            'productos' => $this->productos,
        ], [
            'fecha' => ['nullable', 'date'],
            'notas' => ['nullable', 'string', 'max:1000'],
            'productos' => ['required', 'array', 'min:1'],
            'productos.*.id' => ['nullable', 'integer'],
            'productos.*.nombre' => ['required', 'string', 'max:255'],
            'productos.*.kg' => ['nullable', 'numeric'],
            'productos.*.factor' => ['nullable', 'numeric'],
        ], [
            'productos.*.nombre.required' => 'Cada producto necesita un nombre.',
        ])->validate();

        if ($this->editingTrillaId) {
            $trilla = Trilla::findOrFail($this->editingTrillaId);
            $trilla->update([
                'fecha' => $validated['fecha'] ?: null,
                'notas' => $validated['notas'] ?: null,
            ]);

            $keptIds = [];

            foreach ($validated['productos'] as $producto) {
                $data = [
                    'nombre' => $producto['nombre'],
                    'kg' => $producto['kg'] !== '' && $producto['kg'] !== null ? $producto['kg'] : null,
                    'factor' => $producto['factor'] !== '' && $producto['factor'] !== null ? $producto['factor'] : null,
                ];

                if (! empty($producto['id'])) {
                    $trilla->productos()->where('id', $producto['id'])->update($data);
                    $keptIds[] = $producto['id'];
                } else {
                    $keptIds[] = $trilla->productos()->create($data)->id;
                }
            }

            $trilla->productos()->whereNotIn('id', $keptIds)->delete();
        } else {
            $trilla = Trilla::create([
                'fecha' => $validated['fecha'] ?: null,
                'notas' => $validated['notas'] ?: null,
            ]);

            foreach ($validated['productos'] as $producto) {
                $trilla->productos()->create([
                    'nombre' => $producto['nombre'],
                    'kg' => $producto['kg'] !== '' ? $producto['kg'] : null,
                    'factor' => $producto['factor'] !== '' ? $producto['factor'] : null,
                ]);
            }

            InventoryRecord::whereIn('id', $this->selected)->update(['trilla_id' => $trilla->id]);

            $this->selected = [];
        }

        $this->editingTrillaId = null;
        $this->showDrawer = false;
    }

    public function render()
    {
        $available = InventoryRecord::whereNull('trilla_id')
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->filter(function (InventoryRecord $record) {
                if ($this->search === '') {
                    return true;
                }

                return str_contains(mb_strtolower((string) $record->remision), mb_strtolower($this->search));
            })
            ->values();

        $recentTrillas = Trilla::withCount('inventoryRecords')
            ->with(['productos', 'inventoryRecords'])
            ->orderByDesc('id')
            ->limit(5)
            ->get();

        $selectedKgRecibidos = InventoryRecord::whereIn('id', $this->selected)->sum('kg_recibidos');

        $drawerKgRecibidos = $this->editingTrillaId
            ? InventoryRecord::where('trilla_id', $this->editingTrillaId)->sum('kg_recibidos')
            : $selectedKgRecibidos;

        $editingTrilla = $this->editingTrillaId
            ? $recentTrillas->firstWhere('id', $this->editingTrillaId) ?? Trilla::withCount('inventoryRecords')->find($this->editingTrillaId)
            : null;

        return view('livewire.trilla-page', [
            'available' => $available,
            'recentTrillas' => $recentTrillas,
            'selectedKgRecibidos' => $selectedKgRecibidos,
            'drawerKgRecibidos' => $drawerKgRecibidos,
            'editingTrilla' => $editingTrilla,
        ]);
    }
}
