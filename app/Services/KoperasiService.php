<?php

namespace App\Services;

use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\RiwayatTransaksi;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class KoperasiService
{
    public const DEFAULT_TANGGAL_MULAI = '2026-03-23';

    /**
     * Tanggal koperasi mulai berjalan — disimpan di tabel pengaturan,
     * bisa diubah admin tanpa deploy ulang.
     */
    public function tanggalMulai(): Carbon
    {
        $value = Pengaturan::getValue('tanggal_mulai', self::DEFAULT_TANGGAL_MULAI);

        return Carbon::parse($value)->startOfDay();
    }

    /**
     * Jumlah minggu yang seharusnya sudah berjalan sejak tanggal_mulai.
     */
    public function mingguSeharusnya(?Carbon $sekarang = null): int
    {
        $sekarang = $sekarang ?? Carbon::now();

        $selisihHari = $this->tanggalMulai()->diffInDays($sekarang, false);

        if ($selisihHari <= 0) {
            return 1;
        }

        return max(1, (int) ceil($selisihHari / 7));
    }

    /**
     * Berapa minggu nasabah tertinggal dari seharusnya.
     */
    public function telat(Nasabah $nasabah, ?Carbon $sekarang = null): int
    {
        return max(0, $this->mingguSeharusnya($sekarang) - $nasabah->frekuensi_setor);
    }

    /**
     * Denda real-time: telat > 3 minggu -> telat * setoran_mingguan * 5%.
     * Dibulatkan ke rupiah terdekat. Tidak dikunci/disimpan permanen.
     */
    public function denda(Nasabah $nasabah, ?Carbon $sekarang = null): int
    {
        $telat = $this->telat($nasabah, $sekarang);

        if ($telat > 3) {
            return (int) round($telat * $nasabah->setoran_mingguan * 0.05);
        }

        return 0;
    }

    /**
     * Status nasabah: lunas / mendekati / telat.
     */
    public function status(Nasabah $nasabah, ?Carbon $sekarang = null): string
    {
        $telat = $this->telat($nasabah, $sekarang);

        if ($telat > 3) {
            return 'telat';
        }

        if ($telat > 0) {
            return 'mendekati';
        }

        return 'lunas';
    }

    /**
     * Daftarkan nasabah baru. Nama unik case-insensitive.
     */
    public function daftarNasabah(string $nama, int $setoranMingguan, int $userId): Nasabah
    {
        $nama = trim($nama);

        if ($this->namaDipakai($nama)) {
            throw new InvalidArgumentException('Nama nasabah sudah terdaftar.');
        }

        return DB::transaction(function () use ($nama, $setoranMingguan, $userId) {
            $nasabah = Nasabah::create([
                'nama' => $nama,
                'setoran_mingguan' => $setoranMingguan,
                'frekuensi_setor' => 0,
                'saldo' => 0,
            ]);

            $this->catatRiwayat($nasabah, 'daftar', 0, 'Nasabah baru terdaftar', $userId);

            return $nasabah;
        });
    }

    /**
     * Edit nama dan/atau setoran mingguan. Kalau setoran berubah,
     * saldo dihitung ulang = frekuensi_setor * setoran_mingguan_baru.
     */
    public function editNasabah(Nasabah $nasabah, string $nama, int $setoranMingguan, int $userId): Nasabah
    {
        $nama = trim($nama);

        if ($this->namaDipakai($nama, $nasabah->id)) {
            throw new InvalidArgumentException('Nama nasabah sudah dipakai nasabah lain.');
        }

        return DB::transaction(function () use ($nasabah, $nama, $setoranMingguan, $userId) {
            $nasabah = Nasabah::lockForUpdate()->findOrFail($nasabah->id);

            $perubahan = [];
            if ($nama !== $nasabah->nama) {
                $perubahan[] = 'nama: ' . $nasabah->nama . ' -> ' . $nama;
            }
            if ($setoranMingguan !== $nasabah->setoran_mingguan) {
                $perubahan[] = 'setoran: Rp ' . number_format($nasabah->setoran_mingguan, 0, ',', '.')
                    . ' -> Rp ' . number_format($setoranMingguan, 0, ',', '.');
            }

            $nasabah->nama = $nama;
            $nasabah->setoran_mingguan = $setoranMingguan;
            $nasabah->saldo = $nasabah->frekuensi_setor * $setoranMingguan;
            $nasabah->save();

            $this->catatRiwayat(
                $nasabah,
                'edit',
                0,
                $perubahan ? 'Ubah ' . implode(', ', $perubahan) : 'Tidak ada perubahan',
                $userId
            );

            return $nasabah;
        });
    }

    /**
     * Hapus nasabah (hard delete). Riwayat tetap ada via snapshot nasabah_nama.
     * Riwayat jenis "hapus" dicatat SEBELUM record dihapus.
     */
    public function hapusNasabah(Nasabah $nasabah, int $userId): void
    {
        DB::transaction(function () use ($nasabah, $userId) {
            $nasabah = Nasabah::lockForUpdate()->findOrFail($nasabah->id);

            $this->catatRiwayat(
                $nasabah,
                'hapus',
                0,
                'Nasabah dihapus. Sisa saldo: Rp ' . number_format($nasabah->saldo, 0, ',', '.'),
                $userId
            );

            $nasabah->delete();
        });
    }

    /**
     * Setor mingguan massal. Terima array [{nasabah_id, jumlah_minggu}],
     * diproses dalam SATU DB transaction dengan row locking.
     */
    public function setorMassal(array $items, int $userId): void
    {
        if (empty($items)) {
            throw new InvalidArgumentException('Tidak ada nasabah yang dipilih untuk disetor.');
        }

        DB::transaction(function () use ($items, $userId) {
            foreach ($items as $item) {
                $jumlahMinggu = (int) ($item['jumlah_minggu'] ?? 0);

                if ($jumlahMinggu < 1) {
                    throw new InvalidArgumentException('Jumlah minggu setoran harus minimal 1.');
                }

                $nasabah = Nasabah::lockForUpdate()->findOrFail($item['nasabah_id']);

                $nasabah->frekuensi_setor += $jumlahMinggu;
                $nasabah->saldo = $nasabah->frekuensi_setor * $nasabah->setoran_mingguan;
                $nasabah->save();

                $nominal = $jumlahMinggu * $nasabah->setoran_mingguan;

                $this->catatRiwayat(
                    $nasabah,
                    'setor',
                    $nominal,
                    'Setor ' . $jumlahMinggu . ' minggu (setor ke-' . $nasabah->frekuensi_setor . ')',
                    $userId
                );
            }
        });
    }

    /**
     * Setor mingguan untuk satu nasabah — satu transaksi terpisah.
     */
    public function setorSatu(Nasabah $nasabah, int $jumlahMinggu, int $userId): void
    {
        $this->setorMassal([['nasabah_id' => $nasabah->id, 'jumlah_minggu' => $jumlahMinggu]], $userId);
    }

    /**
     * Koreksi manual jumlah minggu pembayaran nasabah (mis. menyesuaikan
     * dengan aktual lapangan). Saldo dihitung ulang dan perubahan dicatat
     * di riwayat sebagai jenis "koreksi".
     */
    public function koreksiFrekuensi(Nasabah $nasabah, int $mingguBaru, ?string $alasan, int $userId): void
    {
        if ($mingguBaru < 0) {
            throw new InvalidArgumentException('Jumlah minggu tidak boleh negatif.');
        }

        DB::transaction(function () use ($nasabah, $mingguBaru, $alasan, $userId) {
            $nasabah = Nasabah::lockForUpdate()->findOrFail($nasabah->id);

            $mingguLama = $nasabah->frekuensi_setor;

            if ($mingguLama === $mingguBaru) {
                throw new InvalidArgumentException('Jumlah minggu sama dengan sebelumnya, tidak ada yang dikoreksi.');
            }

            $nasabah->frekuensi_setor = $mingguBaru;
            $nasabah->saldo = $mingguBaru * $nasabah->setoran_mingguan;
            $nasabah->save();

            $keterangan = 'Koreksi jumlah minggu pembayaran: ' . $mingguLama . ' -> ' . $mingguBaru;
            if ($alasan) {
                $keterangan .= ' (' . trim($alasan) . ')';
            }

            $this->catatRiwayat($nasabah, 'koreksi', 0, $keterangan, $userId);
        });
    }

    /**
     * Bayar denda: nasabah "catch up" penuh — frekuensi_setor diset ke
     * mingguSeharusnya() saat ini, saldo dihitung ulang. Nominal denda yang
     * berlaku dicatat di riwayat.
     */
    public function bayarDenda(Nasabah $nasabah, int $userId): void
    {
        DB::transaction(function () use ($nasabah, $userId) {
            $nasabah = Nasabah::lockForUpdate()->findOrFail($nasabah->id);

            $dendaBerlaku = $this->denda($nasabah);
            $mingguSeharusnya = $this->mingguSeharusnya();
            $tunggakan = $this->telat($nasabah);

            $nasabah->frekuensi_setor = $mingguSeharusnya;
            $nasabah->saldo = $nasabah->frekuensi_setor * $nasabah->setoran_mingguan;
            $nasabah->save();

            $this->catatRiwayat(
                $nasabah,
                'bayar_denda',
                $dendaBerlaku,
                'Melunasi tunggakan ' . $tunggakan . ' minggu (catch up ke minggu ke-' . $mingguSeharusnya . ')',
                $userId
            );
        });
    }

    /**
     * Hapus semua nasabah & riwayat transaksi (user admin tetap ada).
     * Dipakai untuk memulai ulang data / sebelum impor massal.
     */
    public function bersihkanDataNasabah(): void
    {
        DB::transaction(function () {
            DB::table('riwayat_transaksi')->delete();
            DB::table('nasabah')->delete();
        });
    }

    /**
     * Impor nasabah massal dari data awal. Tiap nasabah dibuat dengan
     * saldo = frekuensi_setor * setoran_mingguan, lalu dicatat riwayat
     * jenis "daftar". Baris tidak valid dilewati dan dilaporkan.
     *
     * @param  array<int, array{nama: string, setoran_mingguan: int, frekuensi_setor: int}>  $rows
     * @return array{berhasil: int, gagal: array<int, array{nama: string, alasan: string}>}
     */
    public function imporNasabah(array $rows, int $userId): array
    {
        $berhasil = 0;
        $gagal = [];

        DB::transaction(function () use ($rows, $userId, &$berhasil, &$gagal) {
            foreach ($rows as $row) {
                $nama = trim((string) ($row['nama'] ?? ''));
                $setoran = (int) ($row['setoran_mingguan'] ?? 0);
                $frekuensi = (int) ($row['frekuensi_setor'] ?? 0);

                if ($nama === '') {
                    $gagal[] = ['nama' => '(tanpa nama)', 'alasan' => 'Nama kosong.'];

                    continue;
                }

                if ($setoran < 1) {
                    $gagal[] = ['nama' => $nama, 'alasan' => 'Setoran per minggu tidak valid.'];

                    continue;
                }

                if ($frekuensi < 0) {
                    $gagal[] = ['nama' => $nama, 'alasan' => 'Frekuensi tidak valid.'];

                    continue;
                }

                if ($this->namaDipakai($nama)) {
                    $gagal[] = ['nama' => $nama, 'alasan' => 'Nama duplikat.'];

                    continue;
                }

                $nasabah = Nasabah::create([
                    'nama' => $nama,
                    'setoran_mingguan' => $setoran,
                    'frekuensi_setor' => $frekuensi,
                    'saldo' => $frekuensi * $setoran,
                ]);

                $this->catatRiwayat($nasabah, 'daftar', 0, 'Nasabah terdaftar (impor data awal)', $userId);

                $berhasil++;
            }
        });

        return ['berhasil' => $berhasil, 'gagal' => $gagal];
    }

    /**
     * Ringkasan untuk kartu dashboard.
     */
    public function ringkasan(): array
    {
        $daftarNasabah = Nasabah::all();

        return [
            'total_nasabah' => $daftarNasabah->count(),
            'total_saldo' => $daftarNasabah->sum('saldo'),
            'total_telat' => $daftarNasabah->filter(fn (Nasabah $n) => $this->telat($n) > 3)->count(),
            'total_denda' => $daftarNasabah->sum(fn (Nasabah $n) => $this->denda($n)),
            'setoran_hari_ini' => $this->totalSetoran(now()->startOfDay()),
        ];
    }

    /**
     * Ringkasan setoran untuk laporan dalam periode tertentu.
     * Periode ditentukan lewat batas awal $dari (inclusive).
     */
    public function ringkasanSetoran(?Carbon $dari = null): array
    {
        $dari = $dari ?? now()->startOfDay();

        $setor = RiwayatTransaksi::query()
            ->where('jenis', 'setor')
            ->where('created_at', '>=', $dari)
            ->selectRaw('COUNT(*) as jumlah_transaksi, COALESCE(SUM(jumlah), 0) as total_setoran, COUNT(DISTINCT nasabah_id) as jumlah_nasabah')
            ->first();

        $totalDenda = RiwayatTransaksi::query()
            ->where('jenis', 'bayar_denda')
            ->where('created_at', '>=', $dari)
            ->sum('jumlah');

        return [
            'total_setoran' => (int) $setor->total_setoran,
            'jumlah_transaksi' => (int) $setor->jumlah_transaksi,
            'jumlah_nasabah' => (int) $setor->jumlah_nasabah,
            'total_denda' => (int) $totalDenda,
        ];
    }

    private function totalSetoran(Carbon $dari): int
    {
        return (int) RiwayatTransaksi::query()
            ->where('jenis', 'setor')
            ->where('created_at', '>=', $dari)
            ->sum('jumlah');
    }

    private function namaDipakai(string $nama, ?int $kecualiId = null): bool
    {
        return Nasabah::query()
            ->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))
            ->whereRaw('LOWER(nama) = ?', [mb_strtolower($nama)])
            ->exists();
    }

    private function catatRiwayat(Nasabah $nasabah, string $jenis, int $jumlah, ?string $keterangan, int $userId): void
    {
        RiwayatTransaksi::create([
            'nasabah_id' => $nasabah->id,
            'nasabah_nama' => $nasabah->nama,
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => $keterangan,
            'user_id' => $userId,
        ]);
    }
}
