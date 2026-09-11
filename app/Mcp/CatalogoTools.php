<?php

namespace App\Mcp;

use App\Models\Cliente;
use App\Models\Producto;
use App\Models\Ubicacion;
use PhpMcp\Server\Attributes\McpTool;

/**
 * Read-only MCP tool exposing the app's simple catalogs. See
 * App\Mcp\InventarioTools for the auth/scope notes shared by every tool
 * class in this directory.
 */
class CatalogoTools
{
    #[McpTool(
        name: 'listar_catalogos',
        description: 'Lista los clientes, ubicaciones y productos registrados en el sistema.'
    )]
    public function listarCatalogos(): array
    {
        return [
            'clientes' => Cliente::orderBy('nombre')->pluck('nombre')->all(),
            'ubicaciones' => Ubicacion::orderBy('nombre')->pluck('nombre')->all(),
            'productos' => Producto::orderBy('nombre')->pluck('nombre')->all(),
        ];
    }
}
