@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium bg-[#129E76] text-white shadow-sm'
            : 'flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    @isset($icon)
        <span class="shrink-0 w-[18px] h-[18px] flex items-center justify-center">{{ $icon }}</span>
    @endisset
    {{ $slot }}
</a>
