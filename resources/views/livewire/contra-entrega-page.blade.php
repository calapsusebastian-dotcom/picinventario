@php
    $fmt = fn ($n, $dec = 2) => $n === null ? '—' : number_format((float) $n, $dec, ',', '.');
    $fmtDiff = function ($n, $dec = 2, $suffix = '') use ($fmt) {
        if ($n === null) {
            return '—';
        }
        $sign = $n > 0.0001 ? '+' : '';
        return $sign.$fmt($n, $dec).$suffix;
    };
    $diffColor = function ($n) {
        if ($n === null || abs($n) < 0.0001) {
            return 'var(--pic-ink-soft)';
        }
        return $n < 0 ? 'var(--pic-danger)' : 'var(--pic-accent-deep)';
    };
@endphp

@push('head')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Roboto+Mono:wght@400;500&display=swap" rel="stylesheet">
@endpush

<div class="pic-board">
    <div class="wrap">

        <div class="topbar">
            <div class="brand">
                <div class="brand-mark">PIC</div>
                <div>
                    <h1>Contra entrega · Bodega PIC</h1>
                    <p class="subtitle">Compara lo declarado en Envío contra lo verificado en Recepción, remisión por remisión</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-purple-soft);color:var(--pic-purple)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg enviados</div>
                    <div class="kpi-value">{{ $fmt($totales['kg_enviados']) }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg recibidos</div>
                    <div class="kpi-value">{{ $fmt($totales['kg_recibidos']) }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:{{ abs($totales['diff_kg']) < 0.001 ? 'var(--pic-accent-soft)' : 'var(--pic-danger-soft)' }};color:{{ abs($totales['diff_kg']) < 0.001 ? 'var(--pic-accent-deep)' : 'var(--pic-danger)' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 20V4"/><path d="M5 13l7 7 7-7"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Diferencia total (rec. − env.)</div>
                    <div class="kpi-value" style="color:{{ $diffColor($totales['diff_kg']) }}">{{ $fmtDiff($totales['diff_kg']) }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M12 2a10 10 0 00-8.66 15L12 12l8.66 5A10 10 0 0012 2z"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Diferencia %</div>
                    <div class="kpi-value" style="color:{{ $diffColor($totales['diff_kg_pct']) }}">{{ $fmtDiff($totales['diff_kg_pct'], 1, '%') }}</div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión, cliente o calidad...">
            </div>
        </div>

        <div class="section-label">Envío vs. Recepción ({{ $comparaciones->count() }})</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Remisión</th><th>Cliente</th><th>Ubicación</th>
                            <th class="num">Kg env.</th><th class="num">Kg rec.</th><th class="num">Dif. kg</th><th class="num">Dif. %</th>
                            <th class="num">Factor env.</th><th class="num">Factor rec.</th><th class="num">Dif. factor</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($comparaciones as $c)
                            @php $r = $c['record']; @endphp
                            <tr class="data-row" wire:key="ce-{{ $r->id }}" wire:click="toggleExpand({{ $r->id }})">
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">{{ $r->remision ?: '—' }}</td>
                                <td>{{ $r->cliente ?: '—' }}</td>
                                <td>{{ $r->ubicacion ?: '—' }}</td>
                                <td class="mono num">{{ $fmt($r->kg_enviados) }}</td>
                                <td class="mono num">{{ $fmt($r->kg_recibidos) }}</td>
                                <td class="mono num" style="font-weight:700;color:{{ $diffColor($c['diff_kg']) }}">{{ $fmtDiff($c['diff_kg']) }}</td>
                                <td class="mono num" style="color:{{ $diffColor($c['diff_kg_pct']) }}">{{ $fmtDiff($c['diff_kg_pct'], 1, '%') }}</td>
                                <td class="mono num">{{ $fmt($r->factor_env) }}</td>
                                <td class="mono num">{{ $fmt($r->factor_rec) }}</td>
                                <td class="mono num" style="color:{{ $diffColor($c['diff_factor']) }}">{{ $fmtDiff($c['diff_factor']) }}</td>
                            </tr>

                            @if ($expandedRow === $r->id)
                                <tr class="detail-row">
                                    <td colspan="11">
                                        <div class="table-scroll">
                                            <table>
                                                <thead>
                                                    <tr>
                                                        <th>Métrica</th><th class="num">Envío</th><th class="num">Recepción</th><th class="num">Diferencia</th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr>
                                                        <td>Kg</td>
                                                        <td class="mono num">{{ $fmt($r->kg_enviados) }}</td>
                                                        <td class="mono num">{{ $fmt($r->kg_recibidos) }}</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_kg']) }}">{{ $fmtDiff($c['diff_kg']) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Almendra sana</td>
                                                        <td class="mono num">{{ $fmt($r->as_env) }}%</td>
                                                        <td class="mono num">{{ $fmt($r->as_rec) }}%</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_as']) }}">{{ $fmtDiff($c['diff_as'], 2, '%') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Pasilla</td>
                                                        <td class="mono num">{{ $fmt($r->pas_env) }}%</td>
                                                        <td class="mono num">{{ $fmt($r->pas_rec) }}%</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_pas']) }}">{{ $fmtDiff($c['diff_pas'], 2, '%') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Primer grupo</td>
                                                        <td class="mono num">{{ $fmt($r->pg_env) }}%</td>
                                                        <td class="mono num">{{ $fmt($r->pg_rec) }}%</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_pg']) }}">{{ $fmtDiff($c['diff_pg'], 2, '%') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Broca</td>
                                                        <td class="mono num">{{ $fmt($r->broca_env) }}%</td>
                                                        <td class="mono num">{{ $fmt($r->broca_rec) }}%</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_broca']) }}">{{ $fmtDiff($c['diff_broca'], 2, '%') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Humedad</td>
                                                        <td class="mono num">{{ $fmt($r->humedad_env) }}%</td>
                                                        <td class="mono num">{{ $fmt($r->humedad_rec) }}%</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_humedad']) }}">{{ $fmtDiff($c['diff_humedad'], 2, '%') }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Factor</td>
                                                        <td class="mono num">{{ $fmt($r->factor_env) }}</td>
                                                        <td class="mono num">{{ $fmt($r->factor_rec) }}</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_factor']) }}">{{ $fmtDiff($c['diff_factor']) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Puntaje taza</td>
                                                        <td class="mono num">{{ $fmt($r->puntaje_taza_env) }}</td>
                                                        <td class="mono num">{{ $fmt($r->puntaje_taza_rec) }}</td>
                                                        <td class="mono num" style="color:{{ $diffColor($c['diff_puntaje_taza']) }}">{{ $fmtDiff($c['diff_puntaje_taza']) }}</td>
                                                    </tr>
                                                    <tr>
                                                        <td>Taza</td>
                                                        <td class="mono num">{{ $r->taza_env ?: '—' }}</td>
                                                        <td class="mono num">{{ $r->taza_rec ?: '—' }}</td>
                                                        <td class="mono num">
                                                            @if ($c['taza_coincide'] === null)
                                                                —
                                                            @elseif ($c['taza_coincide'])
                                                                <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Coincide</span>
                                                            @else
                                                                <span class="badge" style="background:var(--pic-danger-soft);color:var(--pic-danger);">Difiere</span>
                                                            @endif
                                                        </td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="11">No hay remisiones con Envío y Recepción completos para comparar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
