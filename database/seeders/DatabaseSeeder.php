<?php

namespace Database\Seeders;

use App\Models\Nasabah;
use App\Models\Pengaturan;
use App\Models\User;
use App\Services\KoperasiService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Pengaturan awal — tanggal koperasi mulai berjalan
        Pengaturan::setValue('tanggal_mulai', KoperasiService::DEFAULT_TANGGAL_MULAI);

        // Admin user (login: email + password ter-hash bcrypt)
        User::updateOrCreate(
            ['email' => 'admin@koperasi.test'],
            [
                'name' => 'Admin Koperasi',
                'password' => Hash::make('password'),
            ]
        );

        // Nasabah contoh dengan kondisi beragam (lunas / mendekati / telat)
        $minggu = (new KoperasiService())->mingguSeharusnya();

        $contoh = [
            ['nama' => 'Siti Rahayu', 'setoran_mingguan' => 10000, 'frekuensi_setor' => $minggu],
            ['nama' => 'Budi Santoso', 'setoran_mingguan' => 20000, 'frekuensi_setor' => max(0, $minggu - 2)],
            ['nama' => 'Joko Prasetyo', 'setoran_mingguan' => 15000, 'frekuensi_setor' => max(0, $minggu - 6)],
            ['nama' => 'Ani Wijaya', 'setoran_mingguan' => 25000, 'frekuensi_setor' => max(0, $minggu - 1)],
            ['nama' => 'Rina Marlina', 'setoran_mingguan' => 5000, 'frekuensi_setor' => max(0, $minggu - 10)],
        ];

        foreach ($contoh as $data) {
            Nasabah::updateOrCreate(
                ['nama' => $data['nama']],
                [
                    'setoran_mingguan' => $data['setoran_mingguan'],
                    'frekuensi_setor' => $data['frekuensi_setor'],
                    'saldo' => $data['frekuensi_setor'] * $data['setoran_mingguan'],
                ]
            );
        }
    }
}
