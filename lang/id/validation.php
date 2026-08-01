<?php

return [
    'required' => ':attribute wajib diisi.',
    'string' => ':attribute harus berupa teks.',
    'email' => ':attribute harus berupa alamat email yang valid.',
    'max' => [
        'string' => ':attribute maksimal :max karakter.',
    ],
    'min' => [
        'numeric' => ':attribute minimal :min.',
        'string' => ':attribute minimal :min karakter.',
    ],
    'numeric' => ':attribute harus berupa angka.',
    'integer' => ':attribute harus berupa bilangan bulat.',
    'unique' => ':attribute sudah digunakan.',
    'confirmed' => 'Konfirmasi :attribute tidak cocok.',
    'current_password' => 'Kata sandi salah.',

    'attributes' => [
        'email' => 'Email',
        'password' => 'Kata sandi',
        'nama' => 'Nama nasabah',
        'setoran_mingguan' => 'Setoran mingguan',
        'jumlah_minggu' => 'Jumlah minggu',
    ],
];
