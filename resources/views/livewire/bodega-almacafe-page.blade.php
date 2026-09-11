@php
    $statusColor = function (string $estatus) {
        return match ($estatus) {
            'Despachado' => ['bg' => '#DCF3EC', 'fg' => '#0B6B54'],
            'En tránsito' => ['bg' => '#FBF0DC', 'fg' => '#8A5A0B'],
            'Reservado' => ['bg' => '#EFE9F8', 'fg' => '#5B3A9E'],
            default => ['bg' => '#EEF0F2', 'fg' => '#4B5563'],
        };
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
                    <h1>Bodega Almacafe · Bodega PIC</h1>
                    <p class="subtitle">Remisiones movidas desde Bodega a la bodega Almacafe — elige cuáles pasan a Trilla o Despacho</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 8v13H3V8"/><path d="M1 3h22v5H1z"/><path d="M10 12h4"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg recibidos (total)</div>
                    <div class="kpi-value">{{ number_format($totales['kg_recibido'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-purple-soft);color:var(--pic-purple)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Kg enviados a trilla</div>
                    <div class="kpi-value">{{ number_format($totales['kg_usado_trilla'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12l8.73-5.04"/><path d="M12 22.08V12"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Saldo en bodega Almacafe</div>
                    <div class="kpi-value">{{ number_format($totales['saldo'], 2, ',', '.') }} <small>kg</small></div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión, calidad o cliente...">
            </div>
        </div>

        <div class="section-label">Movimientos de bodega Almacafe ({{ $movimientos->total() }})</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell"></th><th>Fecha</th><th>Remisión</th><th>Calidad</th><th>Cliente</th><th class="num">Kg recibido</th><th class="num">Kg a trilla</th><th class="num">Saldo</th><th>Estatus</th><th>Trilla</th><th>Despacho</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($movimientos as $mov)
                            @php
                                $r = $mov['record'];
                                $col = $statusColor($r->estatus);
                            @endphp
                            <tr class="data-row" wire:key="mov-{{ $r->id }}" wire:click="toggleExpand({{ $r->id }})">
                                <td class="checkbox-cell" @click.stop="null">
                                    @if ($mov['saldo'] > 0.001 && ! $r->enviado_a_trilla && ! $r->enviado_a_despacho)
                                        <input type="checkbox" wire:model.live="selected" value="{{ $r->id }}">
                                    @endif
                                </td>
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">{{ $r->remision ?: '—' }}</td>
                                <td>{{ $r->calidad_enviada ?: '—' }}</td>
                                <td>{{ $r->cliente ?: '—' }}</td>
                                <td class="mono num">{{ number_format($mov['kg_recibido'], 2, ',', '.') }}</td>
                                <td class="mono num">{{ number_format($mov['kg_usado_trilla'], 2, ',', '.') }}</td>
                                <td class="mono num" style="font-weight:700;">{{ number_format($mov['saldo'], 2, ',', '.') }}</td>
                                <td><span class="badge" style="background:{{ $col['bg'] }};color:{{ $col['fg'] }}">{{ $r->estatus }}</span></td>
                                <td>
                                    @if ($r->enviado_a_trilla)
                                        <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Enviada</span>
                                    @else
                                        <span class="badge" style="background:var(--pic-line);color:var(--pic-ink-soft);">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    @if ($r->enviado_a_despacho)
                                        <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Enviada</span>
                                    @else
                                        <span class="badge" style="background:var(--pic-line);color:var(--pic-ink-soft);">Pendiente</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="icon-btn danger" title="Reversar a bodega" wire:click.stop="reversarABodega({{ $r->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </td>
                            </tr>

                            @if ($expandedRow === $r->id)
                                <tr class="detail-row">
                                    <td colspan="12">
                                        <div class="detail-grid">
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">General</span></div>
                                                <div class="detail-item"><span>Año</span><span>{{ $r->anio ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Mes</span><span>{{ $r->mes ?: '—' }}</span></div>
                                                <div class="detail-item"><span>N° tulas</span><span>{{ $r->tulas ?: '—' }}</span></div>
                                                <div class="detail-item"><span>N° costal</span><span>{{ $r->costal ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Ubicación</span><span>{{ $r->ubicacion ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Observación</span><span>{{ $r->observacion ?: '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Envío · {{ $r->analisis_enviado_por ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Calidad enviada</span><span>{{ $r->calidad_enviada ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Kg enviados</span><span>{{ $r->kg_enviados ? $r->kg_enviados.' kg' : '—' }}</span></div>
                                                <div class="detail-item"><span>Factor</span><span>{{ $r->factor_env ?: '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-amber)"></span><span class="detail-title">Recepción · {{ $r->analisis_recibido_por ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Kg recibidos</span><span>{{ $r->kg_recibidos ? $r->kg_recibidos.' kg' : '—' }}</span></div>
                                                <div class="detail-item"><span>Factor</span><span>{{ $r->factor_rec ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Humedad</span><span>{{ $r->humedad_rec ? $r->humedad_rec.'%' : '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-accent)"></span><span class="detail-title">Bodega Almacafe (saldo)</span></div>
                                                <div class="detail-item"><span>Kg recibidos</span><span>{{ number_format($mov['kg_recibido'], 2, ',', '.') }} kg</span></div>
                                                <div class="detail-item"><span>Kg enviados a trilla</span><span>{{ number_format($mov['kg_usado_trilla'], 2, ',', '.') }} kg</span></div>
                                                @if ($r->remision_envio_trilla)
                                                    <div class="detail-item"><span>Remisión envío a trilla</span><span>{{ $r->remision_envio_trilla }}</span></div>
                                                @endif
                                                <div class="detail-item" style="border-top:1px solid var(--pic-line);margin-top:6px;padding-top:8px;"><span style="font-weight:700;">Saldo</span><span style="font-weight:700;">{{ number_format($mov['saldo'], 2, ',', '.') }} kg</span></div>
                                            </div>
                                            @if ($r->trillas->isNotEmpty())
                                                <div class="detail-block" style="grid-column:1 / -1;">
                                                    <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">Lotes de trilla que usaron esta remisión</span></div>
                                                    @foreach ($r->trillas as $t)
                                                        <div class="detail-item"><span>Lote #{{ $t->id }} ({{ $t->fecha?->format('Y-m-d') ?: '—' }})</span><span>{{ number_format((float) $t->pivot->kg_usado, 2, ',', '.') }} kg</span></div>
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="12">No hay remisiones en la bodega Almacafe.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $movimientos->links('livewire.pagination') }}
        </div>

        @if (count($selected) > 0)
            <div class="selection-bar">
                <span>{{ count($selected) }} remisión(es) seleccionada(s)</span>
                <button type="button" class="btn-primary" wire:click="abrirEnviarATrilla">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M5 12h14"/><path d="M12 5l7 7-7 7"/></svg>
                    Enviar a trilla
                </button>
                <button type="button" class="btn-primary" style="background:var(--pic-accent-deep);" wire:click="enviarADespacho">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    Enviar a despacho
                </button>
            </div>
        @endif

    </div>

    {{-- Enviar a trilla: pide la remisión del envío antes de confirmar --}}
    <div class="overlay overlay-center{{ $showEnviarATrillaModal ? ' open' : '' }}" wire:click.self="cancelarEnviarATrilla">
        <div class="mini-modal">
            <div class="mini-modal-header">
                <h2>Enviar a trilla</h2>
                <button type="button" wire:click="cancelarEnviarATrilla">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>
            <form wire:submit.prevent="confirmarEnviarATrilla">
                <div class="mini-modal-body">
                    <p style="margin:0 0 14px;font-size:13px;color:var(--pic-ink-soft);">{{ count($selected) }} remisión(es) seleccionada(s). Indica la remisión de este envío a la trilladora.</p>
                    <div class="field">
                        <label>Número de remisión</label>
                        <input wire:model="remisionEnvioTrilla" placeholder="R-0000" autofocus>
                        @error('remisionEnvioTrilla') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                </div>
                <div class="mini-modal-footer">
                    <button type="button" class="btn-secondary" wire:click="cancelarEnviarATrilla">Cancelar</button>
                    <button type="submit" class="btn-primary">Confirmar envío</button>
                </div>
            </form>
        </div>
    </div>
</div>
