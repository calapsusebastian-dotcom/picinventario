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
                    <h1>{{ $stageLabel }} · Bodega PIC</h1>
                    <p class="subtitle">{{ $stageRoleLabel }} — bandeja de registros pendientes de este paso</p>
                </div>
            </div>
            @if ($stage === 'general')
                <button type="button" class="btn-primary" wire:click="openCreate">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 5v14M5 12h14"/></svg>
                    Nuevo registro
                </button>
            @endif
        </div>

        <div class="kpis">
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-amber-soft);color:var(--pic-amber)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Pendientes de {{ $stageLabel }}</div>
                    <div class="kpi-value">{{ $pendingCount }}</div>
                </div>
            </div>
            <div class="kpi-card">
                <div class="kpi-icon" style="background:var(--pic-accent-soft);color:var(--pic-accent-deep)">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 6L9 17l-5-5"/></svg>
                </div>
                <div>
                    <div class="kpi-label">Completados</div>
                    <div class="kpi-value">{{ $completedCount }}</div>
                </div>
            </div>
        </div>

        <div class="toolbar">
            <div class="search-box">
                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Buscar por remisión, cliente, destino...">
            </div>
        </div>

        <div class="section-label">Bandeja de {{ $stageLabel }}</div>

        <div class="table-card">
            <div class="table-scroll">
                <table>
                    <thead>
                        <tr>
                            <th>Fecha</th><th>Remisión</th><th>Calidad</th><th>Cliente</th><th>Estatus</th><th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($records as $r)
                            @php $col = $statusColor($r->estatus); @endphp
                            <tr class="data-row" wire:click="openEdit({{ $r->id }})">
                                <td class="mono">{{ $r->fecha?->format('Y-m-d') ?? '—' }}</td>
                                <td class="mono">{{ $r->remision ?: '—' }}</td>
                                <td>{{ $r->calidad_enviada ?: '—' }}</td>
                                <td>{{ $r->cliente ?: '—' }}</td>
                                <td><span class="badge" style="background:{{ $col['bg'] }};color:{{ $col['fg'] }}">{{ $r->estatus }}</span></td>
                                <td>
                                    <button type="button" class="icon-btn" title="Completar {{ $stageLabel }}" wire:click.stop="openEdit({{ $r->id }})">
                                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M11 4H4a2 2 0 00-2 2v14a2 2 0 002 2h14a2 2 0 002-2v-7"/><path d="M18.5 2.5a2.12 2.12 0 013 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr class="empty-row"><td colspan="6">No hay registros pendientes de {{ $stageLabel }}.</td></tr>
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
                <h2>{{ $editingId ? 'Completar '.$stageLabel : 'Nuevo registro' }}</h2>
                <button type="button" wire:click="closeDrawer">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M18 6L6 18M6 6l12 12"/></svg>
                </button>
            </div>

            @if ($editingId && $stage !== 'general')
                <div style="padding:16px 24px 0;">
                    <div class="detail-grid">
                        <div class="detail-block">
                            <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-ink-faint)"></span><span class="detail-title">General</span></div>
                            <div class="detail-item"><span>Remisión</span><span>{{ $form->remision ?: '—' }}</span></div>
                            <div class="detail-item"><span>Fecha</span><span>{{ $form->fecha ?: '—' }}</span></div>
                            <div class="detail-item"><span>Año</span><span>{{ $form->anio ?: '—' }}</span></div>
                            <div class="detail-item"><span>Mes</span><span>{{ $form->mes ?: '—' }}</span></div>
                            <div class="detail-item"><span>N° tulas</span><span>{{ $form->tulas ?: '—' }}</span></div>
                            <div class="detail-item"><span>N° costal</span><span>{{ $form->costal ?: '—' }}</span></div>
                            <div class="detail-item"><span>Ubicación</span><span>{{ $form->ubicacion ?: '—' }}</span></div>
                        </div>

                        @if (in_array($stage, ['recepcion', 'destino', 'imov']))
                            <div class="detail-block">
                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-purple)"></span><span class="detail-title">Envío · {{ $form->analisis_enviado_por ?: '—' }}</span></div>
                                <div class="detail-item"><span>Calidad enviada</span><span>{{ $form->calidad_enviada ?: '—' }}</span></div>
                                <div class="detail-item"><span>Kg enviados</span><span>{{ $form->kg_enviados ? $form->kg_enviados.' kg' : '—' }}</span></div>
                                <div class="detail-item"><span>Almendra sana</span><span>{{ $form->as_env ? $form->as_env.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Pasilla</span><span>{{ $form->pas_env ? $form->pas_env.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Primer grupo</span><span>{{ $form->pg_env ? $form->pg_env.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Broca</span><span>{{ $form->broca_env ? $form->broca_env.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Humedad</span><span>{{ $form->humedad_env ? $form->humedad_env.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Factor</span><span>{{ $form->factor_env ?: '—' }}</span></div>
                                <div class="detail-item"><span>Taza</span><span>{{ $form->taza_env ?: '—' }}</span></div>
                                <div class="detail-item"><span>Puntaje taza</span><span>{{ $form->puntaje_taza_env ?: '—' }}</span></div>
                            </div>
                        @endif

                        @if (in_array($stage, ['destino', 'imov']))
                            <div class="detail-block">
                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-amber)"></span><span class="detail-title">Recepción · {{ $form->analisis_recibido_por ?: '—' }}</span></div>
                                <div class="detail-item"><span>Kg recibidos</span><span>{{ $form->kg_recibidos ? $form->kg_recibidos.' kg' : '—' }}</span></div>
                                <div class="detail-item"><span>Almendra sana</span><span>{{ $form->as_rec ? $form->as_rec.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Pasilla</span><span>{{ $form->pas_rec ? $form->pas_rec.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Primer grupo</span><span>{{ $form->pg_rec ? $form->pg_rec.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Broca</span><span>{{ $form->broca_rec ? $form->broca_rec.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Humedad</span><span>{{ $form->humedad_rec ? $form->humedad_rec.'%' : '—' }}</span></div>
                                <div class="detail-item"><span>Factor</span><span>{{ $form->factor_rec ?: '—' }}</span></div>
                                <div class="detail-item"><span>Taza</span><span>{{ $form->taza_rec ?: '—' }}</span></div>
                                <div class="detail-item"><span>Puntaje taza</span><span>{{ $form->puntaje_taza_rec ?: '—' }}</span></div>
                            </div>
                        @endif

                        @if ($stage === 'imov')
                            <div class="detail-block">
                                <div class="detail-block-head"><span class="role-dot" style="background:var(--pic-accent)"></span><span class="detail-title">Destino</span></div>
                                <div class="detail-item"><span>Destino</span><span>{{ $form->destino ?: '—' }}</span></div>
                                <div class="detail-item"><span>Cliente</span><span>{{ $form->cliente ?: '—' }}</span></div>
                                <div class="detail-item"><span>Negocio</span><span>{{ $form->negocio ?: '—' }}</span></div>
                                <div class="detail-item"><span>Estatus</span><span>{{ $form->estatus ?: '—' }}</span></div>
                                <div class="detail-item"><span>Existencia</span><span>{{ $form->existencia ? $form->existencia.' kg' : '—' }}</span></div>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <form wire:submit.prevent="save">
                @if ($stage === 'general')
                    <div class="tab-panel active">
                        <div class="field"><label>Año</label><input wire:model="form.anio">@error('form.anio') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Mes</label>
                            <select wire:model="form.mes">
                                <option value="">Selecciona...</option>
                                <option>Enero</option><option>Febrero</option><option>Marzo</option><option>Abril</option><option>Mayo</option><option>Junio</option>
                                <option>Julio</option><option>Agosto</option><option>Septiembre</option><option>Octubre</option><option>Noviembre</option><option>Diciembre</option>
                            </select>
                            @error('form.mes') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                        </div>
                        <div class="field"><label>Fecha</label><input type="date" wire:model="form.fecha">@error('form.fecha') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Remisión</label><input wire:model="form.remision" placeholder="R-0000">@error('form.remision') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>N° tulas</label><input type="number" wire:model="form.tulas">@error('form.tulas') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>N° costal</label><input type="number" wire:model="form.costal">@error('form.costal') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field">
                            <label>Ubicación</label>
                            <select wire:model="form.ubicacion">
                                <option value="">Selecciona...</option>
                                @foreach ($ubicaciones as $u)
                                    <option value="{{ $u }}">{{ $u }}</option>
                                @endforeach
                            </select>
                            @error('form.ubicacion') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                        </div>
                    </div>
                @endif

                @if ($stage === 'envio')
                    <div class="tab-panel active">
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
                        <div class="field"><label>Kg enviados</label><input type="number" wire:model="form.kg_enviados">@error('form.kg_enviados') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
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
                @endif

                @if ($stage === 'recepcion')
                    <div class="tab-panel active">
                        <div class="field"><label>Análisis recibido por</label>
                            <select wire:model="form.analisis_recibido_por"><option value="">Selecciona...</option><option>Bodega</option><option>Calidades</option><option>Trilladora</option></select>
                            @error('form.analisis_recibido_por') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror
                        </div>
                        <div class="field"><label>Kg recibidos</label><input type="number" wire:model="form.kg_recibidos">@error('form.kg_recibidos') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Almendra sana <small>(%)</small></label><input type="number" wire:model="form.as_rec">@error('form.as_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Pasilla <small>(%)</small></label><input type="number" wire:model="form.pas_rec">@error('form.pas_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Primer grupo <small>(%)</small></label><input type="number" wire:model="form.pg_rec">@error('form.pg_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Broca <small>(%)</small></label><input type="number" wire:model="form.broca_rec">@error('form.broca_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Humedad <small>(%)</small></label><input type="number" wire:model="form.humedad_rec">@error('form.humedad_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Factor</label><input type="number" wire:model="form.factor_rec">@error('form.factor_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Taza</label><input wire:model="form.taza_rec" placeholder="Notas de catación">@error('form.taza_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field"><label>Puntaje de taza</label><input type="number" wire:model="form.puntaje_taza_rec">@error('form.puntaje_taza_rec') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    </div>
                @endif

                @if ($stage === 'destino')
                    <div class="tab-panel active">
                        <div class="field"><label>Destino</label><input wire:model="form.destino">@error('form.destino') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                        <div class="field">
                            <label>Cliente</label>
                            <select wire:model="form.cliente">
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
                @endif

                @if ($stage === 'imov')
                    <div class="tab-panel active">
                        <div class="field"><label>Imov</label><input type="number" wire:model="form.imov">@error('form.imov') <small style="color:var(--pic-danger);">{{ $message }}</small> @enderror</div>
                    </div>
                @endif
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
