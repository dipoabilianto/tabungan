<?php

namespace App\Livewire;

use App\Models\RiwayatTransaksi;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithPagination;

class RiwayatTable extends Component
{
    use WithPagination;

    public string $filterNama = '';

    public function updatingFilterNama(): void
    {
        $this->resetPage();
    }

    #[On('transaksi-selesai')]
    public function render()
    {
        $riwayat = RiwayatTransaksi::query()
            ->when($this->filterNama, fn ($q) => $q->where('nasabah_nama', 'like', '%' . $this->filterNama . '%'))
            ->latest('created_at')
            ->latest('id')
            ->paginate(15);

        return view('livewire.riwayat-table', [
            'riwayat' => $riwayat,
        ]);
    }
}
