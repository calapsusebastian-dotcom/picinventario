@php
    $icons = [
        'clipboard' => '<path d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2"/><rect x="9" y="3" width="6" height="4" rx="1"/>',
        'send' => '<path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/>',
        'inbox' => '<path d="M22 12h-6l-2 3h-4l-2-3H2"/><path d="M5.45 5.11L2 12v6a2 2 0 002 2h16a2 2 0 002-2v-6l-3.45-6.89A2 2 0 0016.76 4H7.24a2 2 0 00-1.79 1.11z"/>',
        'box' => '<path d="M21 16V8a2 2 0 00-1-1.73l-7-4a2 2 0 00-2 0l-7 4A2 2 0 003 8v8a2 2 0 001 1.73l7 4a2 2 0 002 0l7-4A2 2 0 0021 16z"/><path d="M3.27 6.96L12 12l8.73-5.04M12 22.08V12"/>',
        'gauge' => '<path d="M12 15a3 3 0 100-6 3 3 0 000 6z"/><path d="M12 2a10 10 0 00-8.66 15L12 12l8.66 5A10 10 0 0012 2z"/>',
        'clock' => '<circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/>',
        'truck' => '<rect x="1" y="3" width="15" height="13"/><path d="M16 8h4l3 3v5h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/>',
    ];
    $colors = [
        'accent' => ['bg' => 'var(--pic-accent-soft)', 'fg' => 'var(--pic-accent-deep)'],
        'purple' => ['bg' => 'var(--pic-purple-soft)', 'fg' => 'var(--pic-purple)'],
        'amber' => ['bg' => 'var(--pic-amber-soft)', 'fg' => 'var(--pic-amber)'],
    ];
    $fmt = fn ($n) => number_format((float) $n, 0, ',', '.');
    $cards = [
        ['label' => 'Registros', 'value' => $summary['registros'], 'unit' => '', 'icon' => 'clipboard', 'color' => 'accent'],
        ['label' => 'Kg enviados', 'value' => $fmt($summary['kg_enviados']), 'unit' => 'kg', 'icon' => 'send', 'color' => 'purple'],
        ['label' => 'Kg en bodega', 'value' => $fmt($summary['kg_en_bodega']), 'unit' => 'kg', 'icon' => 'inbox', 'color' => 'amber'],
        ['label' => 'Kg en trilla', 'value' => $fmt($summary['kg_en_trilla']), 'unit' => 'kg', 'icon' => 'clock', 'color' => 'purple'],
        ['label' => 'Kg en despacho (por despachar)', 'value' => $fmt($summary['kg_en_despacho']), 'unit' => 'kg', 'icon' => 'truck', 'color' => 'accent'],
        ['label' => 'Existencia en bodegas', 'value' => $fmt($summary['existencia']), 'unit' => 'kg', 'icon' => 'box', 'color' => 'accent'],
        ['label' => 'Factor ponderado (recepción)', 'value' => number_format($summary['factor_promedio'], 1), 'unit' => '', 'icon' => 'gauge', 'color' => 'purple'],
    ];
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
                    <h1>Tablero de inventario · Bodega PIC</h1>
                    <p class="subtitle">Sigue cada remisión de café por rol, desde el envío hasta el destino final</p>
                </div>
            </div>
            <button type="button" class="btn-primary" wire:click="openCreate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo registro
            </button>
        </div>

        <div class="kpis">
            @foreach ($cards as $c)
                <div class="kpi-card">
                    <div class="kpi-icon" style="background:{{ $colors[$c['color']]['bg'] }};color:{{ $colors[$c['color']]['fg'] }}">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">{!! $icons[$c['icon']] !!}</svg>
                    </div>
                    <div>
                        <div class="kpi-label">{{ $c['label'] }}</div>
                        <div class="kpi-value">{{ $c['value'] }}@if($c['unit']) <small>{{ $c['unit'] }}</small>@endif</div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión, cliente, destino...">
            </div>
            <select class="f-anio" wire:model.live="filterAnio">
                <option value="Todos">Todos los años</option>
                @foreach ($years as $y)
                    <option value="{{ $y }}">{{ $y }}</option>
                @endforeach
            </select>
            <select class="f-estatus" wire:model.live="filterEstatus">
                <option value="Todos">Todos los estatus</option>
                <option>En bodega</option><option>Despachado</option><option>En tránsito</option><option>Reservado</option>
            </select>
        </div>

        <div class="section-label">Tablero de registros</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Remisión</th><th>Calidad</th><th>Kg env.</th><th>Kg rec.</th><th>Ubicación</th><th>Cliente</th><th>Imov</th><th>Estatus</th><th>Progreso</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $r)
                            @php
                                $col = $statusColor($r->estatus);
                                $stages = $r->stageStatus();
                                $done = count(array_filter($stages));
                            @endphp
                            <tr class="data-row" wire:click="toggleExpand({{ $r->id }})">
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">
                                    {{ $r->remision ?: '—' }}
                                    @foreach ($r->trillas as $t)
                                        <span class="batch-pill" title="Lote de trilla #{{ $t->id }} · usó {{ number_format((float) $t->pivot->kg_usado, 2, ',', '.') }} kg de esta remisión">Trilla #{{ $t->id }}</span>
                                    @endforeach
                                </td>
                                <td>{{ $r->calidad_enviada ?: '—' }}</td>
                                <td class="mono num">{{ $r->kg_enviados ?: '0' }}</td>
                                <td class="mono num">{{ $r->kg_recibidos ?: '0' }}</td>
                                <td>
                                    @php $saldo = $r->kgDisponible(); @endphp
                                    @if ($r->isDespachadoDirecto())
                                        <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Despachado</span>
                                    @elseif ($r->enviado_a_despacho)
                                        <span class="badge" style="background:#E1EFFB;color:#1D5FA8;">En despacho</span>
                                    @elseif ($saldo === null || $saldo <= 0.001)
                                        @if ($r->trillas->isNotEmpty())
                                            <span class="badge" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">Trillado</span>
                                        @else
                                            —
                                        @endif
                                    @elseif ($r->enviado_a_trilla)
                                        <span class="badge" style="background:var(--pic-purple-soft);color:var(--pic-purple)">En trilla</span>
                                    @else
                                        <span class="badge" style="background:var(--pic-amber-soft);color:var(--pic-amber)">En bodega</span>
                                    @endif
                                </td>
                                <td>{{ $r->cliente ?: '—' }}</td>
                                <td class="mono num">{{ $r->imov ?: '—' }}</td>
                                <td><span class="badge" style="background:{{ $col['bg'] }};color:{{ $col['fg'] }}">{{ $r->estatus }}</span></td>
                                <td>
                                    <div class="progress-wrap">
                                        <div class="progress">
                                            @foreach ($stages as $d)
                                                <span class="dot{{ $d ? ' done' : '' }}"></span>
                                            @endforeach
                                        </div>
                                        <span class="progress-caption">{{ $done }}/5 roles</span>
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar" wire:click.stop="openEdit({{ $r->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn danger" title="Eliminar" wire:click.stop="confirmDelete({{ $r->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                                    </button>
                                </td>
                            </tr>

                            @if ($confirmDeleteId === $r->id)
                                <tr class="confirm-row">
                                    <td colspan="11">
                                        ¿Eliminar el registro {{ $r->remision ?: 'sin remisión' }}? Esta acción no se puede deshacer.
                                        <button type="button" class="btn-del-confirm" wire:click.stop="delete({{ $r->id }})">Eliminar</button>
                                        <button type="button" class="btn-del-cancel" wire:click.stop="cancelDelete">Cancelar</button>
                                    </td>
                                </tr>
                            @endif

                            @if ($expandedRow === $r->id)
                                <tr class="detail-row">
                                    <td colspan="11">
                                        <div class="detail-grid">
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Envío · {{ $r->analisis_enviado_por ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Calidad enviada</span><span>{{ $r->calidad_enviada ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Kg enviados</span><span>{{ $r->kg_enviados ? $r->kg_enviados.' kg' : '—' }}</span></div>
                                                <div class="detail-item"><span>N° tulas</span><span>{{ $r->tulas ?: '—' }}</span></div>
                                                <div class="detail-item"><span>N° costal</span><span>{{ $r->costal ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Almendra sana</span><span>{{ $r->as_env ? $r->as_env.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Pasilla</span><span>{{ $r->pas_env ? $r->pas_env.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Primer grupo</span><span>{{ $r->pg_env ? $r->pg_env.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Broca</span><span>{{ $r->broca_env ? $r->broca_env.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Humedad</span><span>{{ $r->humedad_env ? $r->humedad_env.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Factor</span><span>{{ $r->factor_env ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Taza</span><span>{{ $r->taza_env ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Puntaje taza</span><span>{{ $r->puntaje_taza_env ?: '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-amber)"></span><span class="detail-title">Recepción · {{ $r->analisis_recibido_por ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Kg recibidos</span><span>{{ $r->kg_recibidos ? $r->kg_recibidos.' kg' : '—' }}</span></div>
                                                <div class="detail-item"><span>Almendra sana</span><span>{{ $r->as_rec ? $r->as_rec.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Pasilla</span><span>{{ $r->pas_rec ? $r->pas_rec.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Primer grupo</span><span>{{ $r->pg_rec ? $r->pg_rec.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Broca</span><span>{{ $r->broca_rec ? $r->broca_rec.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Humedad</span><span>{{ $r->humedad_rec ? $r->humedad_rec.'%' : '—' }}</span></div>
                                                <div class="detail-item"><span>Factor</span><span>{{ $r->factor_rec ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Taza</span><span>{{ $r->taza_rec ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Puntaje taza</span><span>{{ $r->puntaje_taza_rec ?: '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-accent)"></span><span class="detail-title">Destino</span></div>
                                                <div class="detail-item"><span>Destino</span><span>{{ $r->destino ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Negocio</span><span>{{ $r->negocio ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Existencia</span><span>{{ $r->existencia ? $r->existencia.' kg' : '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">Imov</span></div>
                                                <div class="detail-item"><span>Imov</span><span>{{ $r->imov ?: '—' }}</span></div>
                                            </div>
                                            @foreach ($r->trillas as $t)
                                                <div class="detail-block" style="grid-column:1 / -1;">
                                                    <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">Trilla · Lote #{{ $t->id }} ({{ $t->fecha?->format('Y-m-d') ?: '—' }}) · {{ number_format((float) $t->pivot->kg_usado, 2, ',', '.') }} kg de esta remisión</span></div>
                                                    @forelse ($t->productos as $p)
                                                        <div class="detail-item"><span>{{ $p->nombre }}</span><span>{{ $p->kg ? $p->kg.' kg' : '—' }}@if($p->factor) · factor {{ $p->factor }}@endif · {{ $p->remision_despacho ? 'Despachado ('.$p->remision_despacho.')'.($p->destino ? ' → '.$p->destino : '') : 'Sin despachar' }}</span></div>
                                                    @empty
                                                        <div class="detail-item"><span>Productos</span><span>Sin productos registrados</span></div>
                                                    @endforelse
                                                </div>
                                            @endforeach
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="11">No hay registros que coincidan con los filtros.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Slide-over form --}}
    <div class="overlay{{ $showDrawer ? ' open' : '' }}" wire:click.self="closeDrawer">
        <div class="drawer" x-data="{ lockVisible: false, lockText: '' }" x-on:lock-attempted.window="lockText = $event.detail.message; lockVisible = true; clearTimeout(window.__picLockTimer); window.__picLockTimer = setTimeout(() => lockVisible = false, 2600)">
            <div class="drawer-header">
                <h2>{{ $editingId ? 'Editar registro' : 'Nuevo registro' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div class="tabs">
                @php $status = $form->stageStatus(); @endphp
                @foreach ($sections as $idx => $s)
                    @php $locked = $this->isTabLocked($idx, $status); @endphp
                    <button type="button" class="tab{{ $activeSection === $idx ? ' active' : '' }}{{ $locked ? ' locked' : '' }}" wire:click="attemptSwitch({{ $idx }})">
                        <span class="tab-label">{{ $s['label'] }}</span>
                        <span class="tab-role">
                            @if ($locked)
                                <svg width="10" height="10" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><rect x="5" y="11" width="14" height="10" rx="2"/><path d="M7 11V7a5 5 0 0110 0v4"/></svg>
                            @endif
                            <span>{{ $s['role'] }}</span>
                        </span>
                    </button>
                @endforeach
            </div>

            <div class="lock-msg" :class="{ show: lockVisible }" x-text="lockText"></div>

            <form wire:submit.prevent="save">
                <div class="tab-panel{{ $activeSection === 0 ? ' active' : '' }}">
                    <div class="field"><label>Año</label><input wire:model="form.anio">@error('form.anio') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Mes</label>
                        <select wire:model="form.mes">
                            <option value="">Selecciona...</option>
                            <option>Enero</option><option>Febrero</option><option>Marzo</option><option>Abril</option><option>Mayo</option><option>Junio</option>
                            <option>Julio</option><option>Agosto</option><option>Septiembre</option><option>Octubre</option><option>Noviembre</option><option>Diciembre</option>
                        </select>
                        @error('form.mes') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field"><label>Fecha</label><input type="date" wire:model.live="form.fecha">@error('form.fecha') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Remisión</label><input wire:model.live="form.remision" placeholder="R-0000">@error('form.remision') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>N° tulas</label><input type="number" wire:model="form.tulas">@error('form.tulas') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>N° costal</label><input type="number" wire:model="form.costal">@error('form.costal') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                </div>

                <div class="tab-panel{{ $activeSection === 1 ? ' active' : '' }}">
                    <div class="field">
                        <label>Calidad enviada</label>
                        <select wire:model="form.calidad_enviada">
                            <option value="">Selecciona...</option>
                            @foreach ($productos as $p)
                                <option value="{{ $p }}">{{ $p }}</option>
                            @endforeach
                        </select>
                        @error('form.calidad_enviada') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field"><label>Kg enviados</label><input type="number" wire:model.live="form.kg_enviados">@error('form.kg_enviados') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Análisis enviado por</label>
                        <select wire:model="form.analisis_enviado_por"><option value="">Selecciona...</option><option>Jorge</option><option>Evelyn</option><option>Natalia</option></select>
                        @error('form.analisis_enviado_por') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div></div>
                    <div class="field"><label>Almendra sana <small>(%)</small></label><input type="number" wire:model="form.as_env">@error('form.as_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Pasilla <small>(%)</small></label><input type="number" wire:model="form.pas_env">@error('form.pas_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Primer grupo <small>(%)</small></label><input type="number" wire:model="form.pg_env">@error('form.pg_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Broca <small>(%)</small></label><input type="number" wire:model="form.broca_env">@error('form.broca_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Humedad <small>(%)</small></label><input type="number" wire:model="form.humedad_env">@error('form.humedad_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Factor</label><input type="number" wire:model="form.factor_env">@error('form.factor_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Taza</label><input wire:model="form.taza_env" placeholder="Notas de catación">@error('form.taza_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Puntaje de taza</label><input type="number" wire:model="form.puntaje_taza_env">@error('form.puntaje_taza_env') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                </div>

                <div class="tab-panel{{ $activeSection === 2 ? ' active' : '' }}">
                    <div class="field"><label>Análisis recibido por</label>
                        <select wire:model="form.analisis_recibido_por"><option value="">Selecciona...</option><option>Bodega</option><option>Calidades</option><option>Trilladora</option></select>
                        @error('form.analisis_recibido_por') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field"><label>Kg recibidos</label><input type="number" wire:model.live="form.kg_recibidos">@error('form.kg_recibidos') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Almendra sana <small>(%)</small></label><input type="number" wire:model="form.as_rec">@error('form.as_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Pasilla <small>(%)</small></label><input type="number" wire:model="form.pas_rec">@error('form.pas_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Primer grupo <small>(%)</small></label><input type="number" wire:model="form.pg_rec">@error('form.pg_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Broca <small>(%)</small></label><input type="number" wire:model="form.broca_rec">@error('form.broca_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Humedad <small>(%)</small></label><input type="number" wire:model="form.humedad_rec">@error('form.humedad_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Factor</label><input type="number" wire:model="form.factor_rec">@error('form.factor_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Taza</label><input wire:model="form.taza_rec" placeholder="Notas de catación">@error('form.taza_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Puntaje de taza</label><input type="number" wire:model="form.puntaje_taza_rec">@error('form.puntaje_taza_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                </div>

                <div class="tab-panel{{ $activeSection === 3 ? ' active' : '' }}">
                    <div class="field"><label>Destino</label><input wire:model="form.destino">@error('form.destino') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field">
                        <label>Cliente</label>
                        <select wire:model.live="form.cliente">
                            <option value="">Selecciona...</option>
                            @foreach ($clientes as $c)
                                <option value="{{ $c }}">{{ $c }}</option>
                            @endforeach
                        </select>
                        @error('form.cliente') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field"><label>Negocio</label><input wire:model="form.negocio">@error('form.negocio') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    <div class="field"><label>Estatus</label>
                        <select wire:model="form.estatus"><option>En bodega</option><option>Despachado</option><option>En tránsito</option><option>Reservado</option></select>
                        @error('form.estatus') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field"><label>Existencia <small>(kg)</small></label><input type="number" wire:model="form.existencia">@error('form.existencia') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                </div>

                <div class="tab-panel{{ $activeSection === 4 ? ' active' : '' }}">
                    <div class="field"><label>Imov</label><input type="number" wire:model="form.imov">@error('form.imov') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns">
                    <button type="button" class="btn-ghost" wire:click="prevSection" @if($activeSection === 0) disabled @endif>← Atrás</button>
                    <button type="button" class="btn-ghost" wire:click="nextSection" @if($activeSection === count($sections) - 1) disabled @endif>Siguiente →</button>
                </div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Guardar registro
                </button>
            </div>
        </div>
    </div>
</div>
