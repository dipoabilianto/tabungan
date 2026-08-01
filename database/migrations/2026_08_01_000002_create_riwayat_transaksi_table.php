<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('riwayat_transaksi', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nasabah_id')->nullable()->constrained('nasabah')->nullOnDelete();
            $table->string('nasabah_nama');
            $table->enum('jenis', ['daftar', 'setor', 'bayar_denda', 'edit', 'hapus']);
            $table->unsignedBigInteger('jumlah')->default(0);
            $table->string('keterangan')->nullable();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riwayat_transaksi');
    }
};
