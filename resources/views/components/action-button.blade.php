@props([
    'href' => null,
    'type' => 'button',
    'variant' => 'primary',
])

@php
    $classes = 'inline-flex items-center justify-center gap-2 rounded-xl font-bold shadow-sm transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60 ' . match ($variant) {
        'secondary' => 'bg-secondary text-white hover:bg-primary focus-visible:ring-secondary',
        'danger' => 'bg-red-600 text-white hover:bg-red-700 focus-visible:ring-red-600',
        default => 'bg-primary text-white hover:bg-secondary focus-visible:ring-primary',
    };
@endphp

@if($href)
    <a href="{{ $href }}" {{ $attributes->class($classes) }}>{{ $slot }}</a>
@else
    <button type="{{ $type }}" {{ $attributes->class($classes) }}>{{ $slot }}</button>
@endif
