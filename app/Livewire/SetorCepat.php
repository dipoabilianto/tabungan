<?php

namespace App\Livewire;

use App\Models\Nasabah;
use App\Services\KoperasiService;
use InvalidArgumentException;
use Livewire\Component;

class SetorCepat extends Component
{
    /** @var array<int, int|string> nasabah_id => jumlah minggu (input per baris) */
    public array $minggu = [];

    /** @var array<int, int|string> nasabah_id => jumlah minggu di keranjang */
    public array $keranjang = [];

    public string $search = '';

    public function tambah(int $nasabahId, KoperasiService $koperasi): void
    {
        $nasabah = Nasabah::find($nasabahId);

        if (! $nasabah) {
            return;
        }

        if ($this->terkunciKoreksi($nasabah, $koperasi)) {
            $this->addError('telat.' . $nasabahId, 'Nasabah telat lebih dari 3 minggu, hanya bisa dikoreksi lewat tab Nasabah.');

            return;
        }

        $jumlahMinggu = $this->jumlahMingguValid($this->minggu[$nasabahId] ?? null);

        if ($jumlahMinggu < 1) {
            $this->addError('minggu.' . $nasabahId, 'Jumlah minggu minimal 1.');

            return;
        }

        $this->keranjang[$nasabahId] = $jumlahMinggu;
    }

    public function hapus(int $nasabahId): void
    {
        unset($this->keranjang[$nasabahId]);
    }

    public function setorSatu(int $nasabahId, KoperasiService $koperasi): void
    {
        $nasabah = Nasabah::find($nasabahId);

        if (! $nasabah) {
            return;
        }

        if ($this->terkunciKoreksi($nasabah, $koperasi)) {
            $this->addError('telat.' . $nasabahId, 'Nasabah telat lebih dari 3 minggu, hanya bisa dikoreksi lewat tab Nasabah.');

            return;
        }

        $jumlahMinggu = $this->jumlahMingguValid($this->minggu[$nasabahId] ?? null);

        if ($jumlahMinggu < 1) {
            $this->addError('minggu.' . $nasabahId, 'Jumlah minggu minimal 1.');

            return;
        }

        try {
            $koperasi->setorSatu($nasabah, $jumlahMinggu, auth()->id());
        } catch (InvalidArgumentException $e) {
            $this->addError('minggu.' . $nasabahId, $e->getMessage());

            return;
        }

        unset($this->minggu[$nasabahId]);
        session()->flash('sukses', 'Setoran ' . $nasabah->nama . ' (' . $jumlahMinggu . ' minggu) berhasil diproses.');
        $this->dispatch('transaksi-selesai');
    }

    public function proses(KoperasiService $koperasi): void
    {
        $items = [];
        foreach ($this->keranjang as $nasabahId => $jumlahMinggu) {
            $jumlahMinggu = (int) $jumlahMinggu;

            if ($jumlahMinggu < 1) {
                $this->addError('keranjang.' . $nasabahId, 'Jumlah minggu minimal 1.');

                return;
            }

            $nasabah = Nasabah::find($nasabahId);

            if ($nasabah && $this->terkunciKoreksi($nasabah, $koperasi)) {
                $this->addError('keranjang', $nasabah->nama . ' telat lebih dari 3 minggu, keluarkan dari keranjang. Hanya bisa dikoreksi lewat tab Nasabah.');

                return;
            }

            $items[] = ['nasabah_id' => (int) $nasabahId, 'jumlah_minggu' => $jumlahMinggu];
        }

        if (empty($items)) {
            $this->addError('keranjang', 'Keranjang masih kosong. Tambahkan nasabah dahulu.');

            return;
        }

        try {
            $koperasi->setorMassal($items, auth()->id());
        } catch (InvalidArgumentException $e) {
            $this->addError('keranjang', $e->getMessage());

            return;
        }

        $this->keranjang = [];
        $this->resetValidation();
        session()->flash('sukses', count($items) . ' setoran berhasil diproses.');
        $this->dispatch('transaksi-selesai');
    }

    public function render(KoperasiService $koperasi)
    {
        $daftarNasabah = Nasabah::query()
            ->when($this->search, fn ($q) => $q->where('nama', 'like', '%' . $this->search . '%'))
            ->orderBy('nama')
            ->get()
            ->map(fn (Nasabah $n) => [
                'model' => $n,
                'telat' => $koperasi->telat($n),
                'status' => $koperasi->status($n),
            ]);

        $keranjangItems = collect($this->keranjang)
            ->map(fn ($minggu, $id) => [
                'model' => Nasabah::find($id),
                'minggu' => $minggu,
            ])
            ->filter(fn ($item) => $item['model'] !== null)
            ->values();

        $totalKeranjang = $keranjangItems->sum(fn ($item) => $item['model']->setoran_mingguan * (int) $item['minggu']);

        return view('livewire.setor-cepat', [
            'daftarNasabah' => $daftarNasabah,
            'keranjangItems' => $keranjangItems,
            'totalKeranjang' => $totalKeranjang,
        ]);
    }

    private function jumlahMingguValid(mixed $raw): int
    {
        return ($raw === null || $raw === '') ? 1 : (int) $raw;
    }

    private function terkunciKoreksi(Nasabah $nasabah, KoperasiService $koperasi): bool
    {
        return $koperasi->telat($nasabah) > 3;
    }
}
