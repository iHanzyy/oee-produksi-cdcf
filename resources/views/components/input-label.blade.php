@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-semibold text-slate-800']) }}>
    {{ $value ?? $slot }}
</label>
