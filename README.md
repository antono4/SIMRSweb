# SIMRS — Sistem Informasi Manajemen Rumah Sakit

Aplikasi web SIMRS berbasis **PHP 8 (native, tanpa framework)**, **MySQL/MariaDB**, dan **AdminLTE 4** (Bootstrap 5).

## Fitur

| Modul | Keterangan |
|---|---|
| Dashboard | Statistik pasien, kunjungan, antrian, tagihan, grafik kunjungan 7 hari, stok obat menipis |
| Data Pasien | CRUD, No. RM otomatis, pencarian, riwayat rekam medis |
| Data Dokter | CRUD, spesialisasi, jadwal praktek, status aktif |
| Data Poli | CRUD poli/departemen |
| Data Obat | CRUD, stok, harga, kadaluarsa (badge peringatan 90 hari), indikator stok menipis |
| Tarif Tindakan | Master kode/nama/tarif tindakan medis; dipilih di rekam medis dan biaya terisi otomatis |
| Pendaftaran | Registrasi kunjungan, no. registrasi + nomor antrian otomatis per poli per hari |
| Janji Temu | Penjadwalan per poli/dokter; konfirmasi "Hadir" otomatis membuat pendaftaran + nomor antrian |
| Papan Antrian | Papan operator 4 kolom + **display antrian publik** (`/display-antrian.php`) untuk layar TV: auto-refresh 15 detik, jam berjalan, **suara panggilan** (text-to-speech Indonesia) |
| Rekam Medis | Tanda vital, anamnesa, diagnosis, tindakan (tarif otomatis), resep obat (stok berkurang otomatis, tercatat di kartu stok) |
| Surat Keterangan | Surat sakit (istirahat) & rujukan dari rekam medis, nomor surat otomatis, halaman cetak A5 berkop RS, arsip surat |
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

### Di XAMPP (Windows/Linux)

```bash
# 1. Clone/copy ke htdocs (nama folder bebas, base_url otomatis)
cp -r . /opt/lampp/htdocs/SIMRSweb

# 2. Buat database dan user MySQL (via phpMyAdmin atau terminal)
sudo /opt/lampp/bin/mysql -u root -e "CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs'@'localhost' IDENTIFIED BY 'simrs123';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs'@'localhost'; FLUSH PRIVILEGES;"

# 3. Import skema + data contoh
sudo /opt/lampp/bin/mysql -u simrs -psimrs123 simrs < database/simrs.sql

# 4. Edit config/database.php: ubah DB_USER='root', DB_PASS='' (kosong) untuk XAMPP default

# 5. Akses aplikasi
http://localhost/SIMRSweb/login.php
```

### Di Linux (Apache/Nginx + PHP-FPM)

```bash
# 1. Clone/copy ke document root
git clone https://github.com/antono4/SIMRSweb.git /var/www/html/simrs

# 2. Buat database
sudo mysql -e "CREATE DATABASE simrs CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'simrs'@'localhost' IDENTIFIED BY 'simrs123';
GRANT ALL PRIVILEGES ON simrs.* TO 'simrs'@'localhost'; FLUSH PRIVILEGES;"

# 3. Import skema
mysql -u simrs -psimrs123 simrs < database/simrs.sql

# 4. Akses
http://localhost/simrs/login.php
```

### Di PHP Built-in Server (development)

```bash
# 1. Clone
git clone https://github.com/antono4/SIMRSweb.git
cd SIMRSweb

# 2. Setup database (lihat atas)

# 3. Jalankan server
php -S 0.0.0.0:8000 -t /path/ke/SIMRSweb

# 4. Akses
http://localhost:8000/login.php
```

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
