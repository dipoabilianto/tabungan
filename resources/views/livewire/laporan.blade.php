<div class="py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">

        {{-- Judul --}}
        <div class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="font-display text-3xl font-semibold text-ink">Laporan Setoran</h1>
                <p class="text-muted text-sm mt-1">Periode {{ $dari->format('d/m/Y H:i') }} &mdash; sekarang</p>
            </div>
        </div>

        {{-- Filter periode --}}
        <div class="flex flex-wrap items-center gap-2">
            @foreach (['hari' => 'Hari Ini', 'minggu' => '1 Minggu', 'bulan' => '1 Bulan'] as $key => $label)
                <button wire:click="$set('periode', '{{ $key }}')"
                        class="rounded-lg px-4 py-2 text-sm font-semibold transition-colors {{ $periode === $key ? 'bg-primary text-white shadow-sm' : 'bg-white border border-border text-muted hover:text-ink hover:border-muted' }}">
                    {{ $label }}
                </button>
            @endforeach
        </div>

        {{-- Kartu ringkasan --}}
        <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Total Setoran</p>
                <p class="mt-2 font-mono tabular text-2xl sm:text-3xl font-semibold text-primary">Rp {{ number_format($ringkasan['total_setoran'], 0, ',', '.') }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Jumlah Transaksi</p>
                <p class="mt-2 font-mono tabular text-3xl font-semibold text-ink">{{ $ringkasan['jumlah_transaksi'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Nasabah Setor</p>
                <p class="mt-2 font-mono tabular text-3xl font-semibold text-ink">{{ $ringkasan['jumlah_nasabah'] }}</p>
            </div>
            <div class="bg-white rounded-xl border border-border p-5 shadow-sm">
                <p class="text-xs font-medium uppercase tracking-wide text-muted">Denda Terbayar</p>
                <p class="mt-2 font-mono tabular text-2xl sm:text-3xl font-semibold text-gold">Rp {{ number_format($ringkasan['total_denda'], 0, ',', '.') }}</p>
            </div>
        </div>

        {{-- Tabel transaksi --}}
        <div class="bg-white rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="paper-holes h-3 border-b border-border"></div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-left text-xs uppercase tracking-wide text-muted border-b border-border">
                            <th class="px-4 py-3 font-medium">Waktu</th>
                            <th class="px-4 py-3 font-medium">Nama</th>
                            <th class="px-4 py-3 font-medium text-center">Jenis</th>
                            <th class="px-4 py-3 font-medium text-right">Jumlah</th>
                            <th class="px-4 py-3 font-medium hidden md:table-cell">Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transaksi as $log)
                            <tr class="ledger-row hover:bg-paper/60" wire:key="laporan-{{ $log->id }}">
                                <td class="px-4 py-3 font-mono tabular text-xs text-muted whitespace-nowrap">
                                    {{ $log->created_at->format('d/m/Y H:i') }}
                                </td>
                                <td class="px-4 py-3 font-medium text-ink">{{ $log->nasabah_nama }}</td>
                                <td class="px-4 py-3 text-center">
                                    @if ($log->jenis === 'bayar_denda')
                                        <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold bg-gold/20 text-yellow-800">Bayar Denda</span>
                                    @else
                                        <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold bg-primary/15 text-primary-dark">Setor</span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right font-mono tabular font-semibold text-primary-dark">
                                    Rp {{ number_format($log->jumlah, 0, ',', '.') }}
                                </td>
                                <td class="px-4 py-3 text-muted hidden md:table-cell">{{ $log->keterangan ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-4 py-10 text-center text-muted">
                                    Tidak ada transaksi pada periode ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="p-4 border-t border-border">
                {{ $transaksi->links() }}
            </div>
        </div>
    </div>
</div>
