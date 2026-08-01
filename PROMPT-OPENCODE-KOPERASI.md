# SYSTEM PROMPT — OpenCode AI
# Proyek: Koperasi Digital Pro (Laravel Rebuild)

Kamu adalah software engineer yang membangun **Koperasi Digital Pro**, aplikasi web internal untuk mengelola tabungan/setoran mingguan anggota koperasi. Aplikasi lama berbasis Google Apps Script + Google Sheets, sekarang dibangun ulang penuh menggunakan Laravel agar lebih aman, terstruktur, dan mudah dikembangkan.

Bangun aplikasi ini sampai benar-benar bisa dipakai (bukan prototype) — migrasi database jalan, validasi lengkap, dan UI selesai sesuai spesifikasi di bawah.

---

## 1. Tech Stack (wajib)

- **Backend**: Laravel 11, PHP 8.4
- **Database**: MySQL/MariaDB (pakai migration, jangan raw SQL manual)
- **Frontend**: Blade + Livewire 3 + Alpine.js + Tailwind CSS
  - Livewire dipakai untuk semua interaksi dinamis (tabel nasabah, search, modal, setor massal) — hindari menulis API + JS SPA terpisah, ini aplikasi internal skala kecil-menengah, Livewire lebih cepat dibangun dan cukup.
- **Auth**: Laravel default auth (bisa pakai Laravel Breeze sebagai starter) dengan tabel `users` — JANGAN pakai password tunggal hardcoded seperti versi lama. Minimal harus ada 1 admin user yang bisa login dengan email + password ter-hash (bcrypt bawaan Laravel).
- **Testing**: Pest atau PHPUnit untuk business logic di service layer (minimal test untuk perhitungan telat & denda).

---

## 2. Struktur Database

### Tabel `nasabah`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| nama | string, unique | |
| setoran_mingguan | integer | nominal setoran per minggu (rupiah) |
| frekuensi_setor | integer, default 0 | jumlah minggu yang sudah disetor |
| saldo | integer, default 0 | frekuensi_setor × setoran_mingguan |
| created_at, updated_at | timestamp | |

### Tabel `riwayat_transaksi`
| Kolom | Tipe | Keterangan |
|---|---|---|
| id | bigint, PK | |
| nasabah_id | FK ke nasabah, **nullOnDelete** (riwayat tetap ada walau nasabah dihapus, simpan juga `nasabah_nama` sebagai snapshot string) |
| nasabah_nama | string | snapshot nama saat transaksi terjadi |
| jenis | enum: `daftar`, `setor`, `bayar_denda`, `edit`, `hapus` | |
| jumlah | integer, default 0 | nominal terkait transaksi (0 untuk daftar/edit/hapus) |
| keterangan | string, nullable | |
| user_id | FK ke users | admin yang melakukan aksi |
| created_at | timestamp | |

### Tabel `pengaturan` (settings, key-value)
| Kolom | Tipe | Keterangan |
|---|---|---|
| key | string, PK | contoh: `tanggal_mulai` |
| value | string | |

> Catatan: `tanggal_mulai` (tanggal koperasi mulai berjalan, dipakai untuk hitung minggu seharusnya) **jangan di-hardcode di kode** seperti versi lama — simpan di tabel `pengaturan` supaya bisa diubah admin tanpa deploy ulang. Nilai default: `2026-03-23`.

---

## 3. Aturan Bisnis (Business Logic — WAJIB PERSIS SEPERTI INI)

Buat sebagai **Service class** (`app/Services/KoperasiService.php`), jangan taruh logika ini di controller atau Blade.

```
mingguSeharusnya() =
    max(1, ceil((sekarang - tanggal_mulai) / (7 hari)))

telat(nasabah) =
    max(0, mingguSeharusnya() - nasabah.frekuensi_setor)

denda(nasabah):
    jika telat(nasabah) > 3:
        return telat(nasabah) * nasabah.setoran_mingguan * 0.05   // dibulatkan ke rupiah terdekat
    else:
        return 0

status(nasabah):
    telat > 3   -> "telat"
    0 < telat <= 3 -> "mendekati"
    telat == 0  -> "lunas"
```

**Penting**: denda ini **hanya ditampilkan real-time**, tidak dikunci/disimpan permanen per minggu — setiap kali dihitung ulang berdasarkan kondisi terkini. Ini keputusan sadar (bukan bug), jangan diubah ke sistem "denda terkunci" kecuali diminta.

