<x-guest-layout>
    <div class="mb-6 text-sm leading-6 text-slate-600">
        {{ __('Lupa password? Masukkan alamat email akun Anda. Kami akan mengirimkan tautan untuk membuat password baru.') }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div class="mt-6">
            <x-primary-button class="w-full">
                {{ __('Kirim Tautan Reset Password') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>
