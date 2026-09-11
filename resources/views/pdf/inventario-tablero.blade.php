<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de inventario</title>
    <style>
        @page { margin: 26px 28px 40px; }
        * { box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; color: #131813; font-size: 10px; }

        .header { width: 100%; border-bottom: 2px solid #0E5C45; padding-bottom: 10px; margin-bottom: 12px; }
        .header table { width: 100%; border-collapse: collapse; }
        .brand-mark { width: 32px; height: 32px; border-radius: 8px; background: #0E5C45; color: #fff; text-align: center; vertical-align: middle; font-weight: bold; font-size: 12px; }
        .brand-name { font-size: 15px; font-weight: bold; color: #131813; }
        .report-title { font-size: 12px; color: #6B7368; margin-top: 2px; }
        .meta { text-align: right; font-size: 9px; color: #6B7368; line-height: 1.5; }

        .filters { background: #F5F6F3; border: 1px solid #E9EBE6; border-radius: 6px; padding: 7px 10px; margin-bottom: 12px; font-size: 9.5px; color: #4B5563; }
        .filters strong { color: #131813; }

        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { text-align: left; background: #F5F6F3; color: #6B7368; font-size: 8.5px; text-transform: uppercase; letter-spacing: .03em; padding: 6px 7px; border-bottom: 1px solid #D7DAD2; }
        table.data td { padding: 5.5px 7px; font-size: 9.5px; border-bottom: 1px solid #E9EBE6; }
        table.data tr.even td { background: #FAFBF8; }
        table.data td.num { text-align: right; }

        .badge { display: inline-block; padding: 2px 7px; border-radius: 8px; font-size: 8.5px; font-weight: bold; }

        tfoot td { padding: 8px 7px; font-weight: bold; font-size: 10px; border-top: 2px solid #D7DAD2; background: #FAFBF8; }
        tfoot td.num { text-align: right; }

        .footer { position: fixed; bottom: -28px; left: 0; right: 0; text-align: center; font-size: 8px; color: #9CA39A; }
    </style>
</head>
<body>

    <div class="header">
        <table>
            <tr>
                <td style="width:40px;"><div class="brand-mark">PIC</div></td>
                <td>
                    <div class="brand-name">Bodega PIC</div>
                    <div class="report-title">Reporte de inventario &middot; Tablero de registros</div>
                </td>
                <td class="meta">
                    Generado: {{ $generadoEn->format('Y-m-d H:i') }}<br>
                    Por: {{ $usuario }}
                </td>
            </tr>
        </table>
    </div>

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
                <tr><td colspan="9" style="text-align:center;padding:20px;color:#6B7368;">No hay registros para los filtros seleccionados.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4">Totales ({{ $totales['count'] }} registro{{ $totales['count'] === 1 ? '' : 's' }})</td>
                <td class="num">{{ number_format($totales['kg_enviados'], 2, ',', '.') }}</td>
                <td class="num">{{ number_format($totales['kg_recibidos'], 2, ',', '.') }}</td>
                <td class="num">{{ number_format($totales['factor_rec_ponderado'], 2, ',', '.') }}</td>
                <td colspan="2"></td>
            </tr>
        </tfoot>
    </table>

    <div class="footer">Bodega PIC &middot; Documento generado automáticamente &middot; {{ $generadoEn->format('Y-m-d H:i') }}</div>

</body>
</html>