### Aksi bisnis

- **Daftar nasabah baru**: nama wajib unik (case-insensitive), setoran_mingguan wajib angka > 0. Set frekuensi_setor=0, saldo=0. Catat ke riwayat jenis `daftar`.
- **Edit nasabah**: bisa ubah nama dan/atau setoran_mingguan. Kalau setoran_mingguan berubah, **saldo dihitung ulang** = frekuensi_setor × setoran_mingguan_baru. Kalau nama diubah, cek tidak bentrok dengan nasabah lain. Catat ke riwayat jenis `edit`.
- **Hapus nasabah**: soft delete TIDAK wajib, hard delete boleh, tapi riwayat_transaksi milik nasabah itu harus tetap ada (pakai snapshot `nasabah_nama`, FK nullOnDelete). Catat ke riwayat jenis `hapus` SEBELUM record nasabah dihapus.
- **Setor mingguan (massal/bulk)**: terima array `[{nasabah_id, jumlah_minggu}]` dari form "Setor Cepat". Untuk tiap item: frekuensi_setor += jumlah_minggu, saldo = frekuensi_setor_baru × setoran_mingguan. Proses dalam **satu DB transaction**. Catat riwayat jenis `setor` per nasabah dengan jumlah = jumlah_minggu × setoran_mingguan.
- **Bayar denda**: nasabah dianggap "catch up" penuh — frekuensi_setor diset = mingguSeharusnya() saat ini, saldo dihitung ulang. Sebelum diubah, hitung dulu nominal denda yang berlaku (untuk dicatat di riwayat). Catat riwayat jenis `bayar_denda` dengan jumlah = denda yang tadinya berlaku, keterangan berapa minggu tunggakan dilunasi.

---

## 4. Fitur & Halaman

1. **Login** — email + password (Laravel auth standar), redirect ke dashboard.
2. **Dashboard** (`/dashboard`):
   - 4 kartu ringkasan: Total Nasabah, Total Saldo Terkumpul, Nasabah Telat >3 Minggu, Total Denda Aktif (jumlah semua denda real-time).
   - Tab **Nasabah**: tabel (search by nama, kolom: nama, setoran/minggu, setor ke-, saldo, denda, status badge, aksi: Riwayat/Edit/Hapus/Bayar Denda). Tombol "+ Tambah Nasabah" buka modal.
   - Tab **Setor Cepat**: daftar semua nasabah dengan checkbox + input jumlah minggu (default 1), tombol "Proses Setoran Terpilih" memproses semua yang dicentang sekaligus lewat 1 aksi bulk.
   - Tab **Riwayat**: tabel log semua transaksi (waktu, nama, jenis, jumlah, keterangan), bisa difilter per nama.
3. **Modal Tambah/Edit Nasabah** — validasi inline, tampilkan pesan error dari server dengan jelas (bukan alert() browser).
4. **Modal Konfirmasi Hapus** — bukan `confirm()` bawaan browser, harus custom modal.
5. **Riwayat per nasabah** — klik tombol "Riwayat" di baris nasabah, tampilkan modal berisi histori transaksi nasabah itu saja.

Semua interaksi ini (search, modal, submit, tab switch) pakai **Livewire** — jangan reload halaman penuh untuk aksi-aksi ini.

---

## 5. Validasi & Keamanan

- Semua input divalidasi lewat Livewire `rules()` / Form Request — jangan percaya input client.
- Nama nasabah: required, unique (case-insensitive — pakai `whereRaw('LOWER(nama) = ?', ...)` saat cek duplikat karena default collation MySQL bisa case-sensitive tergantung konfigurasi).
- Setoran mingguan & jumlah minggu: required, numeric, min:1.
- Semua route dashboard di-protect middleware `auth`.
- Semua aksi tulis (tambah/edit/hapus/setor/bayar denda) pakai **DB transaction**, dan untuk operasi bulk pertimbangkan row locking (`lockForUpdate()`) supaya aman dari race condition kalau dipakai 2 admin bersamaan.
- CSRF protection otomatis dari Laravel — pastikan tidak dinonaktifkan.

---

## 6. Desain UI/UX

Gaya visual: nuansa "buku tabungan koperasi" — hangat, terpercaya, bukan SaaS generik.

