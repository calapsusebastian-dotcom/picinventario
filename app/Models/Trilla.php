<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Trilla extends Model
{
    protected $fillable = ['fecha', 'notas'];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function inventoryRecords(): BelongsToMany
    {
        return $this->belongsToMany(InventoryRecord::class, 'trilla_inventory_record')
            ->withPivot('kg_usado')
            ->withTimestamps();
    }

    public function productos(): HasMany
    {
        return $this->hasMany(TrillaProducto::class);
    }

    public function isFullyDespachada(): bool
    {
        return $this->productos()->exists() && ! $this->productos()->whereNull('remision_despacho')->exists();
    }

    /**
     * Keeps the linked remisiones' estatus in sync with this lote's despacho
     * progress: Despachado once every producto has a remisión de despacho,
     * back to En bodega if a despacho gets reverted and it no longer does.
     */
    public function syncRecordsEstatus(): void
    {
        $estatus = $this->isFullyDespachada() ? 'Despachado' : 'En bodega';

        $this->inventoryRecords()->update(['estatus' => $estatus]);
    }
}
