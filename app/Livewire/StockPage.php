<?php

namespace App\Livewire;

use App\Models\Producto;
use App\Models\TrillaProducto;
use Livewire\Component;

class StockPage extends Component
{
    public string $search = '';

    public ?string $expandedProducto = null;

    public function mount(): void
    {
        abort_unless(auth()->user()->isAdmin(), 403);
    }

    public function toggleExpand(string $nombre): void
    {
        $this->expandedProducto = $this->expandedProducto === $nombre ? null : $nombre;
    }

    public function render()
    {
        $todos = TrillaProducto::with('trilla.inventoryRecords')->orderByDesc('id')->get();

        // Union the catalog (so a product with zero movement still shows,
        // at 0 stock) with whatever names actually appear on trilla output,
        // since some entries predate the Productos catalog existing.
        $nombres = Producto::orderBy('nombre')->pluck('nombre')
            ->merge($todos->pluck('nombre'))
            ->unique()
            ->filter()
            ->sort()
            ->values();

        $filas = $nombres
            ->map(function (string $nombre) use ($todos) {
                $lotes = $todos->where('nombre', $nombre);
                $trillado = (float) $lotes->sum('kg');
                $despachado = (float) $lotes->whereNotNull('remision_despacho')->sum('kg');

                return [
                    'nombre' => $nombre,
                    'kg_trillado' => $trillado,
                    'kg_despachado' => $despachado,
                    'kg_stock' => max(0, $trillado - $despachado),
                    'lotes' => $lotes->values(),
                ];
            })
            ->filter(function (array $fila) {
                if ($this->search === '') {
                    return true;
                }

                return str_contains(mb_strtolower($fila['nombre']), mb_strtolower($this->search));
            })
            ->sortByDesc('kg_stock')
            ->values();

        $totales = [
            'kg_trillado' => $filas->sum('kg_trillado'),
            'kg_despachado' => $filas->sum('kg_despachado'),
            'kg_stock' => $filas->sum('kg_stock'),
        ];

        return view('livewire.stock-page', [
            'filas' => $filas,
            'totales' => $totales,
        ]);
    }
}
