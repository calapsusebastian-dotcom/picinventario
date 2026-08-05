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
                    <h1>Clientes · Bodega PIC</h1>
                    <p class="subtitle">Catálogo de clientes que alimenta la lista desplegable de Destino</p>
                </div>
            </div>
            <button type="button" class="btn-primary" wire:click="openCreate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo cliente
            </button>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar cliente...">
            </div>
        </div>

        <div class="section-label">Clientes</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($clientes as $c)
                            <tr class="data-row" wire:click="openEdit({{ $c->id }})">
                                <td>{{ $c->nombre }}</td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar" wire:click.stop="openEdit({{ $c->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    <button type="button" class="icon-btn danger" title="Eliminar" wire:click.stop="confirmDelete({{ $c->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                                    </button>
                                </td>
                            </tr>

                            @if ($confirmDeleteId === $c->id)
                                <tr class="confirm-row">
                                    <td colspan="2">
                                        ¿Eliminar "{{ $c->nombre }}"? Esta acción no se puede deshacer.
                                        <button type="button" class="btn-del-confirm" wire:click.stop="delete({{ $c->id }})">Eliminar</button>
                                        <button type="button" class="btn-del-cancel" wire:click.stop="cancelDelete">Cancelar</button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="2">No hay clientes que coincidan con la búsqueda.</td></tr>
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
                <h2>{{ $editingClienteId ? 'Editar cliente' : 'Nuevo cliente' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="save">
                <div class="tab-panel active">
                    <div class="field">
                        <label>Nombre</label>
                        <input wire:model="nombre" placeholder="Ej. Los Mayer">
                        @error('nombre') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns"></div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    {{ $editingClienteId ? 'Guardar cambios' : 'Crear cliente' }}
                </button>
            </div>
        </div>
    </div>
</div>
