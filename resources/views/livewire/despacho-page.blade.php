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
                    <h1>Despacho · Bodega PIC</h1>
                    <p class="subtitle">Toma los productos que salieron de cada corte de trilla y asígnales su remisión de despacho</p>
                </div>
            </div>
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Pendientes de despacho</div>
                    <div class="kpi-value">{{ $pendingCount }}</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Despachados</div>
                    <div class="kpi-value">{{ $completedCount }}</div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por producto o remisión de origen...">
            </div>
        </div>

        <div class="section-label">Productos pendientes de despacho</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th><th>Kg</th><th>Factor</th><th>Lote</th><th>Remisiones de origen</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($pending as $p)
                            <tr class="data-row" wire:click="openEdit({{ $p->id }})">
                                <td>{{ $p->nombre }}</td>
                                <td class="mono num">{{ $p->kg ?: '0' }}</td>
                                <td class="mono num">{{ $p->factor ?: '—' }}</td>
                                <td class="mono">{{ $p->trilla ? '#'.$p->trilla_id : '—' }}</td>
                                <td>{{ $p->trilla?->inventoryRecords->pluck('remision')->implode(', ') ?: '—' }}</td>
                                <td>
                                    <button type="button" class="icon-btn" title="Despachar" wire:click.stop="openEdit({{ $p->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6">No hay productos pendientes de despacho.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="section-label" style="margin-top:28px;">Registro de despachos</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Producto</th><th>Kg</th><th>Estado</th><th>Remisión despacho</th><th>Destino</th><th>Lote</th><th>Remisiones de origen</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($despachados as $p)
                            <tr class="data-row" wire:click="openEdit({{ $p->id }})">
                                <td>{{ $p->nombre }}</td>
                                <td class="mono num">{{ $p->kg ?: '0' }}</td>
                                <td><span class="badge" style="background:#DCF3EC;color:#0B6B54;">Despachada</span></td>
                                <td class="mono">{{ $p->remision_despacho }}</td>
                                <td>{{ $p->destino ?: '—' }}</td>
                                <td class="mono">{{ $p->trilla ? '#'.$p->trilla_id : '—' }}</td>
                                <td>{{ $p->trilla?->inventoryRecords->pluck('remision')->implode(', ') ?: '—' }}</td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar despacho" wire:click.stop="openEdit({{ $p->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn danger" title="Revertir despacho" wire:click.stop="confirmRevert({{ $p->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 12a9 9 0 109-9 9.75 9.75 0 00-6.74 2.74L3 8"/><path d="M3 3v5h5"/></svg>
                                    </button>
                                </td>
                            </tr>

                            @if ($confirmRevertId === $p->id)
                                <tr class="confirm-row">
                                    <td colspan="8">
                                        ¿Revertir el despacho de "{{ $p->nombre }}"? Vuelve a quedar pendiente de despacho. Esta acción no se puede deshacer.
                                        <button type="button" class="btn-del-confirm" wire:click.stop="revert({{ $p->id }})">Revertir</button>
                                        <button type="button" class="btn-del-cancel" wire:click.stop="cancelRevert">Cancelar</button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="8">Aún no se ha despachado ningún producto.</td></tr>
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
                <h2 style="display:flex;align-items:center;gap:10px;">
                    {{ $editingProducto && $editingProducto->isDespachado() ? 'Editar despacho' : 'Despachar producto' }}
                    @if ($editingProducto && $editingProducto->isDespachado())
                        <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Despachada</span>
                    @endif
                </h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($editingProducto)
                <div style="padding:16px 24px 0;">
                    <div class="detail-grid">
                        <div class="detail-block">
                            <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-accent)"></span><span class="detail-title">Producto</span></div>
                            <div class="detail-item"><span>Nombre</span><span>{{ $editingProducto->nombre }}</span></div>
                            <div class="detail-item"><span>Kg</span><span>{{ $editingProducto->kg ? $editingProducto->kg.' kg' : '—' }}</span></div>
                            <div class="detail-item"><span>Factor</span><span>{{ $editingProducto->factor ?: '—' }}</span></div>
                        </div>
                        <div class="detail-block">
                            <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Origen · Lote #{{ $editingProducto->trilla_id }}</span></div>
                            <div class="detail-item"><span>Fecha de trilla</span><span>{{ $editingProducto->trilla?->fecha?->format('Y-m-d') ?: '—' }}</span></div>
                            @forelse ($editingProducto->trilla?->inventoryRecords ?? [] as $ir)
                                <div class="detail-item"><span>Remisión</span><span>{{ $ir->remision ?: '—' }}</span></div>
                            @empty
                                <div class="detail-item"><span>Remisiones</span><span>Ninguna vinculada</span></div>
                            @endforelse
                        </div>
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="save">
                <div class="tab-panel active">
                    <div class="field">
                        <label>Número de remisión</label>
                        <input wire:model="remision_despacho" placeholder="D-0000">
                        @error('remision_despacho') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                    <div class="field">
                        <label>Destino</label>
                        <input wire:model="destino" placeholder="Cliente o destino final">
                        @error('destino') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns"></div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    Guardar
                </button>
            </div>
        </div>
    </div>
</div>
