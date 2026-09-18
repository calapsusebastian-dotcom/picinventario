<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Bodega extends Model
{
    protected $fillable = ['nombre', 'slug', 'activo'];

    protected $casts = [
        'activo' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::creating(function (Bodega $bodega) {
            if (! $bodega->slug) {
                $bodega->slug = static::uniqueSlug($bodega->nombre);
            }
        });
    }

    /** Route-model binding uses the slug instead of the id. */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    public function inventoryRecords(): HasMany
    {
        return $this->hasMany(InventoryRecord::class, 'bodega_actual_id');
    }

    private static function uniqueSlug(string $nombre): string
    {
        $base = Str::slug($nombre) ?: 'bodega';
        $slug = $base;
        $i = 2;

        while (static::where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
