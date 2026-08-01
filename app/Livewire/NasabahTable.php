<?php

namespace App\Livewire;

use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\RiwayatTransaksi;
use App\Services\KoperasiService;
use InvalidArgumentException;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class NasabahTable extends Component
{
    use WithFileUploads;

    // Gate password saat membuka tab nasabah
    public bool $showPasswordModal = true;
    public string $password = '';
    public string $search = '';

    // Modal tambah/edit
    public bool $showFormModal = false;
    public ?int $editingId = null;
    public string $nama = '';
    public string $setoran_mingguan = '';

    // Modal konfirmasi hapus
    public bool $showDeleteModal = false;
    public ?int $deletingId = null;

    // Modal bayar denda
    public bool $showDendaModal = false;
    public ?int $dendaId = null;

    // Modal riwayat per nasabah
    public bool $showRiwayatModal = false;
    public ?int $riwayatNasabahId = null;

    // Modal koreksi jumlah minggu
    public bool $showKoreksiModal = false;
    public ?int $koreksiId = null;
    public string $koreksiMinggu = '';
    public string $koreksiAlasan = '';

    // Modal impor CSV
    public bool $showImportModal = false;
    public $importFile = null;
    public string $importTanggalMulai = '';
    public bool $importKonfirmasiBersih = false;
    public ?array $importHasil = null;

    protected function rules(): array
    {
        return [
            'nama' => ['required', 'string', 'max:255'],
            'setoran_mingguan' => ['required', 'numeric', 'min:1'],
        ];
    }

    protected function messages(): array
    {
        return [
            'nama.required' => 'Nama nasabah wajib diisi.',
            'nama.max' => 'Nama nasabah maksimal 255 karakter.',
            'setoran_mingguan.required' => 'Setoran mingguan wajib diisi.',
            'setoran_mingguan.numeric' => 'Setoran mingguan harus berupa angka.',
            'setoran_mingguan.min' => 'Setoran mingguan minimal Rp 1.',
        ];
    }

    public function verifikasiPassword(): void
    {
        $this->validate([
            'password' => ['required'],
        ], [
            'password.required' => 'Password wajib diisi.',
        ]);

        if (! Hash::check($this->password, auth()->user()->password)) {
            $this->addError('password', 'Password salah.');

            return;
        }

        $this->password = '';
        $this->showPasswordModal = false;
        $this->resetValidation();
    }

    public function create(): void
    {
        $this->resetForm();
        $this->editingId = null;
        $this->showFormModal = true;
    }

    public function edit(int $id): void
    {
        $nasabah = Nasabah::findOrFail($id);

        $this->resetForm();
        $this->editingId = $nasabah->id;
        $this->nama = $nasabah->nama;
        $this->setoran_mingguan = (string) $nasabah->setoran_mingguan;
        $this->showFormModal = true;
    }

    public function save(KoperasiService $koperasi): void
    {
        $this->validate();

        try {
            if ($this->editingId) {
                $koperasi->editNasabah(
                    Nasabah::findOrFail($this->editingId),
                    $this->nama,
                    (int) $this->setoran_mingguan,
                    auth()->id()
                );
                session()->flash('sukses', 'Data nasabah berhasil diperbarui.');
            } else {
                $koperasi->daftarNasabah($this->nama, (int) $this->setoran_mingguan, auth()->id());
                session()->flash('sukses', 'Nasabah baru berhasil didaftarkan.');
            }
        } catch (InvalidArgumentException $e) {
            $this->addError('nama', $e->getMessage());

            return;
        }

        $this->showFormModal = false;
        $this->resetForm();
        $this->dispatch('transaksi-selesai');
    }

    public function confirmDelete(int $id): void
    {
        $this->deletingId = $id;
        $this->showDeleteModal = true;
    }

    public function delete(KoperasiService $koperasi): void
    {
        $koperasi->hapusNasabah(Nasabah::findOrFail($this->deletingId), auth()->id());

        $this->showDeleteModal = false;
        $this->deletingId = null;
        session()->flash('sukses', 'Nasabah berhasil dihapus.');
        $this->dispatch('transaksi-selesai');
    }

    public function confirmDenda(int $id): void
    {
        $this->dendaId = $id;
        $this->showDendaModal = true;
    }

    public function bayarDenda(KoperasiService $koperasi): void
    {
        $koperasi->bayarDenda(Nasabah::findOrFail($this->dendaId), auth()->id());

        $this->showDendaModal = false;
        $this->dendaId = null;
        session()->flash('sukses', 'Denda berhasil dibayar dan tunggakan dilunasi.');
        $this->dispatch('transaksi-selesai');
    }

    public function showRiwayat(int $id): void
    {
        $this->riwayatNasabahId = $id;
        $this->showRiwayatModal = true;
    }

    public function confirmKoreksi(int $id): void
    {
        $nasabah = Nasabah::findOrFail($id);

        $this->koreksiId = $nasabah->id;
        $this->koreksiMinggu = (string) $nasabah->frekuensi_setor;
        $this->koreksiAlasan = '';
        $this->resetValidation();
        $this->showKoreksiModal = true;
    }

    public function saveKoreksi(KoperasiService $koperasi): void
    {
        $this->validate([
            'koreksiMinggu' => ['required', 'integer', 'min:0', 'max:100000'],
            'koreksiAlasan' => ['nullable', 'string', 'max:255'],
        ], [
            'koreksiMinggu.required' => 'Jumlah minggu wajib diisi.',
            'koreksiMinggu.integer' => 'Jumlah minggu harus berupa angka.',
            'koreksiMinggu.min' => 'Jumlah minggu minimal 0.',
            'koreksiMinggu.max' => 'Jumlah minggu terlalu besar.',
            'koreksiAlasan.max' => 'Alasan maksimal 255 karakter.',
        ]);

        try {
            $koperasi->koreksiFrekuensi(
                Nasabah::findOrFail($this->koreksiId),
                (int) $this->koreksiMinggu,
                trim((string) $this->koreksiAlasan) ?: null,
                auth()->id()
            );
        } catch (InvalidArgumentException $e) {
            $this->addError('koreksiMinggu', $e->getMessage());

            return;
        }

        $this->showKoreksiModal = false;
        $this->koreksiId = null;
        $this->koreksiMinggu = '';
        $this->koreksiAlasan = '';
        session()->flash('sukses', 'Koreksi jumlah minggu berhasil disimpan.');
        $this->dispatch('transaksi-selesai');
    }

    public function openImport(): void
    {
        $this->importFile = null;
        $this->importTanggalMulai = '';
        $this->importKonfirmasiBersih = false;
        $this->importHasil = null;
        $this->resetValidation();
        $this->showImportModal = true;
    }

    public function import(KoperasiService $koperasi): void
    {
        $this->validate([
            'importFile' => ['required', 'file', 'mimes:csv,txt', 'max:4096'],
            'importTanggalMulai' => ['nullable', 'date'],
            'importKonfirmasiBersih' => ['accepted'],
        ], [
            'importFile.required' => 'Pilih file CSV terlebih dahulu.',
            'importFile.mimes' => 'File harus berformat CSV.',
            'importFile.max' => 'Ukuran file maksimal 4 MB.',
            'importTanggalMulai.date' => 'Tanggal mulai tidak valid.',
            'importKonfirmasiBersih.accepted' => 'Centang konfirmasi untuk menghapus data lama dan impor.',
        ]);

        $rows = $this->parseCsvFile($this->importFile->getRealPath());

        if (empty($rows)) {
            $this->addError('importFile', 'File tidak berisi data yang valid (butuh kolom Nama, Setoran, Frekuensi).');

            return;
        }

        $koperasi->bersihkanDataNasabah();

        if ($this->importTanggalMulai) {
            Pengaturan::setValue('tanggal_mulai', $this->importTanggalMulai);
        }

        $hasil = $koperasi->imporNasabah($rows, auth()->id());

        $this->importHasil = $hasil;

        if (empty($hasil['gagal'])) {
            $this->showImportModal = false;
            $this->resetImportState();
            session()->flash('sukses', $hasil['berhasil'] . ' nasabah berhasil diimpor.');
        }

        $this->dispatch('transaksi-selesai');
    }

    private function resetForm(): void
    {
        $this->reset(['nama', 'setoran_mingguan']);
        $this->resetValidation();
    }

    private function resetImportState(): void
    {
        $this->importFile = null;
        $this->importTanggalMulai = '';
        $this->importKonfirmasiBersih = false;
        $this->importHasil = null;
    }

    /**
     * Parse CSV Google Sheets: hanya kolom A-F yang dipakai.
     * A=nama, B=setoran/minggu, C=frekuensi. Kolom E (tanggal) diabaikan.
     *
     * @return array<int, array{nama: string, setoran_mingguan: int, frekuensi_setor: int}>
     */
    private function parseCsvFile(string $path): array
    {
        $handle = fopen($path, 'r');
        if ($handle === false) {
            return [];
        }

        $rows = [];
        $barisHeader = true;

        while (($fields = fgetcsv($handle)) !== false) {
            if ($barisHeader) {
                $barisHeader = false;

                continue;
            }

            if (count($fields) < 2 || trim(implode('', $fields)) === '') {
                continue;
            }

            $rows[] = [
                'nama' => trim((string) ($fields[0] ?? '')),
                'setoran_mingguan' => (int) preg_replace('/[^0-9]/', '', (string) ($fields[1] ?? '')),
                'frekuensi_setor' => (int) preg_replace('/[^0-9]/', '', (string) ($fields[2] ?? '')),
            ];
        }

        fclose($handle);

        return $rows;
    }

    #[On('transaksi-selesai')]
    public function render(KoperasiService $koperasi)
    {
        $daftarNasabah = Nasabah::query()
            ->when($this->search, fn ($q) => $q->where('nama', 'like', '%' . $this->search . '%'))
            ->orderBy('nama')
            ->get()
            ->map(fn (Nasabah $n) => [
                'model' => $n,
                'telat' => $koperasi->telat($n),
                'denda' => $koperasi->denda($n),
                'status' => $koperasi->status($n),
            ]);

        $riwayatNasabah = null;
        $riwayatItems = collect();
        if ($this->showRiwayatModal && $this->riwayatNasabahId) {
            $riwayatNasabah = Nasabah::find($this->riwayatNasabahId);
            $riwayatItems = RiwayatTransaksi::query()
                ->where('nasabah_id', $this->riwayatNasabahId)
                ->latest('created_at')
                ->latest('id')
                ->limit(50)
                ->get();
        }

        $deletingNasabah = $this->showDeleteModal && $this->deletingId
            ? Nasabah::find($this->deletingId)
            : null;

        $dendaNasabah = null;
        $dendaNominal = 0;
        $dendaTelat = 0;
        if ($this->showDendaModal && $this->dendaId) {
            $dendaNasabah = Nasabah::find($this->dendaId);
            if ($dendaNasabah) {
                $dendaNominal = $koperasi->denda($dendaNasabah);
                $dendaTelat = $koperasi->telat($dendaNasabah);
            }
        }

        $koreksiNasabah = $this->showKoreksiModal && $this->koreksiId
            ? Nasabah::find($this->koreksiId)
            : null;

        return view('livewire.nasabah-table', [
            'daftarNasabah' => $daftarNasabah,
            'riwayatNasabah' => $riwayatNasabah,
            'riwayatItems' => $riwayatItems,
            'deletingNasabah' => $deletingNasabah,
            'dendaNasabah' => $dendaNasabah,
            'dendaNominal' => $dendaNominal,
            'dendaTelat' => $dendaTelat,
            'koreksiNasabah' => $koreksiNasabah,
        ]);
    }
}
