<?php

namespace App\Livewire;

use App\Models\RiwayatTransaksi;
use App\Services\KoperasiService;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Laporan extends Component
{
    use WithPagination;

    public string $periode = 'hari';

    public function updatedPeriode(): void
    {
        $this->resetPage();
    }

    public function render(KoperasiService $koperasi)
    {
        $dari = match ($this->periode) {
            'minggu' => now()->subDays(7)->startOfDay(),
            'bulan' => now()->subDays(30)->startOfDay(),
            default => now()->startOfDay(),
        };

        $transaksi = RiwayatTransaksi::query()
            ->where('created_at', '>=', $dari)
            ->whereIn('jenis', ['setor', 'bayar_denda'])
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.laporan', [
            'dari' => $dari,
            'ringkasan' => $koperasi->ringkasanSetoran($dari),
            'transaksi' => $transaksi,
        ]);
    }
}
