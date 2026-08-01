<div>
    <div class="bg-white rounded-xl border border-border shadow-sm overflow-hidden">
        <div class="paper-holes h-3 border-b border-border"></div>

        {{-- Toolbar --}}
        <div class="p-4 flex flex-wrap items-center justify-between gap-3 border-b border-border">
            <div class="relative w-full sm:w-72">
                <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari nama nasabah..."
                       class="w-full rounded-lg border-border text-sm focus:border-primary focus:ring-primary ps-9">
                <svg class="w-4 h-4 absolute start-3 top-1/2 -translate-y-1/2 text-muted" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-4.35-4.35M17 11a6 6 0 11-12 0 6 6 0 0112 0z"/>
                </svg>
            </div>
            <div class="flex items-center gap-3">
                <span class="text-sm text-muted">
                    Keranjang:
                    <span class="font-mono tabular font-semibold text-ink">Rp {{ number_format($totalKeranjang, 0, ',', '.') }}</span>
                </span>
                <button wire:click="proses"
                        class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                    Proses ({{ count($keranjang) }})
                </button>
            </div>
        </div>

        @error('keranjang')
            <div class="mx-4 mt-3 rounded-lg border border-coral/40 bg-coral/10 text-coral px-4 py-2 text-sm">{{ $message }}</div>
        @enderror

        {{-- Daftar nasabah --}}
        <div class="divide-y-0">
            @forelse ($daftarNasabah as $item)
                @php
                    $id = $item['model']->id;
                    $terkunci = $item['telat'] > 3;
                @endphp
                <div class="ledger-row px-4 py-3 flex items-center gap-3 hover:bg-paper/60 {{ $terkunci ? 'bg-coral/5' : '' }}" wire:key="setor-{{ $id }}">
                    <div class="flex-1 min-w-0">
                        <span class="block font-medium text-ink truncate">{{ $item['model']->nama }}</span>
                        <span class="block text-xs text-muted font-mono tabular">
                            Rp {{ number_format($item['model']->setoran_mingguan, 0, ',', '.') }}/minggu
                            &middot; sudah setor {{ $item['model']->frekuensi_setor }}x
                            @if ($item['telat'] > 0)
                                &middot; <span class="{{ $item['status'] === 'telat' ? 'text-coral' : 'text-yellow-700' }}">telat {{ $item['telat'] }} minggu</span>
                            @endif
                            @if ($terkunci)
                                <span class="inline-block mt-0.5 rounded-full bg-coral/15 px-2 py-0.5 text-[10px] font-semibold text-coral">TERBLOKIR</span>
                            @endif
                        </span>
                    </div>
                    <div class="flex items-center gap-2">
                        <input id="minggu-{{ $id }}" type="number" min="1" step="1"
                               wire:model.live.debounce.300ms="minggu.{{ $id }}"
                               wire:keydown.enter="setorSatu({{ $id }})"
                               placeholder="1"
                               {{ $terkunci ? 'disabled' : '' }}
                               class="w-16 rounded-lg border-border text-sm font-mono tabular text-right focus:border-primary focus:ring-primary {{ $terkunci ? 'opacity-50' : '' }}">
                        <button wire:click="tambah({{ $id }})"
                                title="{{ $terkunci ? 'Telat lebih dari 3 minggu — gunakan Koreksi di tab Nasabah' : 'Tambahkan ke keranjang' }}"
                                class="rounded-lg border border-border px-2.5 py-2 text-sm font-semibold transition-colors {{ $terkunci ? 'opacity-40 cursor-not-allowed text-muted' : (isset($keranjang[$id]) ? 'bg-primary/10 text-primary-dark border-primary/40' : 'text-muted hover:text-ink hover:border-muted') }}"
                                {{ $terkunci || isset($keranjang[$id]) ? 'disabled' : '' }}>
                            {{ isset($keranjang[$id]) ? '✓' : '+' }}
                        </button>
                        <button wire:click="setorSatu({{ $id }})"
                                title="{{ $terkunci ? 'Telat lebih dari 3 minggu — gunakan Koreksi di tab Nasabah' : 'Setor langsung (atau tekan Enter)' }}"
                                class="rounded-lg px-2.5 py-2 text-sm font-semibold transition-colors {{ $terkunci ? 'bg-muted/20 text-muted cursor-not-allowed' : 'bg-primary text-white hover:bg-primary-dark' }}"
                                {{ $terkunci ? 'disabled' : '' }}>
                            Setor
                        </button>
                        @error('minggu.' . $id)
                            <p class="text-xs text-coral">{{ $message }}</p>
                        @enderror
                        @error('telat.' . $id)
                            <p class="text-xs text-coral">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            @empty
                <p class="px-4 py-10 text-center text-muted">
                    {{ $search ? 'Tidak ada nasabah yang cocok dengan pencarian "' . $search . '".' : 'Belum ada nasabah terdaftar.' }}
                </p>
            @endforelse
        </div>
    </div>

    {{-- Panel keranjang --}}
    @if ($keranjangItems->isNotEmpty())
        <div class="mt-4 bg-white rounded-xl border border-border shadow-sm overflow-hidden" wire:key="keranjang-panel">
            <div class="paper-holes h-3 border-b border-border"></div>
            <div class="p-4 border-b border-border flex items-center justify-between">
                <h2 class="font-display font-semibold text-lg text-ink">Keranjang Setoran</h2>
                <button wire:click="$set('keranjang', [])" class="text-xs font-medium text-muted hover:text-coral">Kosongkan</button>
            </div>
            <div class="divide-y divide-border">
                @foreach ($keranjangItems as $item)
                    @php $cid = $item['model']->id; @endphp
                    <div class="px-4 py-3 flex items-center gap-3" wire:key="cart-{{ $cid }}">
                        <div class="flex-1 min-w-0">
                        <span class="block font-medium truncate {{ $terkunci ? 'text-coral' : 'text-ink' }}">{{ $item['model']->nama }}</span>
                            <span class="block text-xs text-muted font-mono tabular">Rp {{ number_format($item['model']->setoran_mingguan, 0, ',', '.') }}/minggu</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <label for="cart-minggu-{{ $cid }}" class="text-xs text-muted hidden sm:block">Minggu:</label>
                            <input id="cart-minggu-{{ $cid }}" type="number" min="1" step="1"
                                   wire:model.live.debounce.300ms="keranjang.{{ $cid }}"
                                   class="w-16 rounded-lg border-border text-sm font-mono tabular text-right focus:border-primary focus:ring-primary">
                            <span class="font-mono tabular text-sm font-semibold text-primary-dark w-24 text-right">
                                Rp {{ number_format($item['model']->setoran_mingguan * (int) $item['minggu'], 0, ',', '.') }}
                            </span>
                            <button wire:click="hapus({{ $cid }})" title="Hapus dari keranjang"
                                    class="rounded-lg border border-border px-2 py-1.5 text-sm text-muted hover:text-coral hover:border-coral/40 transition-colors">
                                ✕
                            </button>
                        </div>
                    </div>
                @endforeach
            </div>
            <div class="p-4 border-t border-border flex items-center justify-between bg-paper/50">
                <span class="text-sm font-medium text-muted">Total</span>
                <span class="font-mono tabular text-xl font-semibold text-primary">Rp {{ number_format($totalKeranjang, 0, ',', '.') }}</span>
            </div>
        </div>
    @endif

    <p class="mt-3 text-xs text-muted">
        Klik "Setor" atau tekan Enter pada kolom minggu untuk memproses satu nasabah langsung.
        Klik "+" untuk memasukkan ke keranjang, atur jumlah minggu, lalu "Proses" untuk setoran massal dalam satu transaksi.
    </p>
</div>
