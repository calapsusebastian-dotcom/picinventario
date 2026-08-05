<?php

use App\Livewire\Actions\Logout;
use App\Support\InventoryStages;
use Livewire\Volt\Component;

new class extends Component
{
    /**
     * Log the current user out of the application.
     */
    public function logout(Logout $logout): void
    {
        $logout();

        $this->redirect('/', navigate: true);
    }

    /**
     * Stage keys this user may navigate to: all of them for admins,
     * only their own for everyone else.
     */
    public function stageLinks(): array
    {
        $user = auth()->user();

        $stages = $user->isAdmin()
            ? InventoryStages::ORDER
            : array_intersect(InventoryStages::ORDER, $user->roles ?? []);

        return collect($stages)
            ->map(fn (string $stage) => ['stage' => $stage, 'label' => InventoryStages::label($stage)])
            ->all();
    }

    /**
     * Trilla and Despacho are separate modules from the general/envío/
     * recepción/destino/imov workflow, so they aren't part of stageLinks().
     */
    public function canAccessModule(string $role): bool
    {
        $user = auth()->user();

        return $user->isAdmin() || $user->hasRole($role);
    }
}; ?>

<div class="contents" x-data="{ open: false }">
    {{-- Desktop sidebar --}}
    <aside class="hidden sm:flex sm:flex-col sm:w-64 sm:shrink-0 bg-white dark:bg-gray-800 border-r border-gray-100 dark:border-gray-700 min-h-screen">
        <div class="flex items-center gap-3 h-16 px-5 border-b border-gray-100 dark:border-gray-700">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-3">
                <x-application-logo class="h-9 w-9" />
                <span class="font-semibold text-gray-800 dark:text-gray-100">Bodega PIC</span>
            </a>
        </div>

        <nav class="flex-1 overflow-y-auto py-4 px-3 space-y-1">
            @if (auth()->user()->isAdmin())
                <x-sidebar-link :href="route('inventario')" :active="request()->routeIs('inventario')" wire:navigate>
                    {{ __('Tablero') }}
                </x-sidebar-link>
                <x-sidebar-link :href="route('informes')" :active="request()->routeIs('informes')" wire:navigate>
                    {{ __('Informes') }}
                </x-sidebar-link>
            @endif
            @foreach ($this->stageLinks() as $link)
                <x-sidebar-link :href="route('inventario.stage', $link['stage'])" :active="request()->routeIs('inventario.stage') && request()->route('stage') === $link['stage']" wire:navigate>
                    {{ $link['label'] }}
                </x-sidebar-link>
            @endforeach
            @if ($this->canAccessModule('trilla'))
                <x-sidebar-link :href="route('trilla')" :active="request()->routeIs('trilla')" wire:navigate>
                    {{ __('Trilla') }}
                </x-sidebar-link>
            @endif
            @if ($this->canAccessModule('despacho'))
                <x-sidebar-link :href="route('despacho')" :active="request()->routeIs('despacho')" wire:navigate>
                    {{ __('Despacho') }}
                </x-sidebar-link>
            @endif

            @if (auth()->user()->isAdmin())
                <div class="pt-3 mt-3 border-t border-gray-100 dark:border-gray-700 space-y-1">
                    <x-sidebar-link :href="route('usuarios')" :active="request()->routeIs('usuarios')" wire:navigate>
                        {{ __('Roles') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('productos')" :active="request()->routeIs('productos')" wire:navigate>
                        {{ __('Productos') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('clientes')" :active="request()->routeIs('clientes')" wire:navigate>
                        {{ __('Clientes') }}
                    </x-sidebar-link>
                </div>
            @endif
        </nav>

        <div class="border-t border-gray-100 dark:border-gray-700 p-3">
            <x-dropdown align="left" width="56">
                <x-slot name="trigger">
                    <button class="w-full flex items-center gap-2 px-3 py-2 rounded-lg text-sm text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 focus:outline-none transition ease-in-out duration-150">
                        <div class="flex-1 text-start truncate" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                        <svg class="fill-current h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>

                <x-slot name="content">
                    <x-dropdown-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-dropdown-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-dropdown-link>
                            {{ __('Log Out') }}
                        </x-dropdown-link>
                    </button>
                </x-slot>
            </x-dropdown>
        </div>
    </aside>

    {{-- Mobile top bar + off-canvas menu --}}
    <div class="sm:hidden bg-white dark:bg-gray-800 border-b border-gray-100 dark:border-gray-700">
        <div class="flex items-center justify-between h-16 px-4">
            <a href="{{ route('dashboard') }}" wire:navigate class="flex items-center gap-2">
                <x-application-logo class="h-8 w-8" />
                <span class="font-semibold text-gray-800 dark:text-gray-100">Bodega PIC</span>
            </a>

            <button @click="open = ! open" aria-label="Abrir menú" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 dark:text-gray-500 hover:text-gray-500 dark:hover:text-gray-400 hover:bg-gray-100 dark:hover:bg-gray-900 focus:outline-none focus:bg-gray-100 dark:focus:bg-gray-900 focus:text-gray-500 dark:focus:text-gray-400 transition duration-150 ease-in-out">
                <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-gray-100 dark:border-gray-700">
            <div class="py-2 px-3 space-y-1">
                @if (auth()->user()->isAdmin())
                    <x-sidebar-link :href="route('inventario')" :active="request()->routeIs('inventario')" wire:navigate>
                        {{ __('Tablero') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('informes')" :active="request()->routeIs('informes')" wire:navigate>
                        {{ __('Informes') }}
                    </x-sidebar-link>
                @endif
                @foreach ($this->stageLinks() as $link)
                    <x-sidebar-link :href="route('inventario.stage', $link['stage'])" :active="request()->routeIs('inventario.stage') && request()->route('stage') === $link['stage']" wire:navigate>
                        {{ $link['label'] }}
                    </x-sidebar-link>
                @endforeach
                @if ($this->canAccessModule('trilla'))
                    <x-sidebar-link :href="route('trilla')" :active="request()->routeIs('trilla')" wire:navigate>
                        {{ __('Trilla') }}
                    </x-sidebar-link>
                @endif
                @if ($this->canAccessModule('despacho'))
                    <x-sidebar-link :href="route('despacho')" :active="request()->routeIs('despacho')" wire:navigate>
                        {{ __('Despacho') }}
                    </x-sidebar-link>
                @endif
                @if (auth()->user()->isAdmin())
                    <x-sidebar-link :href="route('usuarios')" :active="request()->routeIs('usuarios')" wire:navigate>
                        {{ __('Roles') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('productos')" :active="request()->routeIs('productos')" wire:navigate>
                        {{ __('Productos') }}
                    </x-sidebar-link>
                    <x-sidebar-link :href="route('clientes')" :active="request()->routeIs('clientes')" wire:navigate>
                        {{ __('Clientes') }}
                    </x-sidebar-link>
                @endif
            </div>

            <div class="pt-3 pb-3 border-t border-gray-100 dark:border-gray-700">
                <div class="px-4">
                    <div class="font-medium text-base text-gray-800 dark:text-gray-200" x-data="{{ json_encode(['name' => auth()->user()->name]) }}" x-text="name" x-on:profile-updated.window="name = $event.detail.name"></div>
                    <div class="font-medium text-sm text-gray-500">{{ auth()->user()->email }}</div>
                </div>

                <div class="mt-3 px-3 space-y-1">
                    <x-sidebar-link :href="route('profile')" wire:navigate>
                        {{ __('Profile') }}
                    </x-sidebar-link>

                    <button wire:click="logout" class="w-full text-start">
                        <x-sidebar-link href="#">
                            {{ __('Log Out') }}
                        </x-sidebar-link>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
