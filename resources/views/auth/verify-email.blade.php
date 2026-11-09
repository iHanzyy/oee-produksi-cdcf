<x-guest-layout>
    <div class="mb-6 text-sm leading-6 text-slate-600">
        {{ __('Verifikasi alamat email Anda melalui tautan yang telah kami kirim. Jika email belum diterima, kirim ulang tautannya.') }}
    </div>

    @if (session('status') == 'verification-link-sent')
        <div class="mb-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-800">
            {{ __('Tautan verifikasi baru telah dikirim ke alamat email Anda.') }}
        </div>
    @endif

    <div class="mt-6 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <form method="POST" action="{{ route('verification.send') }}">
            @csrf

            <div>
                <x-primary-button>
                    {{ __('Kirim Ulang Verifikasi') }}
                </x-primary-button>
            </div>
        </form>

        <form method="POST" action="{{ route('logout') }}">
            @csrf

            <button type="submit" class="min-h-[44px] rounded-xl px-3 text-sm font-semibold text-slate-600 transition-colors hover:bg-slate-100 hover:text-slate-900 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-700 focus-visible:ring-offset-2">
                {{ __('Keluar') }}
            </button>
        </form>
    </div>
</x-guest-layout>
