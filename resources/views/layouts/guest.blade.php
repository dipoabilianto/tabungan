<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Koperasi Digital Pro') }}</title>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Plus+Jakarta+Sans:wght@400;500;600;700&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-ink antialiased">
        {{-- Animated mesh-gradient background --}}
        <div class="relative min-h-screen overflow-hidden bg-primary-dark">
            <div class="pointer-events-none absolute inset-0">
                <div class="animate-blob-1 absolute -top-32 -left-32 h-[34rem] w-[34rem] rounded-full bg-[#4FA98C] opacity-50 blur-3xl"></div>
                <div class="animate-blob-2 absolute top-1/4 -right-40 h-[30rem] w-[30rem] rounded-full bg-[#C9A227] opacity-40 blur-3xl"></div>
                <div class="animate-blob-3 absolute -bottom-40 left-1/4 h-[32rem] w-[32rem] rounded-full bg-[#F2D9A8] opacity-30 blur-3xl"></div>
                <div class="animate-blob-1 absolute bottom-10 right-1/4 h-[24rem] w-[24rem] rounded-full bg-[#A9D8B8] opacity-30 blur-3xl"></div>
                <div class="animate-blob-2 absolute top-10 left-1/3 h-[22rem] w-[22rem] rounded-full bg-[#1F4A38] opacity-60 blur-3xl"></div>
            </div>

            <div class="relative flex min-h-screen flex-col items-center justify-center p-6">
                <div class="w-full sm:max-w-md">
                    {{-- Glass card --}}
                    <div class="rounded-2xl border border-white/30 bg-white/10 p-8 shadow-2xl backdrop-blur-xl">
                        <div class="mb-6 flex flex-col items-center text-center">
                            {{-- Logo mark: koin emas --}}
                            <span class="inline-flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-gold via-yellow-300 to-gold shadow-lg ring-4 ring-gold/30">
                                <span class="font-display text-3xl font-bold text-primary-dark">K</span>
                            </span>
                            <h1 class="mt-4 font-display text-2xl font-semibold text-white">Koperasi Digital Pro</h1>
                            <p class="mt-1 text-sm text-white/70">Buku tabungan mingguan anggota</p>
                        </div>

                        {{ $slot }}
                    </div>

                    <p class="mt-6 text-center text-xs text-white/50">
                        &copy; {{ date('Y') }} Koperasi Digital Pro
                    </p>
                </div>
            </div>
        </div>
    </body>
</html>
