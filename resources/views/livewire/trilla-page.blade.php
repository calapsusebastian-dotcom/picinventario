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
                    <h1>Trilla · Bodega PIC</h1>
                    <p class="subtitle">Bodega · Trilladora — agrupa varias remisiones en un lote y registra los productos que salen</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Remisiones disponibles</div>
                    <div class="kpi-value">{{ $available->count() }}</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-purple-soft);color:var(--pic-purple)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 2L11 13"/><path d="M22 2L15 22l-4-9-9-4 20-7z"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Seleccionadas</div>
                    <div class="kpi-value">{{ count($selected) }}</div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión...">
            </div>
        </div>

        <div class="section-label">Remisiones que puedo trillar</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th class="checkbox-cell"></th><th>Fecha</th><th>Remisión</th><th>Calidad enviada</th><th>Kg rec.</th><th>Cliente</th><th>Estatus</th><th>Progreso</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($available as $r)
                            @php
                                $col = $statusColor($r->estatus);
                                $stages = $r->stageStatus();
                                $done = count(array_filter($stages));
                            @endphp
                            <tr class="data-row" wire:click="toggleExpand({{ $r->id }})">
                                <td class="checkbox-cell" @click.stop="null"><input type="checkbox" wire:model.live="selected" value="{{ $r->id }}"></td>
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">{{ $r->remision ?: '—' }}</td>
                                <td>{{ $r->calidad_enviada ?: '—' }}</td>
                                <td class="mono num">{{ $r->kg_recibidos ?: '0' }}</td>
                                <td>{{ $r->cliente ?: '—' }}</td>
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
                            </tr>

                            @if ($expandedRow === $r->id)
                                <tr class="detail-row">
                                    <td colspan="8">
                                        <div class="detail-grid">
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">General</span></div>
                                                <div class="detail-item"><span>Año</span><span>{{ $r->anio ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Mes</span><span>{{ $r->mes ?: '—' }}</span></div>
                                                <div class="detail-item"><span>N° tulas</span><span>{{ $r->tulas ?: '—' }}</span></div>
                                                <div class="detail-item"><span>N° costal</span><span>{{ $r->costal ?: '—' }}</span></div>
                                            </div>
                                            <div class="detail-block">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Envío · {{ $r->analisis_enviado_por ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Calidad enviada</span><span>{{ $r->calidad_enviada ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Kg enviados</span><span>{{ $r->kg_enviados ? $r->kg_enviados.' kg' : '—' }}</span></div>
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
                                                <div class="detail-item"><span>Cliente</span><span>{{ $r->cliente ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Negocio</span><span>{{ $r->negocio ?: '—' }}</span></div>
                                                <div class="detail-item"><span>Existencia</span><span>{{ $r->existencia ? $r->existencia.' kg' : '—' }}</span></div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="8">No hay remisiones disponibles para trillar.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (count($selected) > 0)
            <div class="selection-bar">
                <span>{{ count($selected) }} remisión(es) seleccionada(s) · {{ number_format((float) $selectedKgRecibidos, 2, ',', '.') }} kg recibidos</span>
                <button type="button" class="btn-primary" wire:click="openProcess">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                    Trillar seleccionadas
                </button>
            </div>
        @endif

        <div class="section-label" style="margin-top:28px;">Lotes de trilla recientes</div>
        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Lote</th><th>Fecha</th><th>Remisiones</th><th>Productos</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($recentTrillas as $t)
                            <tr class="data-row" wire:click="toggleExpandTrilla({{ $t->id }})">
                                <td class="mono">#{{ $t->id }}</td>
                                <td class="mono">{{ $t->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td>{{ $t->inventory_records_count }}</td>
                                <td>{{ $t->productos->pluck('nombre')->implode(', ') ?: '—' }}</td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar lote" wire:click.stop="editTrilla({{ $t->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn danger" title="Revertir lote" wire:click.stop="confirmRevert({{ $t->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </td>
                            </tr>

                            @if ($confirmRevertId === $t->id)
                                <tr class="confirm-row">
                                    <td colspan="5">
                                        ¿Revertir el lote #{{ $t->id }}? Las {{ $t->inventory_records_count }} remisión(es) volverán a estar disponibles para trillar y se perderán los productos registrados. Esta acción no se puede deshacer.
                                        <button type="button" class="btn-del-confirm" wire:click.stop="revertTrilla({{ $t->id }})">Revertir</button>
                                        <button type="button" class="btn-del-cancel" wire:click.stop="cancelRevert">Cancelar</button>
                                    </td>
                                </tr>
                            @endif

                            @if ($expandedTrilla === $t->id)
                                @php
                                    $kgIngresados = (float) $t->inventoryRecords->sum('kg_recibidos');
                                    $kgProductos = (float) $t->productos->sum('kg');
                                    $kgSobrante = $kgIngresados - $kgProductos;
                                @endphp
                                <tr class="detail-row">
                                    <td colspan="5">
                                        <div class="detail-grid">
                                            <div class="detail-block" style="grid-column:1 / -1;padding:0;overflow:hidden;">
                                                <div class="detail-block-head" style="padding:12px 14px 0;"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Remisiones trilladas en este lote</span></div>
                                                @if ($t->inventoryRecords->isNotEmpty())
                                                    <div class="table-scroll">
                                                        <table>
                                                            <thead>
                                                                <tr>
                                                                    <th>Remisión</th><th>Fecha</th><th>Calidad enviada</th><th>Kg env.</th><th>Kg rec.</th><th>Cliente</th><th>Estatus</th>
                                                                </tr>
                                                            </thead>
                                                            <tbody>
                                                                @foreach ($t->inventoryRecords as $ir)
                                                                    @php $irCol = $statusColor($ir->estatus); @endphp
                                                                    <tr>
                                                                        <td class="mono">{{ $ir->remision ?: '—' }}</td>
                                                                        <td class="mono">{{ $ir->fecha?->format('Y-m-d') ?: '—' }}</td>
                                                                        <td>{{ $ir->calidad_enviada ?: '—' }}</td>
                                                                        <td class="mono num">{{ $ir->kg_enviados ?: '0' }}</td>
                                                                        <td class="mono num">{{ $ir->kg_recibidos ?: '0' }}</td>
                                                                        <td>{{ $ir->cliente ?: '—' }}</td>
                                                                        <td><span class="badge" style="background:{{ $irCol['bg'] }};color:{{ $irCol['fg'] }}">{{ $ir->estatus }}</span></td>
                                                                    </tr>
                                                                @endforeach
                                                            </tbody>
                                                        </table>
                                                    </div>
                                                @else
                                                    <div class="detail-item" style="padding:0 14px 12px;"><span>Remisiones</span><span>Ninguna vinculada</span></div>
                                                @endif
                                            </div>
                                            <div class="detail-block" style="grid-column:1 / -1;">
                                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-accent)"></span><span class="detail-title">Productos de salida</span></div>
                                                @forelse ($t->productos as $p)
                                                    <div class="detail-item">
                                                        <span>{{ $p->nombre }}</span>
                                                        <span>{{ $p->kg ? $p->kg.' kg' : '—' }}@if($p->factor) · factor {{ $p->factor }}@endif</span>
                                                    </div>
                                                @empty
                                                    <div class="detail-item"><span>Productos</span><span>Ninguno registrado</span></div>
                                                @endforelse
                                                <div class="detail-item" style="border-top:1px solid var(--pic-line);margin-top:6px;padding-top:8px;">
                                                    <span>Kg ingresados (remisiones)</span>
                                                    <span>{{ number_format($kgIngresados, 2, ',', '.') }} kg</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span>Kg en productos</span>
                                                    <span>{{ number_format($kgProductos, 2, ',', '.') }} kg</span>
                                                </div>
                                                <div class="detail-item">
                                                    <span style="font-weight:700;">Lo que quedó (sin asignar a un producto)</span>
                                                    <span style="font-weight:700;color:{{ $kgSobrante > 0 ? 'var(--pic-amber)' : 'var(--pic-accent-deep)' }};">{{ number_format($kgSobrante, 2, ',', '.') }} kg</span>
                                                </div>
                                            </div>
                                            @if ($t->notas)
                                                <div class="detail-block" style="grid-column:1 / -1;">
                                                    <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">Notas</span></div>
                                                    <div class="detail-item"><span>{{ $t->notas }}</span></div>
                                                </div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="5">Aún no se han creado lotes de trilla.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

    </div>

    {{-- Slide-over form --}}
    <div class="overlay{{ $showDrawer ? ' open' : '' }}" wire:click.self="closeDrawer">
        <div class="drawer">
            <div class="drawer-header">
                <h2>{{ $editingTrillaId ? 'Editar lote #'.$editingTrillaId : 'Trillar '.count($selected).' remisión(es)' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <div style="padding:16px 24px 0;">
                <div class="selection-bar" style="margin-top:0;">
                    <span>Kg recibidos ({{ $editingTrillaId ? 'remisiones de este lote' : 'remisiones seleccionadas' }})</span>
                    <span class="mono" style="font-size:15px;">{{ number_format((float) $drawerKgRecibidos, 2, ',', '.') }} kg</span>
                </div>
                @if ($editingTrilla)
                    <p style="font-size:12.5px;color:var(--pic-ink-soft);margin:8px 0 0;">
                        Este lote agrupa {{ $editingTrilla->inventory_records_count }} remisión(es). Para cambiar cuáles remisiones están en el lote, revierte el lote y vuelve a trillar.
                    </p>
                @endif
            </div>

            <form wire:submit.prevent="save">
                <div class="tab-panel active">
                    <div class="field"><label>Fecha</label><input type="date" wire:model="fecha"></div>
                    <div class="field"><label>Notas</label><input wire:model="notas" placeholder="Opcional"></div>
                </div>

                <div style="padding:0 24px 20px;">
                    <div class="section-label" style="margin:0 0 10px;">Productos que salen de esta trilla</div>

                    @foreach ($productos as $i => $producto)
                        <div class="producto-row">
                            <div class="field">
                                @if ($i === 0)<label>Nombre / tipo</label>@endif
                                <input wire:model="productos.{{ $i }}.nombre" placeholder="Ej. Excelso">
                                @error("productos.{$i}.nombre") <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                            </div>
                            <div class="field">
                                @if ($i === 0)<label>Kg</label>@endif
                                <input type="number" wire:model="productos.{{ $i }}.kg">
                            </div>
                            <div class="field">
                                @if ($i === 0)<label>Factor</label>@endif
                                <input type="number" wire:model="productos.{{ $i }}.factor">
                            </div>
                            <button type="button" class="btn-remove-producto" wire:click="removeProducto({{ $i }})" title="Quitar producto">
                                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                            </button>
                        </div>
                    @endforeach

                    <button type="button" class="btn-add-producto" wire:click="addProducto">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                        Agregar producto
                    </button>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns"></div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    {{ $editingTrillaId ? 'Guardar cambios' : 'Confirmar trilla' }}
                </button>
            </div>
        </div>
    </div>
</div>
