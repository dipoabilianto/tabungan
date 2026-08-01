<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RiwayatTransaksi extends Model
{
    protected $table = 'riwayat_transaksi';

    public const UPDATED_AT = null;

    protected $fillable = [
        'nasabah_id',
        'nasabah_nama',
        'jenis',
        'jumlah',
        'keterangan',
        'user_id',
    ];

    protected $casts = [
        'jumlah' => 'integer',
        'created_at' => 'datetime',
    ];

    public function nasabah(): BelongsTo
    {
        return $this->belongsTo(Nasabah::class, 'nasabah_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
