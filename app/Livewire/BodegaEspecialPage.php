<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Trilla;
use Livewire\Component;

class BodegaEspecialPage extends Component
{
    public string $search = '';

    public ?int $expandedRow = null;

    /** @var array<int, int|string> Selected InventoryRecord ids to release to Trilla/Despacho. */
    public array $selected = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    /**
     * Release the selected remisiones into Trilla's pool, same as from the
     * regular Bodega.
     */
    public function enviarATrilla(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_trilla' => true]);

        $this->selected = [];
    }

    /**
     * Release the selected remisiones straight to Despacho, skipping
     * trilla, same as from the regular Bodega.
     */
    public function enviarADespacho(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_despacho' => true]);

        $this->selected = [];
    }

    /**
     * Send a remisión back to the regular Bodega's pool — clears
     * enviado_a_bodega_especial so it shows up there as "Pendiente" again.
     */
    public function reversarABodega(int $id): void
    {
        InventoryRecord::whereKey($id)->update(['enviado_a_bodega_especial' => false]);

        $this->selected = array_values(array_diff($this->selected, [$id]));

        if ($this->expandedRow === $id) {
            $this->expandedRow = null;
        }
    }

    public function render()
    {
        $records = InventoryRecord::with('trillas')
            ->where('enviado_a_bodega_especial', true)
            ->orderByDesc('fecha')
            ->orderByDesc('id')
            ->get()
            ->filter(function (InventoryRecord $r) {
                if ($this->search === '') {
                    return true;
                }

                $haystack = mb_strtolower(implode(' ', [
                    $r->remision, $r->calidad_enviada, $r->cliente,
                ]));

                return str_contains($haystack, mb_strtolower($this->search));
            })
            ->map(function (InventoryRecord $r) {
                $usado = (float) $r->trillas->sum(fn (Trilla $t) => (float) $t->pivot->kg_usado);

                return [
                    'record' => $r,
                    'kg_recibido' => (float) $r->kg_recibidos,
                    'kg_usado_trilla' => $usado,
                    'saldo' => $r->kgDisponible() ?? 0,
                ];
            })
            ->values();

        $totales = [
            'kg_recibido' => $records->sum('kg_recibido'),
            'kg_usado_trilla' => $records->sum('kg_usado_trilla'),
            'saldo' => $records->sum('saldo'),
        ];

        return view('livewire.bodega-especial-page', [
            'movimientos' => $records,
            'totales' => $totales,
        ]);
    }
}
