<?php

namespace Tests\Unit;

use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Services\KoperasiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class KoperasiServiceTest extends TestCase
{
    use RefreshDatabase;

    private KoperasiService $service;

    protected function setUp(): void
    {
        parent::setUp();

        Pengaturan::setValue('tanggal_mulai', '2026-03-23');

        $this->service = new KoperasiService();
    }

    private function nasabah(int $frekuensiSetor, int $setoranMingguan = 10000): Nasabah
    {
        return new Nasabah([
            'nama' => 'Uji',
            'setoran_mingguan' => $setoranMingguan,
            'frekuensi_setor' => $frekuensiSetor,
            'saldo' => $frekuensiSetor * $setoranMingguan,
        ]);
    }

    // --- mingguSeharusnya() ---

    public function test_minggu_seharusnya_di_hari_pertama_adalah_satu(): void
    {
        $this->assertSame(1, $this->service->mingguSeharusnya(Carbon::parse('2026-03-23')));
    }

    public function test_minggu_seharusnya_sebelum_tujuh_hari_tetap_satu(): void
    {
        $this->assertSame(1, $this->service->mingguSeharusnya(Carbon::parse('2026-03-29')));
        $this->assertSame(1, $this->service->mingguSeharusnya(Carbon::parse('2026-03-30')));
    }

    public function test_minggu_seharusnya_memasuki_minggu_kedua(): void
    {
        $this->assertSame(2, $this->service->mingguSeharusnya(Carbon::parse('2026-03-31')));
        $this->assertSame(6, $this->service->mingguSeharusnya(Carbon::parse('2026-05-04')));
    }

    // --- telat() ---

    public function test_telat_nol_ketika_sudah_setor_sesuai_jadwal(): void
    {
        // Minggu ke-6, sudah setor 6x -> telat 0
        $nasabah = $this->nasabah(frekuensiSetor: 6);

        $this->assertSame(0, $this->service->telat($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_telat_dua_minggu(): void
    {
        // Minggu ke-6, baru setor 4x -> telat 2
        $nasabah = $this->nasabah(frekuensiSetor: 4);

        $this->assertSame(2, $this->service->telat($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_telat_lima_minggu(): void
    {
        // Minggu ke-6, baru setor 1x -> telat 5
        $nasabah = $this->nasabah(frekuensiSetor: 1);

        $this->assertSame(5, $this->service->telat($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_telat_tidak_pernah_negatif(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 99);

        $this->assertSame(0, $this->service->telat($nasabah, Carbon::parse('2026-05-04')));
    }

    // --- denda() ---

    public function test_denda_nol_ketika_belum_telat(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 6);

        $this->assertSame(0, $this->service->denda($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_denda_nol_ketika_telat_dua_minggu(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 4);

        $this->assertSame(0, $this->service->denda($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_denda_nol_ketika_telat_tepat_tiga_minggu(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 3);

        $this->assertSame(0, $this->service->denda($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_denda_berlaku_ketika_telat_lebih_dari_tiga_minggu(): void
    {
        // Telat 5 minggu, setoran Rp10.000 -> 5 * 10000 * 5% = 2500
        $nasabah = $this->nasabah(frekuensiSetor: 1, setoranMingguan: 10000);

        $this->assertSame(2500, $this->service->denda($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_denda_dibulatkan_ke_rupiah_terdekat(): void
    {
        // Telat 5 minggu, setoran Rp3.333 -> 5 * 3333 * 5% = 833,25 -> 833
        $nasabah = $this->nasabah(frekuensiSetor: 1, setoranMingguan: 3333);

        $this->assertSame(833, $this->service->denda($nasabah, Carbon::parse('2026-05-04')));
    }

    // --- status() ---

    public function test_status_lunas(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 6);

        $this->assertSame('lunas', $this->service->status($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_status_mendekati(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 4);

        $this->assertSame('mendekati', $this->service->status($nasabah, Carbon::parse('2026-05-04')));
    }

    public function test_status_telat(): void
    {
        $nasabah = $this->nasabah(frekuensiSetor: 1);

        $this->assertSame('telat', $this->service->status($nasabah, Carbon::parse('2026-05-04')));
    }

    // --- aksi bisnis (terintegrasi dengan DB & riwayat) ---

    public function test_daftar_nasabah_mencatat_riwayat_dan_menolak_nama_duplikat_case_insensitive(): void
    {
        $user = \App\Models\User::factory()->create();

        $nasabah = $this->service->daftarNasabah('Budi Santoso', 10000, $user->id);

        $this->assertSame(0, $nasabah->frekuensi_setor);
        $this->assertSame(0, $nasabah->saldo);
        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'daftar', 'nasabah_nama' => 'Budi Santoso']);

        $this->expectException(\InvalidArgumentException::class);
        $this->service->daftarNasabah('budi santoso', 5000, $user->id);
    }

    public function test_setor_massal_dalam_satu_transaksi_dan_tercatat_di_riwayat(): void
    {
        $user = \App\Models\User::factory()->create();
        $a = $this->service->daftarNasabah('Ani', 10000, $user->id);
        $b = $this->service->daftarNasabah('Joko', 20000, $user->id);

        $this->service->setorMassal([
            ['nasabah_id' => $a->id, 'jumlah_minggu' => 2],
            ['nasabah_id' => $b->id, 'jumlah_minggu' => 1],
        ], $user->id);

        $this->assertSame(2, $a->fresh()->frekuensi_setor);
        $this->assertSame(20000, $a->fresh()->saldo);
        $this->assertSame(1, $b->fresh()->frekuensi_setor);
        $this->assertSame(20000, $b->fresh()->saldo);

        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'setor', 'nasabah_nama' => 'Ani', 'jumlah' => 20000]);
        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'setor', 'nasabah_nama' => 'Joko', 'jumlah' => 20000]);
    }

    public function test_bayar_denda_catch_up_penuh_dan_tercatat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-04')); // minggu ke-6

        $user = \App\Models\User::factory()->create();
        $nasabah = $this->service->daftarNasabah('Siti', 10000, $user->id);
        $this->service->setorMassal([['nasabah_id' => $nasabah->id, 'jumlah_minggu' => 1]], $user->id);

        // Telat 5 minggu -> denda 2500
        $this->service->bayarDenda($nasabah->fresh(), $user->id);

        $hasil = $nasabah->fresh();
        $this->assertSame(6, $hasil->frekuensi_setor);
        $this->assertSame(60000, $hasil->saldo);
        $this->assertDatabaseHas('riwayat_transaksi', [
            'jenis' => 'bayar_denda',
            'nasabah_nama' => 'Siti',
            'jumlah' => 2500,
        ]);

        Carbon::setTestNow();
    }

    public function test_hapus_nasabah_riwayat_tetap_ada_dengan_snapshot_nama(): void
    {
        $user = \App\Models\User::factory()->create();
        $nasabah = $this->service->daftarNasabah('Rina', 5000, $user->id);

        $this->service->hapusNasabah($nasabah, $user->id);

        $this->assertDatabaseMissing('nasabah', ['id' => $nasabah->id]);
        $this->assertDatabaseHas('riwayat_transaksi', [
            'jenis' => 'hapus',
            'nasabah_nama' => 'Rina',
            'nasabah_id' => null,
        ]);
    }
}
