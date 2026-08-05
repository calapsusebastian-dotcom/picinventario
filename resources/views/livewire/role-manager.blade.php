@php
    $roleColor = function (string $role) {
        return match ($role) {
            'admin' => ['bg' => '#EFE9F8', 'fg' => '#5B3A9E'],
            'trilla' => ['bg' => '#FBF0DC', 'fg' => '#8A5A0B'],
            default => ['bg' => '#DCF3EC', 'fg' => '#0B6B54'],
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
                    <h1>Administrador de roles · Bodega PIC</h1>
                    <p class="subtitle">Crea usuarios y asigna a qué paso del flujo tiene acceso cada uno</p>
                </div>
            </div>
            <button type="button" class="btn-primary" wire:click="openCreate">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                Nuevo usuario
            </button>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por nombre o email...">
            </div>
        </div>

        <div class="section-label">Usuarios</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Nombre</th><th>Email</th><th>Rol</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $u)
                            <tr class="data-row" wire:click="openEdit({{ $u->id }})">
                                <td>{{ $u->name }}@if($u->id === auth()->id()) <span class="mono" style="color:var(--pic-ink-faint);font-size:11px;">(tú)</span>@endif</td>
                                <td class="mono">{{ $u->email }}</td>
                                <td>
                                    <div style="display:flex;flex-wrap:wrap;gap:4px;">
                                        @foreach ($u->roles ?? [] as $role)
                                            @php $col = $roleColor($role); @endphp
                                            <span class="badge" style="background:{{ $col['bg'] }};color:{{ $col['fg'] }}">{{ \App\Livewire\RoleManager::ROLE_LABELS[$role] ?? $role }}</span>
                                        @endforeach
                                    </div>
                                </td>
                                <td>
                                    <button type="button" class="icon-btn" title="Editar" wire:click.stop="openEdit({{ $u->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                    @if ($u->id !== auth()->id())
                                        <button type="button" class="icon-btn danger" title="Eliminar" wire:click.stop="confirmDelete({{ $u->id }})">
                                            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M3 6h18M8 6V4a2 2 0 012-2h4a2 2 0 012 2v2m3 0v14a2 2 0 01-2 2H7a2 2 0 01-2-2V6h14z"/></svg>
                                        </button>
                                    @endif
                                </td>
                            </tr>

                            @if ($confirmDeleteId === $u->id)
                                <tr class="confirm-row">
                                    <td colspan="4">
                                        ¿Eliminar a {{ $u->name }}? Esta acción no se puede deshacer.
                                        <button type="button" class="btn-del-confirm" wire:click.stop="delete({{ $u->id }})">Eliminar</button>
                                        <button type="button" class="btn-del-cancel" wire:click.stop="cancelDelete">Cancelar</button>
                                    </td>
                                </tr>
                            @endif
                        @empty
                            <tr class="empty-row"><td colspan="4">No hay usuarios que coincidan con la búsqueda.</td></tr>
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
                <h2>{{ $editingUserId ? 'Editar usuario' : 'Nuevo usuario' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            <form wire:submit.prevent="save">
                <div class="tab-panel active">
                    <div class="field"><label>Nombre</label><input wire:model="name"></div>
                    <div class="field"><label>Email</label><input type="email" wire:model="email"></div>
                    <div class="field">
                        <label>Contraseña @if($editingUserId)<small>(dejar en blanco para no cambiar)</small>@endif</label>
                        <input type="password" wire:model="password" placeholder="{{ $editingUserId ? '••••••••' : 'Mínimo 8 caracteres' }}">
                    </div>
                    <div class="field">
                        <label>Roles</label>
                        <div style="display:flex;flex-direction:column;gap:8px;margin-top:4px;">
                            @foreach (\App\Livewire\RoleManager::ROLE_LABELS as $value => $label)
                                <label style="display:flex;align-items:center;gap:8px;font-weight:400;">
                                    <input type="checkbox" wire:model="roles" value="{{ $value }}" style="width:auto;">
                                    {{ $label }}
                                </label>
                            @endforeach
                        </div>
                        @error('roles') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                        @error('roles.*') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                    </div>
                </div>
            </form>

            <div class="drawer-footer">
                <div class="nav-btns"></div>
                <button type="button" class="btn-save" wire:click="save">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M19 21H5a2 2 0 01-2-2V5a2 2 0 012-2h11l5 5v11a2 2 0 01-2 2z"/><path d="M17 21v-8H7v8M7 3v5h8"/></svg>
                    {{ $editingUserId ? 'Guardar cambios' : 'Crear usuario' }}
                </button>
            </div>
        </div>
    </div>
</div>
