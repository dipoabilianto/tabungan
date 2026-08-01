<?php

namespace Tests\Feature;

use App\Livewire\Dashboard;
use App\Livewire\Laporan;
use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\RiwayatTransaksi;
use App\Models\User;
use App\Services\KoperasiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class LaporanTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Pengaturan::setValue('tanggal_mulai', KoperasiService::DEFAULT_TANGGAL_MULAI);
        $this->admin = User::factory()->create();
    }

    public function test_halaman_laporan_dialihkan_ke_login_ketika_belum_auth(): void
    {
        $this->get('/laporan')->assertRedirect('/login');
    }

    public function test_filter_hari_ini_hanya_menampilkan_setoran_hari_ini(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $this->catatSetor(10000, Carbon::parse('2026-08-02 08:00:00'));
        $this->catatSetor(50000, Carbon::parse('2026-07-30 10:00:00'));
        $this->catatSetor(70000, Carbon::parse('2026-07-13 10:00:00'));

        Livewire::actingAs($this->admin)
            ->test(Laporan::class)
            ->assertSee('Rp 10.000')
            ->assertDontSee('Rp 50.000')
            ->assertDontSee('Rp 70.000');

        Carbon::setTestNow();
    }

    public function test_filter_satu_minggu_mencakup_tujuh_hari_terakhir(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $this->catatSetor(10000, Carbon::parse('2026-08-02 08:00:00'));
        $this->catatSetor(50000, Carbon::parse('2026-07-30 10:00:00'));
        $this->catatSetor(70000, Carbon::parse('2026-07-13 10:00:00'));

        Livewire::actingAs($this->admin)
            ->test(Laporan::class)
            ->set('periode', 'minggu')
            ->assertSee('Rp 10.000')
            ->assertSee('Rp 50.000')
            ->assertDontSee('Rp 70.000');

        Carbon::setTestNow();
    }

    public function test_filter_satu_bulan_mencakup_tiga_puluh_hari_terakhir(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $this->catatSetor(10000, Carbon::parse('2026-08-02 08:00:00'));
        $this->catatSetor(50000, Carbon::parse('2026-07-30 10:00:00'));
        $this->catatSetor(70000, Carbon::parse('2026-07-13 10:00:00'));

        Livewire::actingAs($this->admin)
            ->test(Laporan::class)
            ->set('periode', 'bulan')
            ->assertSee('Rp 10.000')
            ->assertSee('Rp 50.000')
            ->assertSee('Rp 70.000');

        Carbon::setTestNow();
    }

    public function test_laporan_mencakup_denda_terbayar(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $nasabah = Nasabah::create(['nama' => 'Sukma', 'setoran_mingguan' => 10000]);
        $this->catat('bayar_denda', 2500, $nasabah, Carbon::parse('2026-08-02 09:00:00'));

        Livewire::actingAs($this->admin)
            ->test(Laporan::class)
            ->assertSee('Rp 2.500');

        Carbon::setTestNow();
    }

    public function test_ringkasan_setoran_menghitung_total_dan_nasabah_unik(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $a = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);
        $b = Nasabah::create(['nama' => 'Painem', 'setoran_mingguan' => 15000]);
        $this->catat('setor', 10000, $a, Carbon::parse('2026-08-02 08:00:00'));
        $this->catat('setor', 20000, $a, Carbon::parse('2026-08-02 09:00:00'));
        $this->catat('setor', 15000, $b, Carbon::parse('2026-08-02 09:30:00'));

        $ringkasan = (new KoperasiService())->ringkasanSetoran(Carbon::parse('2026-08-02 00:00:00'));

        $this->assertSame(45000, $ringkasan['total_setoran']);
        $this->assertSame(3, $ringkasan['jumlah_transaksi']);
        $this->assertSame(2, $ringkasan['jumlah_nasabah']);
        $this->assertSame(0, $ringkasan['total_denda']);

        Carbon::setTestNow();
    }

    public function test_dashboard_menampilkan_kartu_setoran_hari_ini(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00'));

        $this->catatSetor(10000, Carbon::parse('2026-08-02 08:00:00'));
        $this->catatSetor(20000, Carbon::parse('2026-07-30 10:00:00'));

        Livewire::actingAs($this->admin)
            ->test(Dashboard::class)
            ->assertSee('Setoran Hari Ini')
            ->assertSee('Rp 10.000');

        Carbon::setTestNow();
    }

    private function catatSetor(int $jumlah, Carbon $waktu): void
    {
        $nasabah = Nasabah::create(['nama' => 'Nasabah ' . uniqid(), 'setoran_mingguan' => 10000]);

        $this->catat('setor', $jumlah, $nasabah, $waktu);
    }

    private function catat(string $jenis, int $jumlah, Nasabah $nasabah, Carbon $waktu): void
    {
        $riwayat = new RiwayatTransaksi([
            'nasabah_id' => $nasabah->id,
            'nasabah_nama' => $nasabah->nama,
            'jenis' => $jenis,
            'jumlah' => $jumlah,
            'keterangan' => 'Transaksi test',
            'user_id' => $this->admin->id,
        ]);

        $riwayat->created_at = $waktu;
        $riwayat->save();
    }
}
