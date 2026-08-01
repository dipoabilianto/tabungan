<div>
    <div class="bg-white rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="paper-holes h-3 border-b border-border"></div>

        {{-- Toolbar --}}
        <div class="p-4 border-b border-border">
            <div class="relative w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="filterNama" placeholder="Filter per nama nasabah..."
                       class="w-full rounded-lg border-border text-sm focus:border-primary focus:ring-primary ps-9">
                <svg class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
            </div>
        </div>

        {{-- Tabel riwayat --}}
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
                    @forelse ($riwayat as $log)
                        <tr class="ledger-row hover:bg-paper/60" wire:key="log-{{ $log->id }}">
                            <td class="px-4 py-3 font-mono tabular text-xs text-muted whitespace-nowrap">
                                {{ $log->created_at->format('d/m/Y H:i') }}
                            </td>
                            <td class="px-4 py-3 font-medium text-ink">{{ $log->nasabah_nama }}</td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $jenisBadge = match ($log->jenis) {
                                        'daftar' => 'bg-primary/15 text-primary-dark',
                                        'setor' => 'bg-primary/15 text-primary-dark',
                                        'bayar_denda' => 'bg-gold/20 text-yellow-800',
                                        'edit' => 'bg-muted/15 text-muted',
                                        'koreksi' => 'bg-gold/20 text-yellow-800',
                                        default => 'bg-coral/15 text-coral',
                                    };
                                    $jenisLabel = match ($log->jenis) {
                                        'bayar_denda' => 'Bayar Denda',
                                        'koreksi' => 'Koreksi Minggu',
                                        default => ucfirst($log->jenis),
                                    };
                                @endphp
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $jenisBadge }}">{{ $jenisLabel }}</span>
                            </td>
                            <td class="px-4 py-3 text-right font-mono tabular {{ $log->jumlah > 0 ? 'font-semibold text-primary-dark' : 'text-muted' }}">
                                {{ $log->jumlah > 0 ? 'Rp ' . number_format($log->jumlah, 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-muted hidden md:table-cell">{{ $log->keterangan ?? '—' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-muted">
                                {{ $filterNama ? 'Tidak ada riwayat untuk nama "' . $filterNama . '".' : 'Belum ada transaksi tercatat.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="p-4 border-t border-border">
            {{ $riwayat->links() }}
        </div>
    </div>
</div>
