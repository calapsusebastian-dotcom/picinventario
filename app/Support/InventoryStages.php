<?php

namespace App\Support;

class InventoryStages
{
    /**
     * Stage keys in workflow order. Index 0 (general) is the root record;
     * every other stage depends only on general being complete — not on
     * each other.
     */
    public const ORDER = ['general', 'envio', 'recepcion', 'destino', 'imov'];

    public const META = [
        'general' => ['label' => 'General', 'role_label' => 'Base del registro'],
        'envio' => ['label' => 'Envío', 'role_label' => 'Jorge · Evelyn · Natalia'],
        'recepcion' => ['label' => 'Recepción', 'role_label' => 'Bodega · Calidades · Trilladora'],
        'destino' => ['label' => 'Destino', 'role_label' => 'Comercial'],
        'imov' => ['label' => 'Imov', 'role_label' => 'Natalia · Bodega'],
    ];

    public static function index(string $stage): int
    {
        return array_search($stage, self::ORDER, true);
    }

    public static function label(string $stage): string
    {
        return self::META[$stage]['label'] ?? $stage;
    }

    public static function roleLabel(string $stage): string
    {
        return self::META[$stage]['role_label'] ?? '';
    }
}
