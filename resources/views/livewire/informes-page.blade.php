@php
    $statusColor = function (string $estatus) {
        return match ($estatus) {
            'Despachado' => ['bg' => '#DCF3EC', 'fg' => '#0B6B54'],
            'En tránsito' => ['bg' => '#FBF0DC', 'fg' => '#8A5A0B'],
            'Reservado' => ['bg' => '#EFE9F8', 'fg' => '#5B3A9E'],
            default => ['bg' => '#EEF0F2', 'fg' => '#4B5563'],
        };
    };

    $serieColors = [
        'kg_enviados' => '#0891A6',
        'kg_recibidos' => '#B7791F',
        'kg_trillados' => '#6D4AAE',
        'kg_despachados' => '#0B8457',
    ];
    $serieLabels = [
        'kg_enviados' => 'Kg enviados',
        'kg_recibidos' => 'Kg recibidos',
        'kg_trillados' => 'Kg trillados',
        'kg_despachados' => 'Kg despachados',
    ];

    $mesesData = collect($porMes)->values();
    $n = $mesesData->count();
    $maxKg = $mesesData->flatMap(fn ($m) => [$m['kg_enviados'], $m['kg_recibidos'], $m['kg_trillados'], $m['kg_despachados']])->max() ?: 0;
    $niceMax = $maxKg > 0 ? ceil($maxKg / 100) * 100 : 100;

    $chartW = 760;
    $chartH = 240;
    $padL = 56;
    $padR = 36;
    $padT = 16;
    $padB = 34;
    $plotW = $chartW - $padL - $padR;
    $plotH = $chartH - $padT - $padB;

    $xFor = fn ($i) => $n > 1 ? $padL + ($i / ($n - 1)) * $plotW : $padL + $plotW / 2;
    $yFor = fn ($v) => $padT + $plotH - ($niceMax > 0 ? ($v / $niceMax) * $plotH : 0);
    $mesLabel = fn ($ym) => \Illuminate\Support\Carbon::createFromFormat('Y-m', $ym)->translatedFormat('M Y');

    $points = [];
    foreach ($serieColors as $key => $color) {
        $points[$key] = $mesesData->map(fn ($m, $i) => round($xFor($i), 1).','.round($yFor($m[$key]), 1))->implode(' ');
    }

    $hoverData = $mesesData->map(fn ($m, $i) => [
        'x' => round($xFor($i), 1),
        'label' => $mesLabel($m['mes']),
        'kg_enviados' => round($m['kg_enviados'], 2),
        'kg_recibidos' => round($m['kg_recibidos'], 2),
        'kg_trillados' => round($m['kg_trillados'], 2),
        'kg_despachados' => round($m['kg_despachados'], 2),
    ])->values()->all();

    $maxCliente = collect($porCliente)->max('kg') ?: 0;
    $maxProducto = collect($porProducto)->max('kg_trillado') ?: 0;
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
                    <h1>Informes · Bodega PIC</h1>
                    <p class="subtitle">Panorama general de envíos, recepciones, trilla y despachos</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#E3F1F1;color:#0891A6">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg enviados (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_enviados'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg recibidos (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_recibidos'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-purple-soft);color:var(--pic-purple)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg trillados (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_trillados'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#DCF3EC;color:#0B8457">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg despachados (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_despachados'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Existencia actual</div>
                    <div class="kpi-value">{{ number_format($totales['existencia'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
        </div>

        <div class="section-label">Tendencia mensual (últimos 12 meses)</div>
        <div class="table-card" style="padding:20px;">
            @if ($n === 0)
                <p style="color:var(--pic-ink-soft);font-size:13px;margin:0;">Aún no hay suficiente información para mostrar una tendencia.</p>
            @else
                <div
                    x-data="{
                        points: {{ \Illuminate\Support\Js::from($hoverData) }},
                        hovered: null,
                        onMove(e) {
                            const rect = e.currentTarget.getBoundingClientRect();
                            const scaleX = {{ $chartW }} / rect.width;
                            const mx = (e.clientX - rect.left) * scaleX;
                            let nearest = this.points[0], best = Infinity;
                            for (const p of this.points) {
                                const d = Math.abs(p.x - mx);
                                if (d < best) { best = d; nearest = p; }
                            }
                            this.hovered = nearest;
                        }
                    }"
                    class="informes-trend-wrap"
                    style="position:relative;"
                >
                    <svg class="informes-trend-chart" viewBox="0 0 {{ $chartW }} {{ $chartH }}" style="width:100%;height:auto;display:block;" @mousemove="onMove($event)" @mouseleave="hovered = null">
                        @for ($g = 0; $g <= 4; $g++)
                            @php $gy = $padT + $plotH - ($g / 4) * $plotH; $gv = round(($niceMax / 4) * $g); @endphp
                            <line x1="{{ $padL }}" y1="{{ $gy }}" x2="{{ $chartW - $padR }}" y2="{{ $gy }}" stroke="#E1E0D9" stroke-width="1"/>
                            <text x="{{ $padL - 8 }}" y="{{ $gy + 4 }}" text-anchor="end" font-size="10" fill="#898781">{{ number_format($gv) }}</text>
                        @endfor

                        @foreach ($mesesData as $i => $m)
                            @if ($n <= 6 || $i % 2 === 0 || $i === $n - 1)
                                <text x="{{ round($xFor($i), 1) }}" y="{{ $chartH - 10 }}" text-anchor="middle" font-size="10" fill="#898781">{{ $mesLabel($m['mes']) }}</text>
                            @endif
                        @endforeach

                        @foreach ($serieColors as $key => $color)
                            <polyline points="{{ $points[$key] }}" fill="none" stroke="{{ $color }}" stroke-width="2" stroke-linejoin="round" stroke-linecap="round"/>
                            @foreach ($mesesData as $i => $m)
                                <circle cx="{{ round($xFor($i), 1) }}" cy="{{ round($yFor($m[$key]), 1) }}" r="4" fill="{{ $color }}" stroke="#fff" stroke-width="2"/>
                            @endforeach
                        @endforeach

                        <line x-show="hovered" :x1="hovered?.x ?? 0" :x2="hovered?.x ?? 0" y1="{{ $padT }}" y2="{{ $padT + $plotH }}" stroke="#C3C2B7" stroke-width="1" style="pointer-events:none;" x-cloak/>
                    </svg>

                    <div x-show="hovered" x-cloak style="position:absolute;top:8px;right:8px;background:#fff;border:1px solid var(--pic-line);border-radius:10px;padding:10px 12px;font-size:12px;box-shadow:var(--pic-shadow-md);min-width:160px;pointer-events:none;">
                        <div style="font-weight:700;margin-bottom:6px;" x-text="hovered?.label"></div>
                        @foreach ($serieColors as $key => $color)
                            <div style="display:flex;justify-content:space-between;gap:12px;padding:1px 0;">
                                <span style="color:{{ $color }};">● {{ $serieLabels[$key] }}</span>
                                <span class="mono" x-text="hovered ? Number(hovered.{{ $key }}).toLocaleString('es-CO') + ' kg' : ''"></span>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div style="display:flex;flex-wrap:wrap;gap:16px;margin-top:12px;padding-top:12px;border-top:1px solid var(--pic-line);">
                    @foreach ($serieColors as $key => $color)
                        <div style="display:flex;align-items:center;gap:6px;font-size:12px;color:var(--pic-ink-soft);">
                            <span style="width:10px;height:10px;border-radius:2px;background:{{ $color }};display:inline-block;"></span>
                            {{ $serieLabels[$key] }}
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(320px,1fr));gap:16px;margin-top:20px;">
            <div>
                <div class="section-label">Kg despachados por cliente</div>
                <div class="table-card" style="padding:18px;">
                    @forelse ($porCliente as $row)
                        <div style="margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px;">
                                <span>{{ $row['cliente'] }}</span>
                                <span class="mono" style="color:var(--pic-ink-soft);">{{ number_format($row['kg'], 2, ',', '.') }} kg</span>
                            </div>
                            <div style="height:8px;border-radius:4px;background:var(--pic-line);overflow:hidden;">
                                <div style="height:100%;border-radius:4px;background:#0891A6;width:{{ $maxCliente > 0 ? round($row['kg'] / $maxCliente * 100, 1) : 0 }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--pic-ink-soft);font-size:13px;margin:0;">Aún no hay despachos registrados.</p>
                    @endforelse
                </div>
            </div>

            <div>
                <div class="section-label">Kg trillados por producto</div>
                <div class="table-card" style="padding:18px;">
                    @forelse ($porProducto as $row)
                        <div style="margin-bottom:12px;">
                            <div style="display:flex;justify-content:space-between;font-size:12.5px;margin-bottom:4px;">
                                <span>{{ $row['producto'] }}</span>
                                <span class="mono" style="color:var(--pic-ink-soft);">{{ number_format($row['kg_trillado'], 2, ',', '.') }} kg <small style="color:var(--pic-ink-faint);">({{ number_format($row['kg_despachado'], 2, ',', '.') }} despachado)</small></span>
                            </div>
                            <div style="height:8px;border-radius:4px;background:var(--pic-line);overflow:hidden;">
                                <div style="height:100%;border-radius:4px;background:#6D4AAE;width:{{ $maxProducto > 0 ? round($row['kg_trillado'] / $maxProducto * 100, 1) : 0 }}%;"></div>
                            </div>
                        </div>
                    @empty
                        <p style="color:var(--pic-ink-soft);font-size:13px;margin:0;">Aún no hay productos de trilla registrados.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <div class="toolbar" style="margin-top:28px;">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión, cliente, destino, calidad...">
            </div>
        </div>

        <div class="section-label">Detalle de remisiones ({{ $remisiones->count() }})</div>
        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Remisión</th><th>Calidad</th><th>Cliente</th><th>Kg env.</th><th>Kg rec.</th><th>Kg trillado</th><th>Estatus</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($remisiones as $r)
                            @php
                                $col = $statusColor($r->estatus);
                                $kgTrillado = $r->trillas->sum(fn ($t) => (float) $t->pivot->kg_usado);
                            @endphp
                            <tr>
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">{{ $r->remision ?: '—' }}</td>
                                <td>{{ $r->calidad_enviada ?: '—' }}</td>
                                <td>{{ $r->cliente ?: '—' }}</td>
                                <td class="mono num">{{ $r->kg_enviados ?: '0' }}</td>
                                <td class="mono num">{{ $r->kg_recibidos ?: '0' }}</td>
                                <td class="mono num">{{ $kgTrillado > 0 ? number_format($kgTrillado, 2, ',', '.') : '—' }}</td>
                                <td><span class="badge" style="background:{{ $col['bg'] }};color:{{ $col['fg'] }}">{{ $r->estatus }}</span></td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="8">No hay registros que coincidan con la búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
