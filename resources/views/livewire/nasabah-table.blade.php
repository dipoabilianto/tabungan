<div>
    @if ($showPasswordModal)
        {{-- ============ Gate Password ============ --}}
        <div class="bg-white rounded-xl border border-border shadow-sm overflow-hidden">
            <div class="paper-holes h-3 border-b border-border"></div>
            <div class="p-8 max-w-md mx-auto">
                <div class="flex flex-col items-center text-center">
                    <span class="inline-flex h-12 w-12 items-center justify-center rounded-full bg-primary/10">
                        <svg class="w-6 h-6 text-primary-dark" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                        </svg>
                    </span>
                    <h3 class="mt-4 font-display text-xl font-semibold text-ink">Data Nasabah Terkunci</h3>
                    <p class="mt-1 text-sm text-muted">Masukkan password login untuk membuka data nasabah.</p>
                </div>

                <form wire:submit="verifikasiPassword" class="mt-6 space-y-4">
                    <div>
                        <label for="nasabah-password" class="block text-sm font-medium text-ink">Password</label>
                        <input id="nasabah-password" type="password" wire:model="password"
                               autocomplete="current-password" autofocus
                               placeholder="Password login Anda"
                               class="mt-1 w-full rounded-lg border-border focus:border-primary focus:ring-primary">
                        @error('password')
                            <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                            class="w-full rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                        Buka
                    </button>
                </form>
            </div>
        </div>
    @else
        {{-- Panel tabel --}}
    <div class="bg-white rounded-xl border border-border shadow-sm overflow-hidden">
        {{-- Strip lubang sobekan kertas --}}
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
            <div class="flex items-center gap-2">
                <button wire:click="openImport"
                        class="inline-flex items-center gap-2 rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink hover:border-muted transition-colors">
                    Import
                </button>
                <button wire:click="create"
                        class="inline-flex items-center gap-2 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                    <span class="text-lg leading-none">+</span> Tambah Nasabah
                </button>
            </div>
        </div>

        {{-- Tabel ledger --}}
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="text-left text-xs uppercase tracking-wide text-muted border-b border-border">
                        <th class="px-4 py-3 font-medium">Nama</th>
                        <th class="px-4 py-3 font-medium text-right hidden md:table-cell">Setoran/Minggu</th>
                        <th class="px-4 py-3 font-medium text-right hidden sm:table-cell">Setor ke-</th>
                        <th class="px-4 py-3 font-medium text-right">Saldo</th>
                        <th class="px-4 py-3 font-medium text-right hidden lg:table-cell">Denda</th>
                        <th class="px-4 py-3 font-medium text-center">Status</th>
                        <th class="px-4 py-3 font-medium text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($daftarNasabah as $item)
                        <tr class="ledger-row hover:bg-paper/60" wire:key="nasabah-{{ $item['model']->id }}">
                            <td class="px-4 py-3 font-medium text-ink">{{ $item['model']->nama }}</td>
                            <td class="px-4 py-3 text-right font-mono tabular text-muted hidden md:table-cell">
                                Rp {{ number_format($item['model']->setoran_mingguan, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono tabular hidden sm:table-cell">{{ $item['model']->frekuensi_setor }}</td>
                            <td class="px-4 py-3 text-right font-mono tabular font-semibold text-primary-dark">
                                Rp {{ number_format($item['model']->saldo, 0, ',', '.') }}
                            </td>
                            <td class="px-4 py-3 text-right font-mono tabular hidden lg:table-cell {{ $item['denda'] > 0 ? 'text-gold font-semibold' : 'text-muted' }}">
                                {{ $item['denda'] > 0 ? 'Rp ' . number_format($item['denda'], 0, ',', '.') : '—' }}
                            </td>
                            <td class="px-4 py-3 text-center">
                                @php
                                    $badge = match ($item['status']) {
                                        'lunas' => 'bg-primary/15 text-primary-dark',
                                        'mendekati' => 'bg-gold/20 text-yellow-800',
                                        default => 'bg-coral/15 text-coral',
                                    };
                                @endphp
                                <span class="inline-block rounded-full px-3 py-1 text-xs font-semibold {{ $badge }}">
                                    {{ ucfirst($item['status']) }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center justify-end gap-1.5 flex-wrap">
                                    <button wire:click="showRiwayat({{ $item['model']->id }})"
                                            class="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted hover:text-ink hover:border-muted transition-colors">
                                        Riwayat
                                    </button>
                                    <button wire:click="edit({{ $item['model']->id }})"
                                            class="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted hover:text-ink hover:border-muted transition-colors">
                                        Edit
                                    </button>
                                    <button wire:click="confirmKoreksi({{ $item['model']->id }})"
                                            class="rounded-md border border-border px-2.5 py-1 text-xs font-medium text-muted hover:text-ink hover:border-muted transition-colors">
                                        Koreksi
                                    </button>
                                    @if ($item['denda'] > 0)
                                        <button wire:click="confirmDenda({{ $item['model']->id }})"
                                                class="rounded-md bg-gold px-2.5 py-1 text-xs font-semibold text-white hover:bg-yellow-700 transition-colors">
                                            Bayar Denda
                                        </button>
                                    @endif
                                    <button wire:click="confirmDelete({{ $item['model']->id }})"
                                            class="rounded-md border border-coral/40 px-2.5 py-1 text-xs font-medium text-coral hover:bg-coral hover:text-white transition-colors">
                                        Hapus
                                    </button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-muted">
                                {{ $search ? 'Tidak ada nasabah yang cocok dengan pencarian "' . $search . '".' : 'Belum ada nasabah. Klik "+ Tambah Nasabah" untuk mendaftarkan.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ============ Modal Tambah/Edit Nasabah ============ --}}
    @if ($showFormModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showFormModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-md overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-ink">
                        {{ $editingId ? 'Edit Nasabah' : 'Tambah Nasabah Baru' }}
                    </h3>

                    <form wire:submit="save" class="mt-5 space-y-4">
                        <div>
                            <label for="nama" class="block text-sm font-medium text-ink">Nama Lengkap</label>
                            <input id="nama" type="text" wire:model="nama" autofocus
                                   class="mt-1 w-full rounded-lg border-border focus:border-primary focus:ring-primary">
                            @error('nama')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="setoran_mingguan" class="block text-sm font-medium text-ink">Setoran per Minggu (Rp)</label>
                            <input id="setoran_mingguan" type="number" min="1" step="1" wire:model="setoran_mingguan"
                                   class="mt-1 w-full rounded-lg border-border font-mono tabular focus:border-primary focus:ring-primary">
                            @error('setoran_mingguan')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                            @if ($editingId)
                                <p class="mt-1 text-xs text-muted">Mengubah setoran akan menghitung ulang saldo (setor ke-{{ '×' }} frekuensi).</p>
                            @endif
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('showFormModal', false)"
                                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                                {{ $editingId ? 'Simpan Perubahan' : 'Daftarkan' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ Modal Konfirmasi Hapus ============ --}}
    @if ($showDeleteModal && $deletingNasabah)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showDeleteModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-md overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-coral">Hapus Nasabah?</h3>
                    <p class="mt-3 text-sm text-ink">
                        Anda akan menghapus nasabah <strong>{{ $deletingNasabah->nama }}</strong>
                        dengan sisa saldo <strong class="font-mono tabular">Rp {{ number_format($deletingNasabah->saldo, 0, ',', '.') }}</strong>.
                    </p>
                    <p class="mt-2 text-sm text-muted">
                        Tindakan ini tidak bisa dibatalkan. Riwayat transaksi nasabah akan tetap tersimpan.
                    </p>

                    <div class="flex justify-end gap-2 pt-5">
                        <button wire:click="$set('showDeleteModal', false)"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                            Batal
                        </button>
                        <button wire:click="delete"
                                class="rounded-lg bg-coral px-4 py-2 text-sm font-semibold text-white hover:bg-red-800 transition-colors">
                            Ya, Hapus
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ Modal Bayar Denda ============ --}}
    @if ($showDendaModal && $dendaNasabah)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showDendaModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-md overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-ink">Bayar Denda</h3>
                    <p class="mt-3 text-sm text-ink">
                        <strong>{{ $dendaNasabah->nama }}</strong> memiliki tunggakan
                        <strong class="font-mono tabular">{{ $dendaTelat }} minggu</strong>.
                    </p>
                    <div class="mt-4 rounded-lg bg-gold/10 border border-gold/40 p-4">
                        <p class="text-xs uppercase tracking-wide text-muted">Denda yang berlaku saat ini</p>
                        <p class="mt-1 font-mono tabular text-2xl font-semibold text-gold">
                            Rp {{ number_format($dendaNominal, 0, ',', '.') }}
                        </p>
                    </div>
                    <p class="mt-3 text-sm text-muted">
                        Setelah dibayar, nasabah dianggap lunas (catch up penuh) dan denda kembali Rp 0.
                    </p>

                    <div class="flex justify-end gap-2 pt-5">
                        <button wire:click="$set('showDendaModal', false)"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                            Batal
                        </button>
                        <button wire:click="bayarDenda"
                                class="rounded-lg bg-gold px-4 py-2 text-sm font-semibold text-white hover:bg-yellow-700 transition-colors">
                            Konfirmasi Bayar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ Modal Koreksi Jumlah Minggu ============ --}}
    @if ($showKoreksiModal && $koreksiNasabah)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showKoreksiModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-md overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-ink">Koreksi Jumlah Minggu</h3>
                    <p class="mt-3 text-sm text-ink">
                        Nasabah <strong>{{ $koreksiNasabah->nama }}</strong> saat ini tercatat setor sebanyak
                        <strong class="font-mono tabular">{{ $koreksiNasabah->frekuensi_setor }} minggu</strong>
                        dengan saldo
                        <strong class="font-mono tabular">Rp {{ number_format($koreksiNasabah->saldo, 0, ',', '.') }}</strong>.
                    </p>

                    <form wire:submit="saveKoreksi" class="mt-5 space-y-4">
                        <div>
                            <label for="koreksiMinggu" class="block text-sm font-medium text-ink">Jumlah Minggu (setor ke-)</label>
                            <input id="koreksiMinggu" type="number" min="0" step="1" wire:model="koreksiMinggu" autofocus
                                   class="mt-1 w-full rounded-lg border-border font-mono tabular focus:border-primary focus:ring-primary">
                            @error('koreksiMinggu')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="koreksiAlasan" class="block text-sm font-medium text-ink">Alasan Koreksi</label>
                            <textarea id="koreksiAlasan" rows="2" wire:model="koreksiAlasan" placeholder="Misal: menyesuaikan minggu aktual lapangan (opsional)"
                                      class="mt-1 w-full rounded-lg border-border focus:border-primary focus:ring-primary"></textarea>
                            @error('koreksiAlasan')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <p class="text-xs text-muted">
                            Saldo akan dihitung ulang: setoran/minggu &times; jumlah minggu baru. Perubahan tercatat di riwayat sebagai koreksi.
                        </p>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('showKoreksiModal', false)"
                                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                                Simpan Koreksi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ Modal Impor CSV ============ --}}
    @if ($showImportModal)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showImportModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-lg overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-ink">Impor Nasabah (CSV)</h3>
                    <p class="mt-2 text-xs text-muted leading-relaxed">
                        Format kolom: <span class="font-mono">A=Nama, B=Setoran/Minggu, C=Frekuensi</span>.
                        Kolom lain diabaikan. Saldo dihitung otomatis (frekuensi &times; setoran).
                    </p>

                    @if ($importHasil)
                        <div class="mt-4 rounded-lg border border-border bg-paper/60 p-4">
                            <p class="text-sm font-semibold text-primary-dark">
                                {{ $importHasil['berhasil'] }} nasabah berhasil diimpor.
                            </p>
                            @if ($importHasil['gagal'])
                                <p class="mt-2 text-xs font-semibold text-coral">Beberapa baris dilewati:</p>
                                <ul class="mt-1 space-y-1">
                                    @foreach ($importHasil['gagal'] as $g)
                                        <li class="text-xs text-muted">• {{ $g['nama'] }} — {{ $g['alasan'] }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    @endif

                    <form wire:submit="import" class="mt-5 space-y-4">
                        <div>
                            <label for="importFile" class="block text-sm font-medium text-ink">File CSV</label>
                            <input id="importFile" type="file" wire:model="importFile" accept=".csv,text/csv"
                                   class="mt-1 block w-full text-sm text-muted file:me-3 file:rounded-lg file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:font-semibold file:text-primary-dark hover:file:bg-primary/20">
                            @error('importFile')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <div>
                            <label for="importTanggalMulai" class="block text-sm font-medium text-ink">Tanggal Mulai Koperasi (opsional)</label>
                            <input id="importTanggalMulai" type="date" wire:model="importTanggalMulai"
                                   class="mt-1 w-full rounded-lg border-border text-sm focus:border-primary focus:ring-primary">
                            @error('importTanggalMulai')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="rounded-lg border border-coral/40 bg-coral/10 p-4">
                            <label class="flex items-start gap-2 text-sm text-coral">
                                <input type="checkbox" wire:model="importKonfirmasiBersih" class="mt-0.5 rounded border-coral/40 text-coral focus:ring-coral">
                                <span>
                                    Saya mengerti, impor akan <strong>menghapus seluruh nasabah &amp; riwayat yang ada</strong> sebelum mengisi data baru.
                                </span>
                            </label>
                            @error('importKonfirmasiBersih')
                                <p class="mt-1 text-sm text-coral">{{ $message }}</p>
                            @enderror
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <button type="button" wire:click="$set('showImportModal', false)"
                                    class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                                Batal
                            </button>
                            <button type="submit"
                                    class="rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white hover:bg-primary-dark transition-colors">
                                Impor &amp; Bersihkan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ============ Modal Riwayat per Nasabah ============ --}}
    @if ($showRiwayatModal && $riwayatNasabah)
        <div class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div class="absolute inset-0 bg-ink/50" wire:click="$set('showRiwayatModal', false)"></div>
            <div class="relative bg-white rounded-xl border border-border shadow-xl w-full max-w-lg overflow-hidden">
                <div class="paper-holes h-3 border-b border-border"></div>
                <div class="p-6">
                    <h3 class="font-display text-xl font-semibold text-ink">Riwayat: {{ $riwayatNasabah->nama }}</h3>

                    <div class="mt-4 max-h-96 overflow-y-auto">
                        @forelse ($riwayatItems as $log)
                            <div class="ledger-row py-3 flex items-start justify-between gap-3">
                                <div>
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
                                    <span class="inline-block rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $jenisBadge }}">{{ $jenisLabel }}</span>
                                    <p class="mt-1 text-xs text-muted">{{ $log->created_at->format('d M Y, H:i') }}</p>
                                    @if ($log->keterangan)
                                        <p class="mt-1 text-sm text-ink">{{ $log->keterangan }}</p>
                                    @endif
                                </div>
                                @if ($log->jumlah > 0)
                                    <p class="font-mono tabular text-sm font-semibold text-primary-dark whitespace-nowrap">
                                        Rp {{ number_format($log->jumlah, 0, ',', '.') }}
                                    </p>
                                @endif
                            </div>
                        @empty
                            <p class="py-8 text-center text-sm text-muted">Belum ada riwayat transaksi.</p>
                        @endforelse
                    </div>

                    <div class="flex justify-end pt-4">
                        <button wire:click="$set('showRiwayatModal', false)"
                                class="rounded-lg border border-border px-4 py-2 text-sm font-medium text-muted hover:text-ink transition-colors">
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif
    @endif
</div>
