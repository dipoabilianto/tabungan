<x-guest-layout>
    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <label for="email" class="block text-sm font-medium text-white/90">Email</label>
            <input id="email" type="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="username"
                   class="mt-1 block w-full rounded-lg border-white/20 bg-white/10 text-white placeholder-white/40 focus:border-gold focus:ring-gold">
            <x-input-error :messages="$errors->get('email')" class="mt-2 text-gold" />
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-sm font-medium text-white/90">Kata Sandi</label>
            <input id="password" type="password" name="password" required autocomplete="current-password"
                   class="mt-1 block w-full rounded-lg border-white/20 bg-white/10 text-white placeholder-white/40 focus:border-gold focus:ring-gold">
            <x-input-error :messages="$errors->get('password')" class="mt-2 text-gold" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-white/30 bg-white/10 text-gold shadow-sm focus:ring-gold">
                <span class="ms-2 text-sm text-white/80">Ingat saya</span>
            </label>
        </div>

        <!-- reCAPTCHA v3 -->
        @if (config('services.recaptcha.site_key'))
            <input type="hidden" name="g-recaptcha-response" id="g-recaptcha-response">
            <x-input-error :messages="$errors->get('g-recaptcha-response')" class="text-gold" />
        @endif

        <button type="submit"
                class="w-full rounded-lg bg-gold px-4 py-2.5 text-sm font-semibold text-primary-dark shadow-lg transition-colors hover:bg-yellow-500">
            Masuk ke Dashboard
        </button>
    </form>

    @if (config('services.recaptcha.site_key'))
        <script src="https://www.google.com/recaptcha/api.js?render={{ config('services.recaptcha.site_key') }}"></script>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var form = document.querySelector('form');
                if (!form) return;

                form.addEventListener('submit', function (event) {
                    event.preventDefault();
                    grecaptcha.ready(function () {
                        grecaptcha.execute('{{ config('services.recaptcha.site_key') }}', { action: 'login' }).then(function (token) {
                            document.getElementById('g-recaptcha-response').value = token;
                            form.submit();
                        });
                    });
                });
            });
        </script>
    @endif
</x-guest-layout>
