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
                    <h1>Stock · Bodega PIC</h1>
                    <p class="subtitle">Kg en bodega por producto — baja automáticamente cada vez que se despacha</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-purple-soft);color:var(--pic-purple)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg trillados (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_trillado'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:#DCF3EC;color:#0B8457">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg despachados (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_despachado'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12l8.73-5.04"/><path d="M12 22.08V12"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Stock actual (sin despachar)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_stock'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar producto...">
            </div>
        </div>

        <div class="section-label">Stock por producto</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th><th>Kg trillado</th><th>Kg despachado</th><th>Stock actual</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($filas as $fila)
                            <tr class="data-row" wire:click="toggleExpand('{{ $fila['nombre'] }}')">
                                <td>{{ $fila['nombre'] }}</td>
                                <td class="mono num">{{ number_format($fila['kg_trillado'], 2, ',', '.') }}</td>
                                <td class="mono num">{{ number_format($fila['kg_despachado'], 2, ',', '.') }}</td>
                                <td class="mono num" style="font-weight:700;">{{ number_format($fila['kg_stock'], 2, ',', '.') }}</td>
                                <td>
                                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="color:var(--pic-ink-faint);transform:rotate({{ $expandedProducto === $fila['nombre'] ? '180deg' : '0deg' }});transition:transform .12s;"><path d="M6 9l6 6 6-6"/></svg>
                                </td>
                            </tr>

                            @if ($expandedProducto === $fila['nombre'])
                                <tr class="detail-row">
                                    <td colspan="5">
                                        <div class="detail-block" style="grid-column:1 / -1;padding:0;overflow:hidden;">
                                            <div class="detail-block-head" style="padding:12px 14px 0;"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Lotes que aportan a este producto</span></div>
                                            @if ($fila['lotes']->isNotEmpty())
                                                <div class="table-scroll">
                                                    <table>
                                                        <thead>
                                                            <tr>
                                                                <th>Lote</th><th>Kg</th><th>Factor</th><th>Remisiones de origen</th><th>Estado</th>
                                                            </tr>
                                                        </thead>
                                                        <tbody>
                                                            @foreach ($fila['lotes'] as $p)
                                                                <tr>
                                                                    <td class="mono">{{ $p->trilla ? '#'.$p->trilla_id : '—' }}</td>
                                                                    <td class="mono num">{{ number_format((float) $p->kg, 2, ',', '.') }}</td>
                                                                    <td class="mono num">{{ $p->factor ?: '—' }}</td>
                                                                    <td>{{ $p->trilla?->inventoryRecords->pluck('remision')->implode(', ') ?: '—' }}</td>
                                                                    <td>
                                                                        @if ($p->isDespachado())
                                                                            <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Despachado ({{ $p->remision_despacho }})</span>
                                                                        @else
                                                                            <span class="badge" style="background:var(--pic-amber-soft);color:var(--pic-amber);">Sin despachar</span>
                                                                        @endif
                                                                    </td>
                                                                </tr>
                                                            @endforeach
                                                        </tbody>
                                                    </table>
                                                </div>
                                            @else
                                                <div class="detail-item" style="padding:0 14px 12px;"><span>Lotes</span><span>Ninguno registrado todavía</span></div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="5">No hay productos que coincidan con la búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>
