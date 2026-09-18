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
                    <h1>Bodegas · Bodega PIC</h1>
                    <p class="subtitle">Bodegas intermedias donde se pueden mover remisiones desde Bodega, antes de pasar a Trilla o Despacho</p>
                </div>
            </div>
            <button type="button" class="btn-primary" wire:click="openCreate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nueva bodega
            </button>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar bodega...">
            </div>
        </div>

        <div class="section-label">Bodegas</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th><th class="num">Remisiones</th><th>Estado</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($bodegas as $b)
                            <tr class="data-row" wire:key="bodega-{{ $b->id }}">
                                <td>
                                    <a href="{{ route('bodega.detalle', $b) }}" wire:navigate style="color:var(--pic-accent-deep);font-weight:600;">{{ $b->nombre }}</a>
                                </td>
                                <td class="mono num">{{ $b->inventory_records_count }}</td>
                                <td>
                                    @if ($b->activo)
                                        <span class="badge" style="background:#DCF3EC;color:#0B6B54;">Activa</span>
                                    @else
                                        <span class="badge" style="background:var(--pic-line);color:var(--pic-ink-soft);">Inactiva</span>
                                    @endif
                                </td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar" wire:click="openEdit({{ $b->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn{{ $b->activo ? ' danger' : '' }}" title="{{ $b->activo ? 'Desactivar' : 'Activar' }}" wire:click="toggleActivo({{ $b->id }})">
                                        @if ($b->activo)
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M4.9 4.9l14.2 14.2"/></svg>
                                        @else
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                                        @endif
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="4">No hay bodegas que coincidan con la búsqueda.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <p style="margin-top:14px;font-size:12.5px;color:var(--pic-ink-soft);">Una bodega inactiva desaparece del menú y de "Enviar a bodega", pero su página y sus datos siguen disponibles — no se puede eliminar una bodega, solo desactivarla.</p>

    </div>

    {{-- Slide-over form --}}
    <div class="overlay{{ $showDrawer ? ' open' : '' }}" wire:click.self="closeDrawer">
        <div class="drawer">
            <div class="drawer-header">
                <h2>{{ $editingBodegaId ? 'Editar bodega' : 'Nueva bodega' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="save">
                <div class="tab-panel active">
                    <div class="field">
                        <label>Nombre</label>
                        <input wire:model="nombre" placeholder="Ej. Bodega Norte">
                        @error('nombre') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns"></div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    {{ $editingBodegaId ? 'Guardar cambios' : 'Crear bodega' }}
                </button>
            </div>
        </div>
    </div>
</div>
