<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrillaProducto extends Model
{
    protected $fillable = ['trilla_id', 'nombre', 'kg', 'factor', 'remision_despacho', 'destino'];

    protected $casts = [
        'kg' => 'decimal:2',
        'factor' => 'decimal:2',
    ];

    public function trilla(): BelongsTo
    {
        return $this->belongsTo(Trilla::class);
    }

    public function isDespachado(): bool
    {
        return (bool) $this->remision_despacho;
    }
}
