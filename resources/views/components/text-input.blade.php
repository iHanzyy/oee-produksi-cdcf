@props(['disabled' => false])

<input @disabled($disabled) {{ $attributes->merge(['class' => 'h-12 rounded-xl border-slate-300 bg-white text-sm text-slate-950 shadow-sm transition placeholder:text-slate-400 hover:border-slate-400 focus:border-brand-700 focus:ring-brand-700 disabled:cursor-not-allowed disabled:bg-slate-100']) }}>
