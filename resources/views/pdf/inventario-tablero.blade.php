<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de inventario</title>
    <style>
        @page { margin: 0 0 46px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #1B211B; font-size: 10px; margin: 0; }

        /* ---- Solid header band (absolute-positioned children — table-layout
           in dompdf doesn't reliably clip/wrap a fixed-width cell) ---- */
        .header-band { position: relative; width: 100%; height: 92px; background: #0E5C45; }
        .brand-mark { position: absolute; left: 30px; top: 25px; width: 42px; height: 42px; line-height: 40px; border-radius: 10px; background: rgba(255,255,255,.14); border: 1px solid rgba(255,255,255,.35); color: #fff; text-align: center; font-weight: bold; font-size: 14px; }
        .brand-text { position: absolute; left: 86px; top: 24px; }
        .brand-name { font-size: 20px; font-weight: bold; color: #fff; letter-spacing: .01em; }
        .report-title { font-size: 12px; color: #CFEDE1; margin-top: 4px; text-transform: uppercase; letter-spacing: .05em; }
        .meta { position: absolute; right: 30px; top: 27px; width: 260px; text-align: right; font-size: 9.5px; color: #CFEDE1; line-height: 1.7; }
        .meta strong { color: #fff; }

        .body-pad { padding: 20px 30px 0; }

        .filters { background: #E3F5EE; border: 1px solid #B9E3D3; border-radius: 4px; padding: 9px 14px; margin-bottom: 18px; font-size: 10px; color: #0E5C45; }
        .filters strong { color: #0E5C45; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 2px; border: 1px solid #1B211B; }
        table.data th { text-align: left; background: #0E5C45; color: #fff; font-size: 9px; text-transform: uppercase; letter-spacing: .04em; font-weight: bold; padding: 8px 9px; border: 1px solid #0E5C45; }
        table.data td { padding: 7px 9px; font-size: 9.5px; border: 1px solid #D7DAD2; }
        table.data tr.even td { background: #F5F6F3; }
        table.data td.num, table.data th.num { text-align: right; font-family: 'DejaVu Sans Mono', monospace; }
        table.data td.center { text-align: center; }

        .badge { display: inline-block; padding: 2px 8px; border-radius: 3px; font-size: 8.5px; font-weight: bold; }

        tfoot td { padding: 10px 9px; font-weight: bold; font-size: 10.5px; border: 1px solid #0E5C45; background: #0E5C45; color: #fff; }
        tfoot td.num { text-align: right; font-family: 'DejaVu Sans Mono', monospace; }

        .footer { position: fixed; bottom: -34px; left: 0; right: 0; text-align: center; font-size: 8px; color: #9CA39A; border-top: 1px solid #E9EBE6; padding-top: 6px; margin: 0 30px; }
    </style>
</head>
<body>

    <div class="header-band">
        <div class="brand-mark">PIC</div>
        <div class="brand-text">
            <div class="brand-name">BODEGA PIC</div>
            <div class="report-title">Reporte de inventario &middot; Tablero de registros</div>
        </div>
        <div class="meta">
            <strong>Generado:</strong> {{ $generadoEn->format('Y-m-d H:i') }}<br>
            <strong>Elaborado por:</strong> {{ $usuario }}
        </div>
    </div>

    <div class="body-pad">

        <div class="filters">
            @if (count($filtros) > 0)
                <strong>Filtros aplicados:</strong> {{ implode(' &middot; ', $filtros) }}
            @else
                <strong>Filtros aplicados:</strong> Ninguno &mdash; se incluyen todos los registros
            @endif
        </div>

        <table class="data">
            <thead>
                <tr>
                    <th class="center" style="width:26px;">N&deg;</th>
                    <th>Fecha</th>
                    <th>Remisión</th>
                    <th>Calidad</th>
                    <th>Cliente</th>
                    <th class="num">Kg env.</th>
                    <th class="num">Kg rec.</th>
                    <th class="num">Factor rec.</th>
                    <th>Ubicación</th>
                    <th>Estatus</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($registros as $i => $r)
                    <tr class="{{ $i % 2 === 1 ? 'even' : '' }}">
                        <td class="center">{{ $i + 1 }}</td>
                        <td>{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                        <td>{{ $r->remision ?: '—' }}</td>
                        <td>{{ $r->calidad_enviada ?: '—' }}</td>
                        <td>{{ $r->cliente ?: '—' }}</td>
                        <td class="num">{{ number_format((float) $r->kg_enviados, 2, ',', '.') }}</td>
                        <td class="num">{{ number_format((float) $r->kg_recibidos, 2, ',', '.') }}</td>
                        <td class="num">{{ $r->factor_rec ? number_format((float) $r->factor_rec, 2, ',', '.') : '—' }}</td>
                        <td>{{ $r->ubicacionLabel() }}</td>
                        <td>
                            @php
                                $estColors = [
                                    'Despachado' => ['bg' => '#DCF3EC', 'fg' => '#0B6B54'],
                                    'En tránsito' => ['bg' => '#FBF0DC', 'fg' => '#8A5A0B'],
                                    'Reservado' => ['bg' => '#EFE9F8', 'fg' => '#5B3A9E'],
                                ];
                                $ec = $estColors[$r->estatus] ?? ['bg' => '#EEF0F2', 'fg' => '#4B5563'];
                            @endphp
                            <span class="badge" style="background:{{ $ec['bg'] }};color:{{ $ec['fg'] }};">{{ $r->estatus ?: '—' }}</span>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="10" style="text-align:center;padding:22px;color:#6B7368;">No hay registros para los filtros seleccionados.</td></tr>
                @endforelse
            </tbody>
            <tfoot>
                <tr>
                    <td colspan="5">TOTALES &middot; {{ $totales['count'] }} registro{{ $totales['count'] === 1 ? '' : 's' }}</td>
                    <td class="num">{{ number_format($totales['kg_enviados'], 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($totales['kg_recibidos'], 2, ',', '.') }}</td>
                    <td class="num">{{ number_format($totales['factor_rec_ponderado'], 2, ',', '.') }}</td>
                    <td colspan="2"></td>
                </tr>
            </tfoot>
        </table>

    </div>

    <div class="footer">Bodega PIC &middot; Documento generado autom&aacute;ticamente &middot; {{ $generadoEn->format('Y-m-d H:i') }}</div>

</body>
</html>
