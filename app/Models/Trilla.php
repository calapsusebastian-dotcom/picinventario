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
     * Marca las remisiones vinculadas como Despachado cuando todos los
     * productos del lote ya tienen remisión de despacho asignada.
     */
    public function syncRecordsEstatus(): void
    {
        if ($this->isFullyDespachada()) {
            $this->inventoryRecords()->update(['estatus' => 'Despachado']);
        }
    }
}
