-- ============================================================
-- SIMRS - Sistem Informasi Manajemen Rumah Sakit
-- Database: simrs (MySQL / MariaDB)
-- ============================================================

SET NAMES utf8mb4;

CREATE TABLE IF NOT EXISTS users (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username   VARCHAR(50)  NOT NULL UNIQUE,
    password   VARCHAR(255) NOT NULL,
    nama       VARCHAR(100) NOT NULL,
    role       ENUM('admin','petugas','dokter') NOT NULL DEFAULT 'petugas',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS poli (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode       VARCHAR(10)  NOT NULL UNIQUE,
    nama       VARCHAR(100) NOT NULL,
    keterangan VARCHAR(255) NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pasien (
    id              INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_rm           VARCHAR(20)  NOT NULL UNIQUE,
    nik             VARCHAR(20)  NULL,
    nama            VARCHAR(100) NOT NULL,
    jenis_kelamin   ENUM('L','P') NOT NULL,
    tempat_lahir    VARCHAR(50)  NULL,
    tanggal_lahir   DATE         NULL,
    golongan_darah  ENUM('A','B','AB','O','-') DEFAULT '-',
    alamat          TEXT         NULL,
    telepon         VARCHAR(20)  NULL,
    pekerjaan       VARCHAR(50)  NULL,
    penjamin        ENUM('Umum','BPJS','Asuransi') NOT NULL DEFAULT 'Umum',
    created_at      TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_pasien_nama (nama)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS dokter (
    id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nip          VARCHAR(25)  NULL,
    nama         VARCHAR(100) NOT NULL,
    spesialisasi VARCHAR(100) NULL,
    poli_id      INT UNSIGNED NULL,
    telepon      VARCHAR(20)  NULL,
    jadwal       VARCHAR(100) NULL COMMENT 'Contoh: Senin-Jumat 08:00-14:00',
    aktif        TINYINT(1) NOT NULL DEFAULT 1,
    CONSTRAINT fk_dokter_poli FOREIGN KEY (poli_id) REFERENCES poli(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS obat (
    id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    kode       VARCHAR(15)  NOT NULL UNIQUE,
    nama       VARCHAR(100) NOT NULL,
    satuan     VARCHAR(20)  NOT NULL DEFAULT 'tablet',
    stok       INT NOT NULL DEFAULT 0,
    harga      DECIMAL(12,2) NOT NULL DEFAULT 0,
    kadaluarsa DATE NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pendaftaran (
    id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_registrasi VARCHAR(20) NOT NULL UNIQUE,
    pasien_id     INT UNSIGNED NOT NULL,
    poli_id       INT UNSIGNED NOT NULL,
    dokter_id     INT UNSIGNED NULL,
    tanggal       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    keluhan       TEXT NULL,
    status        ENUM('menunggu','diperiksa','selesai','batal') NOT NULL DEFAULT 'menunggu',
    CONSTRAINT fk_daftar_pasien FOREIGN KEY (pasien_id) REFERENCES pasien(id),
    CONSTRAINT fk_daftar_poli   FOREIGN KEY (poli_id)   REFERENCES poli(id),
    CONSTRAINT fk_daftar_dokter FOREIGN KEY (dokter_id) REFERENCES dokter(id) ON DELETE SET NULL,
    INDEX idx_daftar_tanggal (tanggal)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rekam_medis (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    pendaftaran_id    INT UNSIGNED NOT NULL UNIQUE,
    pasien_id         INT UNSIGNED NOT NULL,
    dokter_id         INT UNSIGNED NULL,
    tanggal           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    anamnesa          TEXT NULL,
    tekanan_darah     VARCHAR(10) NULL COMMENT 'Contoh: 120/80',
    suhu              DECIMAL(4,1) NULL,
    berat_badan       DECIMAL(5,1) NULL,
    tinggi_badan      DECIMAL(5,1) NULL,
    pemeriksaan_fisik TEXT NULL,
    diagnosis         TEXT NULL,
    tindakan          TEXT NULL,
    biaya_tindakan    DECIMAL(12,2) NOT NULL DEFAULT 0,
    catatan           TEXT NULL,
    CONSTRAINT fk_rm_pendaftaran FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id),
    CONSTRAINT fk_rm_pasien      FOREIGN KEY (pasien_id)      REFERENCES pasien(id),
    CONSTRAINT fk_rm_dokter      FOREIGN KEY (dokter_id)      REFERENCES dokter(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS resep (
    id             INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    rekam_medis_id INT UNSIGNED NOT NULL,
    obat_id        INT UNSIGNED NOT NULL,
    jumlah         INT NOT NULL,
    aturan_pakai   VARCHAR(100) NULL,
    CONSTRAINT fk_resep_rm   FOREIGN KEY (rekam_medis_id) REFERENCES rekam_medis(id) ON DELETE CASCADE,
    CONSTRAINT fk_resep_obat FOREIGN KEY (obat_id)        REFERENCES obat(id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS tagihan (
    id                INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    no_invoice        VARCHAR(20) NOT NULL UNIQUE,
    pendaftaran_id    INT UNSIGNED NOT NULL UNIQUE,
    tanggal           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    biaya_konsultasi  DECIMAL(12,2) NOT NULL DEFAULT 0,
    biaya_tindakan    DECIMAL(12,2) NOT NULL DEFAULT 0,
    biaya_obat        DECIMAL(12,2) NOT NULL DEFAULT 0,
    total             DECIMAL(12,2) NOT NULL DEFAULT 0,
    metode_pembayaran ENUM('Tunai','Transfer','BPJS','Asuransi') NULL,
    status            ENUM('belum','lunas') NOT NULL DEFAULT 'belum',
    dibayar_pada      DATETIME NULL,
    CONSTRAINT fk_tagihan_daftar FOREIGN KEY (pendaftaran_id) REFERENCES pendaftaran(id)
) ENGINE=InnoDB;

-- ============================ SEED ============================

-- password default: admin123 / petugas123 / dokter123
INSERT INTO users (username, password, nama, role) VALUES
('admin',   '$2y$12$gW2u2P7NbzPH8v0L7BGeSubcs.sJkXZhVLhEigieMRpCt26uf7jDq', 'Administrator', 'admin'),
('petugas', '$2y$12$YN105xMTN.jSfAUBsbJxi.ToL7m44dlEW321/QqPZZKh48Gdyrvcu', 'Siti Petugas', 'petugas'),
('dokter',  '$2y$12$xElh6YpF.yYOmx90Smyg1eSkGAcKMaXyNkMNb23nGD0V/sTmAnryW', 'dr. Budi Santoso', 'dokter')
ON DUPLICATE KEY UPDATE username = username;

INSERT INTO poli (kode, nama, keterangan) VALUES
('UMU', 'Poli Umum',          'Pelayanan kesehatan umum'),
('GIG', 'Poli Gigi',          'Kesehatan gigi dan mulut'),
('ANA', 'Poli Anak',          'Kesehatan anak dan imunisasi'),
('KAN', 'Poli Kandungan',     'Kebidanan dan kandungan'),
('DAL', 'Poli Penyakit Dalam','Penyakit dalam'),
('MATA','Poli Mata',          'Kesehatan mata')
ON DUPLICATE KEY UPDATE nama = VALUES(nama);

INSERT INTO dokter (nip, nama, spesialisasi, poli_id, telepon, jadwal) VALUES
('198501012010011001', 'dr. Budi Santoso',        'Dokter Umum',        1, '081234567801', 'Senin-Jumat 08:00-14:00'),
('198703152012012002', 'drg. Ratna Wijaya',       'Dokter Gigi',        2, '081234567802', 'Senin-Kamis 09:00-15:00'),
('199002202015011003', 'dr. Ani Kusuma, Sp.PD',   'Spesialis Penyakit Dalam', 5, '081234567803', 'Selasa-Sabtu 08:00-13:00'),
('198811102014012004', 'dr. Dewi Lestari, Sp.OG', 'Spesialis Kandungan', 4, '081234567804', 'Senin-Jumat 10:00-16:00'),
('199505052020011005', 'dr. Andi Pratama, Sp.A',  'Spesialis Anak',     3, '081234567805', 'Senin-Sabtu 08:00-12:00');

INSERT INTO pasien (no_rm, nik, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, golongan_darah, alamat, telepon, pekerjaan, penjamin) VALUES
('RM-000001', '3201234567890001', 'Ahmad Hidayat',    'L', 'Jakarta',  '1985-05-12', 'O',  'Jl. Merdeka No. 10, Jakarta',   '081311122233', 'Karyawan Swasta', 'BPJS'),
('RM-000002', '3201234567890002', 'Sri Wahyuni',      'P', 'Bandung',  '1990-08-25', 'A',  'Jl. Sudirman No. 5, Bandung',   '081322233344', 'Ibu Rumah Tangga','Umum'),
('RM-000003', '3201234567890003', 'Bambang Riyanto',  'L', 'Surabaya', '1978-02-14', 'B',  'Jl. Pahlawan No. 3, Surabaya',  '081333344455', 'Wiraswasta',      'Asuransi'),
('RM-000004', '3201234567890004', 'Fitri Rahmawati',  'P', 'Semarang', '1995-11-30', 'AB', 'Jl. Gajah Mada No. 8, Semarang','081344455566', 'Mahasiswa',       'Umum'),
('RM-000005', '3201234567890005', 'Joko Prasetyo',    'L', 'Yogyakarta','2000-07-04', 'O', 'Jl. Malioboro No. 1, Yogyakarta','081355566677', 'Pelajar',        'BPJS');

INSERT INTO obat (kode, nama, satuan, stok, harga, kadaluarsa) VALUES
('OBT-001', 'Paracetamol 500mg',       'tablet', 500,  1500,  '2027-06-30'),
('OBT-002', 'Amoxicillin 500mg',       'kapsul', 300,  3500,  '2027-03-31'),
('OBT-003', 'Antasida DOEN',           'tablet', 200,  2000,  '2026-12-31'),
('OBT-004', 'CTM 4mg',                 'tablet', 150,  1200,  '2027-01-31'),
('OBT-005', 'Omeprazole 20mg',         'kapsul', 180,  5000,  '2027-08-31'),
('OBT-006', 'Vitamin C 500mg',         'tablet', 400,  1000,  '2027-05-31'),
('OBT-007', 'Betadine 10ml',           'botol',  60,   12000, '2027-09-30'),
('OBT-008', 'Salbutamol Inhaler',      'inhaler',40,   55000, '2026-11-30');

INSERT INTO pendaftaran (no_registrasi, pasien_id, poli_id, dokter_id, tanggal, keluhan, status) VALUES
('REG-20260820-0001', 1, 1, 1, NOW() - INTERVAL 2 DAY, 'Demam dan batuk sejak 3 hari', 'selesai'),
('REG-20260820-0002', 2, 3, 5, NOW() - INTERVAL 1 DAY, 'Anak tidak nafsu makan',        'selesai'),
('REG-20260820-0003', 3, 5, 3, NOW(),                  'Nyeri ulu hati berulang',       'diperiksa'),
('REG-20260820-0004', 4, 1, 1, NOW(),                  'Sakit kepala dan mual',         'menunggu'),
('REG-20260820-0005', 5, 2, 2, NOW(),                  'Gigi berlubang nyeri',          'menunggu');

