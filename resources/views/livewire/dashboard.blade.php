<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Judul --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-ink">Buku Tabungan Koperasi</h1>
                <p class="text-muted text-sm mt-1">Minggu ke-{{ $mingguSeharusnya }} sejak koperasi berjalan</p>
            </div>
        </div>

        {{-- Flash message --}}
        @if (session('sukses'))
            <div class="rounded-lg border border-primary/30 bg-primary/10 text-primary-dark px-4 py-3 text-sm font-medium">
                {{ session('sukses') }}
            </div>
        @endif

        {{-- Ringkasan kartu (gaya laporan) --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Total Nasabah</p>
                <p class="mt-2 font-mono tabular text-3xl font-semibold text-ink">{{ $ringkasan['total_nasabah'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Saldo Terkumpul</p>
                <p class="mt-2 font-mono tabular text-xl sm:text-2xl font-semibold text-primary">Rp {{ number_format($ringkasan['total_saldo'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Telat &gt; 3 Minggu</p>
                <p class="mt-2 font-mono tabular text-2xl font-semibold text-coral">{{ $ringkasan['total_telat'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Total Denda</p>
                <p class="mt-2 font-mono tabular text-xl sm:text-2xl font-semibold text-gold">Rp {{ number_format($ringkasan['total_denda'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Setoran Hari Ini</p>
                <p class="mt-2 font-mono tabular text-xl sm:text-2xl font-semibold text-primary">Rp {{ number_format($ringkasan['setoran_hari_ini'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tab --}}
        <div>
            <div class="border-b border-border flex gap-1 overflow-x-auto">
                @foreach (['nasabah' => 'Nasabah', 'setor' => 'Setor Cepat', 'riwayat' => 'Riwayat'] as $key => $label)
                    <button wire:click="$set('tab', '{{ $key }}')"
                            class="px-4 py-2.5 text-sm font-medium rounded-t-lg whitespace-nowrap transition-colors {{ $tab === $key ? 'bg-white text-primary-dark border border-border border-b-white -mb-px' : 'text-muted hover:text-ink' }}">
                        {{ $label }}
                    </button>
                @endforeach
            </div>

            <div class="mt-4">
                @if ($tab === 'nasabah')
                    <livewire:nasabah-table />
                @elseif ($tab === 'setor')
                    <livewire:setor-cepat />
                @else
                    <livewire:riwayat-table />
                @endif
            </div>
        </div>
    </div>
</div>
