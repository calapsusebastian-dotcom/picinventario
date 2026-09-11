<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Guards the remote MCP endpoint (/mcp) — without this, anyone on the
 * internet could query Bodega PIC's inventory data.
 */
class EnsureMcpToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $expected = config('services.mcp.token');
        $given = $request->bearerToken();

        if (! $expected || ! $given || ! hash_equals($expected, $given)) {
            abort(401, 'Token MCP inválido o ausente.');
        }

        return $next($request);
    }
}
