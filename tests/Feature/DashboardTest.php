<?php

namespace Tests\Feature;

use App\Livewire\NasabahTable;
use App\Livewire\SetorCepat;
use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\KoperasiService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Pengaturan::setValue('tanggal_mulai', KoperasiService::DEFAULT_TANGGAL_MULAI);
        $this->admin = User::factory()->create();
    }

    public function test_dashboard_dialihkan_ke_login_ketika_belum_auth(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_dashboard_bisa_diakses_setelah_login(): void
    {
        $this->actingAs($this->admin)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('Buku Tabungan Koperasi');
    }

    public function test_tambah_nasabah_lewat_komponen(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('create')
            ->set('nama', 'Dewi Lestari')
            ->set('setoran_mingguan', 12000)
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('nasabah', ['nama' => 'Dewi Lestari', 'setoran_mingguan' => 12000]);
        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'daftar', 'nasabah_nama' => 'Dewi Lestari']);
    }

    public function test_validasi_nasabah_ditolak_ketika_input_kosong(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('create')
            ->call('save')
            ->assertHasErrors(['nama', 'setoran_mingguan']);
    }

    public function test_nama_duplikat_case_insensitive_ditolak(): void
    {
        Nasabah::create(['nama' => 'Agus', 'setoran_mingguan' => 10000]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('create')
            ->set('nama', 'AGUS')
            ->set('setoran_mingguan', 5000)
            ->call('save')
            ->assertHasErrors(['nama']);
    }

    public function test_edit_nasabah_menghitung_ulang_saldo(): void
    {
        $nasabah = Nasabah::create([
            'nama' => 'Rudi',
            'setoran_mingguan' => 10000,
            'frekuensi_setor' => 3,
            'saldo' => 30000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('edit', $nasabah->id)
            ->set('setoran_mingguan', 20000)
            ->call('save')
            ->assertHasNoErrors();

        $hasil = $nasabah->fresh();
        $this->assertSame(20000, $hasil->setoran_mingguan);
        $this->assertSame(60000, $hasil->saldo); // 3 x 20000
        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'edit', 'nasabah_nama' => 'Rudi']);
    }

    public function test_setor_cepat_massal(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-23 10:00:00')); // minggu ke-1

        $a = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);
        $b = Nasabah::create(['nama' => 'Painem', 'setoran_mingguan' => 15000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->call('tambah', $a->id)
            ->set('keranjang.' . $a->id, 2)
            ->call('tambah', $b->id)
            ->call('proses')
            ->assertHasNoErrors();

        $this->assertSame(2, $a->fresh()->frekuensi_setor);
        $this->assertSame(20000, $a->fresh()->saldo);
        $this->assertSame(1, $b->fresh()->frekuensi_setor); // default 1 minggu
        $this->assertSame(15000, $b->fresh()->saldo);

        Carbon::setTestNow();
    }

    public function test_tambah_hapus_keranjang(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-23 10:00:00')); // minggu ke-1

        $a = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->call('tambah', $a->id)
            ->assertSet('keranjang.' . $a->id, 1)
            ->call('hapus', $a->id)
            ->assertSet('keranjang', []);

        Carbon::setTestNow();
    }

    public function test_setor_satu_nasabah_langsung(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-23 10:00:00')); // minggu ke-1

        $a = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->set('minggu.' . $a->id, 3)
            ->call('setorSatu', $a->id)
            ->assertHasNoErrors();

        $this->assertSame(3, $a->fresh()->frekuensi_setor);
        $this->assertSame(30000, $a->fresh()->saldo);

        Carbon::setTestNow();
    }

    public function test_keranjang_dikosongkan_setelah_proses(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-03-23 10:00:00')); // minggu ke-1

        $a = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->call('tambah', $a->id)
            ->call('proses')
            ->assertHasNoErrors()
            ->assertSet('keranjang', []);

        Carbon::setTestNow();
    }

    public function test_setor_cepat_dengan_keranjang_kosong_ditolak(): void
    {
        Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->call('proses')
            ->assertHasErrors(['keranjang']);
    }

    public function test_bayar_denda_lewat_komponen(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-05-04')); // minggu ke-6

        $nasabah = Nasabah::create([
            'nama' => 'Sukma',
            'setoran_mingguan' => 10000,
            'frekuensi_setor' => 1,
            'saldo' => 10000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('confirmDenda', $nasabah->id)
            ->call('bayarDenda')
            ->assertHasNoErrors();

        $hasil = $nasabah->fresh();
        $this->assertSame(6, $hasil->frekuensi_setor);
        $this->assertSame(60000, $hasil->saldo);
        $this->assertDatabaseHas('riwayat_transaksi', [
            'jenis' => 'bayar_denda',
            'nasabah_nama' => 'Sukma',
            'jumlah' => 2500,
        ]);

        Carbon::setTestNow();
    }

    public function test_hapus_nasabah_lewat_komponen(): void
    {
        $nasabah = Nasabah::create(['nama' => 'Bagas', 'setoran_mingguan' => 8000]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('confirmDelete', $nasabah->id)
            ->call('delete')
            ->assertHasNoErrors();

        $this->assertDatabaseMissing('nasabah', ['nama' => 'Bagas']);
        $this->assertDatabaseHas('riwayat_transaksi', ['jenis' => 'hapus', 'nasabah_nama' => 'Bagas']);
    }

    public function test_koreksi_minggu_mengubah_frekuensi_dan_mencatat_riwayat(): void
    {
        $nasabah = Nasabah::create([
            'nama' => 'Rudi',
            'setoran_mingguan' => 10000,
            'frekuensi_setor' => 5,
            'saldo' => 50000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('confirmKoreksi', $nasabah->id)
            ->set('koreksiMinggu', 8)
            ->set('koreksiAlasan', 'Libur 3 minggu')
            ->call('saveKoreksi')
            ->assertHasNoErrors();

        $hasil = $nasabah->fresh();
        $this->assertSame(8, $hasil->frekuensi_setor);
        $this->assertSame(80000, $hasil->saldo);
        $this->assertDatabaseHas('riwayat_transaksi', [
            'nasabah_id' => $nasabah->id,
            'jenis' => 'koreksi',
            'keterangan' => 'Koreksi jumlah minggu pembayaran: 5 -> 8 (Libur 3 minggu)',
        ]);
    }

    public function test_koreksi_minggu_menolak_nilai_yang_sama(): void
    {
        $nasabah = Nasabah::create([
            'nama' => 'Rudi',
            'setoran_mingguan' => 10000,
            'frekuensi_setor' => 5,
            'saldo' => 50000,
        ]);

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->call('confirmKoreksi', $nasabah->id)
            ->set('koreksiMinggu', 5)
            ->call('saveKoreksi')
            ->assertHasErrors(['koreksiMinggu']);

        $this->assertSame(5, $nasabah->fresh()->frekuensi_setor);
    }

    public function test_setor_satu_ditolak_untuk_nasabah_telat_diatas_3_minggu(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00')); // minggu ke-19

        $telat = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 5, 'saldo' => 50000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->set('minggu.' . $telat->id, 1)
            ->call('setorSatu', $telat->id)
            ->assertHasErrors(['telat.' . $telat->id]);

        $this->assertSame(5, $telat->fresh()->frekuensi_setor);

        Carbon::setTestNow();
    }

    public function test_tambah_keranjang_ditolak_untuk_nasabah_telat_diatas_3_minggu(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00')); // minggu ke-19

        $telat = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 5, 'saldo' => 50000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->call('tambah', $telat->id)
            ->assertHasErrors(['telat.' . $telat->id])
            ->assertSet('keranjang', []);

        Carbon::setTestNow();
    }

    public function test_proses_ditolak_jika_keranjang_berisi_nasabah_telat_diatas_3_minggu(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00')); // minggu ke-19

        $telat = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 5, 'saldo' => 50000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->set('keranjang.' . $telat->id, 1)
            ->call('proses')
            ->assertHasErrors(['keranjang']);

        $this->assertSame(5, $telat->fresh()->frekuensi_setor);

        Carbon::setTestNow();
    }

    public function test_setor_cepat_tetap_meloloskan_nasabah_tidak_telat(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-08-02 10:00:00')); // minggu ke-19

        $lancar = Nasabah::create(['nama' => 'Painem', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 19, 'saldo' => 190000]);

        Livewire::actingAs($this->admin)
            ->test(SetorCepat::class)
            ->set('minggu.' . $lancar->id, 1)
            ->call('setorSatu', $lancar->id)
            ->assertHasNoErrors();

        $this->assertSame(20, $lancar->fresh()->frekuensi_setor);

        Carbon::setTestNow();
    }

    public function test_tab_nasabah_mengharuskan_password_login(): void
    {
        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->assertSet('showPasswordModal', true)
            ->set('password', 'salah')
            ->call('verifikasiPassword')
            ->assertHasErrors(['password'])
            ->set('password', 'password')
            ->call('verifikasiPassword')
            ->assertHasNoErrors()
            ->assertSet('showPasswordModal', false);
    }
}
