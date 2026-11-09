<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex min-h-[48px] items-center justify-center rounded-xl border border-transparent bg-brand-700 px-5 py-2.5 text-sm font-bold text-white transition-colors hover:bg-brand-800 active:bg-brand-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50']) }}>
    {{ $slot }}
</button>
