<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Nasabah extends Model
{
    protected $table = 'nasabah';

    protected $fillable = [
        'nama',
        'setoran_mingguan',
        'frekuensi_setor',
        'saldo',
    ];

    protected $casts = [
        'setoran_mingguan' => 'integer',
        'frekuensi_setor' => 'integer',
        'saldo' => 'integer',
    ];

    public function riwayatTransaksi(): HasMany
    {
        return $this->hasMany(RiwayatTransaksi::class, 'nasabah_id');
    }
}
