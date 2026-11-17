@props(['active'])

@php
$classes = ($active ?? false)
            ? 'inline-flex items-center border-b-2 border-white px-1 pt-1 text-sm font-semibold leading-5 text-white transition duration-150 ease-in-out focus:outline-none focus:border-white'
            : 'inline-flex items-center border-b-2 border-transparent px-1 pt-1 text-sm font-medium leading-5 text-background transition duration-150 ease-in-out hover:border-accent hover:text-white focus:border-accent focus:text-white focus:outline-none';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