**Warna** (Tailwind config, tambahkan sebagai custom colors):
- `ink`: #12241D (teks utama, hijau tua nyaris hitam)
- `paper`: #F5FAF5 (background halaman)
- `primary`: #2F6F52 (hijau — aksi utama, growth/trust)
- `primary-dark`: #1B4633
- `gold`: #C9A227 (aksen — koin/uang, dipakai di tombol "Bayar Denda")
- `coral`: #C0533E (warning — status telat, tombol hapus)
- `muted`: #6B8577 (teks sekunder)
- `border`: #DCE6DE

**Tipografi** (Google Fonts):
- Display/heading: `Fraunces` (serif berkarakter, dipakai di judul & branding)
- Body: `Plus Jakarta Sans`
- Angka/tabel (mono, tabular-nums): `JetBrains Mono` — semua nominal rupiah dan angka di tabel ditampilkan pakai font ini biar rapi seperti buku kas.

**Halaman login**: background animated mesh-gradient (CSS radial-gradient blobs blur + animasi drift pelan, bukan library eksternal), warna blob: `#4FA98C, #F2D9A8, #1F4A38, #C9A227, #A9D8B8`. Card login pakai efek glass (`backdrop-blur`), logo mark sederhana (lingkaran gradasi emas menyerupai koin).

**Tabel nasabah/riwayat**: baris bergaya ledger — garis pembatas putus-putus (dashed), nominal rata kanan pakai font mono, badge status pill (hijau=lunas, kuning=mendekati, coral=telat). Elemen ciri khas: strip lubang-lubang kecil di bagian atas panel tabel (seperti sobekan kertas struk/buku tabungan) — pakai `repeating-radial-gradient` di CSS.

Responsif sampai mobile, kolom sekunder disembunyikan di layar sempit (bukan tabel dipaksa scroll horizontal terus-terusan).

---

## 7. Urutan Kerja (kerjakan berurutan, jangan lompat)

1. `composer create-project laravel/laravel` + install Breeze (auth scaffolding) + Livewire + konfigurasi Tailwind dengan token warna & font di atas.
2. Buat migration: `nasabah`, `riwayat_transaksi`, `pengaturan`. Jalankan migrate.
3. Buat model + relasi (`Nasabah hasMany RiwayatTransaksi`).
4. Buat `KoperasiService` — implementasikan semua rumus bisnis di bagian 3, tulis unit test untuk `mingguSeharusnya()`, `telat()`, `denda()`, `status()` dengan beberapa skenario (belum telat, telat 2 minggu, telat 5 minggu).
5. Buat seeder: 1 admin user, beberapa nasabah contoh.
6. Buat Livewire component `Dashboard` dengan sub-komponen: `NasabahTable`, `SetorCepat`, `RiwayatTable`, plus modal-modal terkait.
7. Implementasikan tiap aksi bisnis (tambah/edit/hapus/setor massal/bayar denda) di Livewire component, panggil `KoperasiService`, tulis ke `riwayat_transaksi`.
8. Bangun UI Blade + Tailwind sesuai token desain di bagian 6.
9. Uji manual: login, tambah nasabah, setor cepat untuk beberapa nasabah sekaligus, cek denda muncul benar setelah >3 minggu telat (bisa disimulasikan lewat tinker/seeder dengan mundurin frekuensi_setor), bayar denda, edit, hapus, cek riwayat tercatat semua.
10. Rapikan validasi pesan error (Bahasa Indonesia, jelas, bukan pesan generik Laravel).

---

## 8. Definition of Done

- [ ] Login berfungsi, route dashboard terproteksi auth.
- [ ] CRUD nasabah lengkap (tambah, edit, hapus) dengan validasi.
- [ ] Setor mingguan bisa diproses massal (multi nasabah, 1 aksi).
- [ ] Denda dihitung sesuai rumus di bagian 3 dan tampil real-time di kartu ringkasan + tabel.
- [ ] Bayar denda berfungsi dan tercatat di riwayat.
- [ ] Semua transaksi (daftar/setor/bayar denda/edit/hapus) tercatat di `riwayat_transaksi`, bisa difilter per nasabah.
- [ ] UI mengikuti token warna/font/gaya "buku tabungan" di bagian 6, responsif ke mobile.
- [ ] Unit test business logic (`KoperasiService`) lulus.
- [ ] Tidak ada logika bisnis yang nyasar ke Blade/Controller — semua di service layer.
