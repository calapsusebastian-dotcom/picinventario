<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Producto;
use App\Models\Trilla;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class TrillaPage extends Component
{
    public string $search = '';

    /** @var array<int, int|string> Selected InventoryRecord ids available to trillar. */
    public array $selected = [];

    /** @var array<int|string, string> Kg to take from each selected remisión, keyed by its id. */
    public array $kgUsado = [];

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

        $records = InventoryRecord::with('trillas')->whereIn('id', $this->selected)->get()->keyBy('id');
        $this->kgUsado = collect($this->selected)
            ->mapWithKeys(fn ($id) => [$id => (string) ($records->get($id)?->kgDisponible() ?? 0)])
            ->all();

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
        $this->kgUsado = [];
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
     * Undo a trilla lote entirely: the kg it had taken from each remisión
     * go back to being available, and the lote (with its productos) is
     * deleted.
     */
    public function revertTrilla(int $id): void
    {
        $trilla = Trilla::with('inventoryRecords')->findOrFail($id);
        $recordIds = $trilla->inventoryRecords->pluck('id');

        $trilla->inventoryRecords()->detach();

        // Only reset a remisión's estatus if it isn't still tied to another
        // trilla lote (it may have contributed the rest of its kg there).
        InventoryRecord::whereIn('id', $recordIds)
            ->whereDoesntHave('trillas')
            ->update(['estatus' => 'En bodega']);

        $trilla->delete();

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
        // Manual addError() calls below don't get cleared automatically
        // between requests the way $this->validate() does, so a stale
        // error from a previous failed attempt could linger even after
        // the user fixes it.
        $this->resetErrorBag();

        if (! $this->editingTrillaId && empty($this->selected)) {
            $this->showDrawer = false;

            return;
        }

        if (! $this->editingTrillaId) {
            $records = InventoryRecord::with('trillas')->whereIn('id', $this->selected)->get()->keyBy('id');

            foreach ($this->selected as $id) {
                $disponible = $records->get($id)?->kgDisponible() ?? 0;
                $usado = is_numeric($this->kgUsado[$id] ?? null) ? (float) $this->kgUsado[$id] : 0;

                if ($usado <= 0) {
                    $this->addError('kgUsado.'.$id, 'Ingresa cuántos kg vas a tomar de esta remisión.');
                } elseif ($usado > $disponible + 0.001) {
                    $this->addError('kgUsado.'.$id, 'Solo hay '.number_format($disponible, 2, ',', '.').' kg disponibles.');
                }
            }

            if ($this->getErrorBag()->isNotEmpty()) {
                return;
            }
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

        // The output products can't total more kg than what actually went
        // into the lote — that would mean producing more than was trillado.
        $kgProductos = collect($validated['productos'])->sum(fn ($p) => is_numeric($p['kg']) ? (float) $p['kg'] : 0);

        $kgIngresados = $this->editingTrillaId
            ? (float) Trilla::findOrFail($this->editingTrillaId)->inventoryRecords()->sum('kg_usado')
            : collect($this->kgUsado)->sum(fn ($v) => is_numeric($v) ? (float) $v : 0);

        if ($kgProductos > $kgIngresados + 0.01) {
            $this->addError('productos', 'La suma de los productos ('.number_format($kgProductos, 2, ',', '.').' kg) no puede superar los kg de las remisiones de este lote ('.number_format($kgIngresados, 2, ',', '.').' kg).');

            return;
        }

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

            foreach ($this->selected as $id) {
                $trilla->inventoryRecords()->attach($id, ['kg_usado' => $this->kgUsado[$id]]);
            }

            $this->selected = [];
            $this->kgUsado = [];
        }

        $this->editingTrillaId = null;
        $this->showDrawer = false;
    }

    public function render()
    {
        $available = InventoryRecord::with('trillas')
            ->where('enviado_a_trilla', true)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->filter(function (InventoryRecord $record) {
                // Only while it still has leftover kg to give — once fully
                // trillada it drops off, even though it was sent from bodega.
                if (($record->kgDisponible() ?? 0) <= 0.001) {
                    return false;
                }

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

        $selectedRecords = InventoryRecord::with('trillas')->whereIn('id', $this->selected)->get()->keyBy('id');

        $selectedKgDisponible = $selectedRecords->sum(fn (InventoryRecord $r) => $r->kgDisponible() ?? 0);

        $selectedKgUsado = collect($this->kgUsado)->sum(fn ($v) => is_numeric($v) ? (float) $v : 0);

        $drawerKgRecibidos = $this->editingTrillaId
            ? ($recentTrillas->firstWhere('id', $this->editingTrillaId)?->inventoryRecords ?? collect())
                ->sum(fn (InventoryRecord $r) => (float) $r->pivot->kg_usado)
            : $selectedKgUsado;

        $editingTrilla = $this->editingTrillaId
            ? $recentTrillas->firstWhere('id', $this->editingTrillaId) ?? Trilla::withCount('inventoryRecords')->find($this->editingTrillaId)
            : null;

        return view('livewire.trilla-page', [
            'available' => $available,
            'recentTrillas' => $recentTrillas,
            'selectedRecords' => $selectedRecords,
            'selectedKgDisponible' => $selectedKgDisponible,
            'drawerKgRecibidos' => $drawerKgRecibidos,
            'editingTrilla' => $editingTrilla,
            'productosCatalogo' => Producto::orderBy('nombre')->pluck('nombre'),
        ]);
    }
}
