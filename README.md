# SIMRS — Sistem Informasi Manajemen Rumah Sakit

Aplikasi web SIMRS berbasis **PHP 8 (native, tanpa framework)**, **MySQL/MariaDB**, dan **AdminLTE 4** (Bootstrap 5).

## Fitur

| Modul | Keterangan |
|---|---|
| Dashboard | Statistik pasien, kunjungan, antrian, tagihan, grafik kunjungan 7 hari, stok obat menipis |
| Data Pasien | CRUD, No. RM otomatis, pencarian, riwayat rekam medis |
| Data Dokter | CRUD, spesialisasi, jadwal praktek, status aktif |
| Data Poli | CRUD poli/departemen |
| Data Obat | CRUD, stok, harga, kadaluarsa, indikator stok menipis |
| Pendaftaran | Registrasi kunjungan, no. registrasi + nomor antrian otomatis per poli per hari |
| Papan Antrian | Papan operator 4 kolom (menunggu → dipanggil → diperiksa → selesai) + **display antrian publik** (`/display-antrian.php`) untuk layar TV, auto-refresh 15 detik |
| Rekam Medis | Tanda vital, anamnesa, diagnosis, tindakan, resep obat (stok berkurang otomatis, transaksi DB, tercatat di kartu stok) |
| Billing / Kasir | Tagihan otomatis dari biaya konsultasi + tindakan + resep, pembayaran (Tunai/Transfer/BPJS/Asuransi), cetak invoice berkop RS |
| Laporan | Kunjungan & pendapatan per hari, rekap per poli, obat terlaris, filter rentang tanggal, ekspor CSV, cetak |
| Kartu Stok | Mutasi stok masuk (form) dan keluar (otomatis dari resep), riwayat per obat beserta petugas |
| Manajemen User | CRUD user dengan role admin/petugas/dokter (khusus admin) |
| Pengaturan RS | Ubah nama, alamat, dan telepon rumah sakit (khusus admin); tampil di sidebar, login, judul, footer, dan invoice |
| Mode Siang/Malam | Saklar tema terang/gelap di header (default: siang), tersimpan per perangkat |

## Akun Demo

| Username | Password | Role |
|---|---|---|
| `admin` | `admin123` | Admin |
| `petugas` | `petugas123` | Petugas |
| `dokter` | `dokter123` | Dokter |

## Instalasi

```bash
# 1. Buat database dan user MySQL
sudo mysql -e "CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs'@'localhost' IDENTIFIED BY 'simrs123';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs'@'localhost'; FLUSH PRIVILEGES;"

# 2. Import skema + data contoh
mysql -u simrs -psimrs123 simrs < database/simrs.sql

# 3. Sesuaikan kredensial di config/database.php bila perlu

# 4. Jalankan server pengembangan
php -S 0.0.0.0:12000 -t /path/ke/proyek
```

Buka `http://localhost:12000` lalu login.

## Struktur Direktori

```
├── index.php            # Front controller (routing ?page=)
├── login.php / logout.php
├── config/database.php  # Koneksi PDO
├── includes/            # auth, helpers, layout (header/sidebar/footer)
├── modules/             # dashboard, pasien, dokter, poli, obat,
│                        # pendaftaran, rekam-medis, billing, users, profil
├── assets/              # AdminLTE 4 + vendor (lokal, tanpa CDN)
└── database/simrs.sql   # Skema + seed data
```

## Catatan Keamanan

- Semua query memakai PDO prepared statement (anti SQL injection)
- Semua output di-escape dengan `htmlspecialchars` (anti XSS)
- Form dilindungi token CSRF
- Password disimpan dengan `password_hash` (bcrypt)
- Resep obat dijalankan dalam transaksi dengan `SELECT ... FOR UPDATE` agar stok aman dari race condition
