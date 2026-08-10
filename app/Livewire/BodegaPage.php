<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use App\Models\Trilla;
use Livewire\Component;

class BodegaPage extends Component
{
    public string $search = '';

    public ?int $expandedRow = null;

    /** @var array<int, int|string> Selected InventoryRecord ids to release to Trilla. */
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
     * Release the selected remisiones into Trilla's pool. Trilla itself
     * still decides how much of each to actually use and what comes out —
     * this just makes them visible/selectable over there.
     */
    public function enviarATrilla(): void
    {
        if (empty($this->selected)) {
            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update(['enviado_a_trilla' => true]);

        $this->selected = [];
    }

    public function render()
    {
        $records = InventoryRecord::with('trillas')
            ->whereNotNull('kg_recibidos')
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

        return view('livewire.bodega-page', [
            'movimientos' => $records,
            'totales' => $totales,
        ]);
    }
}
