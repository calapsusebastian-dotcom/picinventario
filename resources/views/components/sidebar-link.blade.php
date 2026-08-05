@props(['active' => false])

@php
$classes = ($active ?? false)
            ? 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium bg-[#0B646C] text-white'
            : 'flex items-center gap-2 px-3 py-2 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 hover:text-gray-900 dark:hover:text-white transition duration-150 ease-in-out';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
