<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('riwayat_transaksi', function (Blueprint $table) {
            $table->enum('jenis', ['daftar', 'setor', 'bayar_denda', 'edit', 'hapus', 'koreksi'])->change();
        });
    }

    public function down(): void
    {
        Schema::table('riwayat_transaksi', function (Blueprint $table) {
            $table->enum('jenis', ['daftar', 'setor', 'bayar_denda', 'edit', 'hapus'])->change();
        });
    }
};
