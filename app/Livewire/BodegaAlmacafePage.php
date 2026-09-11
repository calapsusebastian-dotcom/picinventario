<?php

namespace App\Livewire;

use App\Models\InventoryRecord;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class BodegaAlmacafePage extends Component
{
    use WithPagination;

    protected string $paginationView = 'livewire.pagination';

    public string $search = '';

    public ?int $expandedRow = null;

    /** @var array<int, int|string> Selected InventoryRecord ids to release to Trilla/Despacho. */
    public array $selected = [];

    public bool $showEnviarATrillaModal = false;
    public string $remisionEnvioTrilla = '';

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    public function toggleExpand(int $id): void
    {
        $this->expandedRow = $this->expandedRow === $id ? null : $id;
    }

    /**
     * Before releasing the selection to Trilla, ask for the remisión that
     * covers this physical shipment from bodega to the trilladora.
     */
    public function abrirEnviarATrilla(): void
    {
        if (empty($this->selected)) {
            return;
        }

        $this->resetErrorBag('remisionEnvioTrilla');
        $this->remisionEnvioTrilla = '';
        $this->showEnviarATrillaModal = true;
    }

    public function cancelarEnviarATrilla(): void
    {
        $this->showEnviarATrillaModal = false;
        $this->remisionEnvioTrilla = '';
        $this->resetErrorBag('remisionEnvioTrilla');
    }

    /**
     * Release the selected remisiones into Trilla's pool, same as from the
     * regular Bodega.
     */
    public function confirmarEnviarATrilla(): void
    {
        $this->validate([
            'remisionEnvioTrilla' => ['required', 'string', 'max:255'],
        ], [
            'remisionEnvioTrilla.required' => 'Ingresa el número de remisión de este envío.',
        ]);

        if (empty($this->selected)) {
            $this->showEnviarATrillaModal = false;

            return;
        }

        InventoryRecord::whereIn('id', $this->selected)->update([
            'enviado_a_trilla' => true,
            'remision_envio_trilla' => $this->remisionEnvioTrilla,
        ]);

        $this->selected = [];
        $this->remisionEnvioTrilla = '';
        $this->showEnviarATrillaModal = false;
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
     * enviado_a_bodega_almacafe so it shows up there as "Pendiente" again.
     */
    public function reversarABodega(int $id): void
    {
        InventoryRecord::whereKey($id)->update(['enviado_a_bodega_almacafe' => false]);

        $this->selected = array_values(array_diff($this->selected, [$id]));

        if ($this->expandedRow === $id) {
            $this->expandedRow = null;
        }
    }

    private const DISP_EXPR = 'GREATEST(COALESCE(inventory_records.kg_recibidos, 0) - COALESCE(piv.usado, 0), 0)';

    private function filteredQuery(): Builder
    {
        return InventoryRecord::query()
            ->leftJoinSub(
                DB::table('trilla_inventory_record')
                    ->select('inventory_record_id')
                    ->selectRaw('SUM(kg_usado) as usado')
                    ->groupBy('inventory_record_id'),
                'piv',
                'piv.inventory_record_id',
                '=',
                'inventory_records.id'
            )
            ->with('trillas')
            ->where('enviado_a_bodega_almacafe', true)
            ->when($this->search !== '', function (Builder $q) {
                $term = '%'.$this->search.'%';
                $q->where(function (Builder $w) use ($term) {
                    $w->where('remision', 'like', $term)
                        ->orWhere('calidad_enviada', 'like', $term)
                        ->orWhere('cliente', 'like', $term);
                });
            })
            ->orderByDesc('fecha')
            ->orderByDesc('id');
    }

    public function render()
    {
        $movimientos = $this->filteredQuery()
            ->select('inventory_records.*')
            ->selectRaw('COALESCE(piv.usado, 0) as kg_usado_trilla')
            ->selectRaw(self::DISP_EXPR.' as saldo')
            ->paginate(50)
            ->through(fn (InventoryRecord $r) => [
                'record' => $r,
                'kg_recibido' => (float) $r->kg_recibidos,
                'kg_usado_trilla' => (float) $r->kg_usado_trilla,
                'saldo' => (float) $r->saldo,
            ]);

        $agg = $this->filteredQuery()
            ->toBase()
            ->reorder()
            ->select(
                DB::raw('COALESCE(SUM(inventory_records.kg_recibidos), 0) as kg_recibido'),
                DB::raw('COALESCE(SUM(COALESCE(piv.usado, 0)), 0) as kg_usado_trilla'),
                DB::raw('COALESCE(SUM('.self::DISP_EXPR.'), 0) as saldo'),
            )
            ->first();

        $totales = [
            'kg_recibido' => (float) $agg->kg_recibido,
            'kg_usado_trilla' => (float) $agg->kg_usado_trilla,
            'saldo' => (float) $agg->saldo,
        ];

        return view('livewire.bodega-almacafe-page', [
            'movimientos' => $movimientos,
            'totales' => $totales,
        ]);
    }
}
