<?php

namespace Tests\Feature;

use App\Livewire\NasabahTable;
use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\RiwayatTransaksi;
use App\Models\User;
use App\Services\KoperasiService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Livewire\Livewire;
use Tests\TestCase;

class ImportNasabahTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Pengaturan::setValue('tanggal_mulai', KoperasiService::DEFAULT_TANGGAL_MULAI);
        $this->admin = User::factory()->create();
    }

    public function test_impor_membuat_nasabah_dengan_saldo_hitung_ulang(): void
    {
        $rows = [
            ['nama' => 'Paijo', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 20],
            ['nama' => 'Painem', 'setoran_mingguan' => 15000, 'frekuensi_setor' => 3],
        ];

        $hasil = (new KoperasiService())->imporNasabah($rows, $this->admin->id);

        $this->assertSame(2, $hasil['berhasil']);
        $this->assertEmpty($hasil['gagal']);

        $paijo = Nasabah::where('nama', 'Paijo')->first();
        $this->assertSame(10000, $paijo->setoran_mingguan);
        $this->assertSame(20, $paijo->frekuensi_setor);
        $this->assertSame(200000, $paijo->saldo);

        $painem = Nasabah::where('nama', 'Painem')->first();
        $this->assertSame(45000, $painem->saldo);

        $this->assertDatabaseHas('riwayat_transaksi', [
            'nasabah_id' => $paijo->id,
            'jenis' => 'daftar',
            'keterangan' => 'Nasabah terdaftar (impor data awal)',
        ]);
    }

    public function test_impor_melewati_baris_tidak_valid(): void
    {
        $rows = [
            ['nama' => 'Valid', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 5],
            ['nama' => '', 'setoran_mingguan' => 10000, 'frekuensi_setor' => 5],
            ['nama' => 'Setoran Nol', 'setoran_mingguan' => 0, 'frekuensi_setor' => 5],
            ['nama' => 'Valid', 'setoran_mingguan' => 20000, 'frekuensi_setor' => 1],
        ];

        $hasil = (new KoperasiService())->imporNasabah($rows, $this->admin->id);

        $this->assertSame(1, $hasil['berhasil']);
        $this->assertCount(3, $hasil['gagal']);
        $this->assertSame(1, Nasabah::count());
    }

    public function test_bersihkan_data_menghapus_nasabah_dan_riwayat_tapi_user_admin_tetap(): void
    {
        $nasabah = Nasabah::create(['nama' => 'Paijo', 'setoran_mingguan' => 10000]);
        RiwayatTransaksi::create([
            'nasabah_id' => $nasabah->id,
            'nasabah_nama' => $nasabah->nama,
            'jenis' => 'daftar',
            'jumlah' => 0,
            'keterangan' => null,
            'user_id' => $this->admin->id,
        ]);

        (new KoperasiService())->bersihkanDataNasabah();

        $this->assertSame(0, Nasabah::count());
        $this->assertSame(0, RiwayatTransaksi::count());
        $this->assertSame(1, User::count());
    }

    public function test_impor_csv_lewat_komponen_mengisi_data_dan_set_tanggal_mulai(): void
    {
        Nasabah::create(['nama' => 'Lama', 'setoran_mingguan' => 9000]);

        $csv = "Nama,Setoran_Per_Minggu,Total_Tabungan,Frekuensi,Tanggal_Terakhir_Setor,Denda_Akumulasi\n"
            . "Paijo,10000,20,200000,7/17/2026 13:06:56,0\n"
            . "Painem,15000,3,45000,7/17/2026 13:06:42,0\n";

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('nasabah.csv', $csv))
            ->set('importTanggalMulai', '2026-01-01')
            ->set('importKonfirmasiBersih', true)
            ->call('import')
            ->assertHasNoErrors();

        $this->assertSame(2, Nasabah::count());
        $this->assertDatabaseHas('nasabah', ['nama' => 'Paijo', 'frekuensi_setor' => 20, 'saldo' => 200000]);
        $this->assertDatabaseHas('nasabah', ['nama' => 'Painem', 'frekuensi_setor' => 3, 'saldo' => 45000]);
        $this->assertSame('2026-01-01', Pengaturan::getValue('tanggal_mulai'));
    }

    public function test_impor_mengharuskan_konfirmasi_bersih(): void
    {
        $csv = "Nama,Setoran_Per_Minggu,Total_Tabungan,Frekuensi,Tanggal_Terakhir_Setor,Denda_Akumulasi\n"
            . "Paijo,10000,20,200000,7/17/2026 13:06:56,0\n";

        Livewire::actingAs($this->admin)
            ->test(NasabahTable::class)
            ->set('importFile', UploadedFile::fake()->createWithContent('nasabah.csv', $csv))
            ->call('import')
            ->assertHasErrors(['importKonfirmasiBersih']);

        $this->assertSame(0, Nasabah::count());
    }
}
