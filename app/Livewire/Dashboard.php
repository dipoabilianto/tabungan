<?php

namespace App\Livewire;

use App\Services\KoperasiService;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;
use Livewire\Component;

#[Layout('layouts.app')]
class Dashboard extends Component
{
    public string $tab = 'nasabah';

    #[On('transaksi-selesai')]
    public function refreshRingkasan(): void
    {
        // Re-render untuk memuat ulang kartu ringkasan
    }

    public function render(KoperasiService $koperasi)
    {
        return view('livewire.dashboard', [
            'ringkasan' => $koperasi->ringkasan(),
            'mingguSeharusnya' => $koperasi->mingguSeharusnya(),
        ]);
    }
}
