# Panduan Migrasi SIMRS ke CodeIgniter 2

## Struktur yang Sudah Dibuat

```
application/
├── controllers/
│   ├── Auth.php          # Login/logout (sudah dibuat)
│   └── Dashboard.php     # Dashboard (sudah dibuat)
├── views/
│   ├── templates/
│   │   ├── header.php    # Layout header (sudah dibuat)
│   │   ├── sidebar.php   # Layout sidebar (sudah dibuat)
│   │   └── footer.php    # Layout footer (sudah dibuat)
│   ├── auth/
│   │   └── login.php     # Halaman login (sudah dibuat)
│   └── dashboard/
│       └── index.php     # Dashboard view (sudah dibuat)
├── models/               # Untuk model database (perlu dibuat)
└── config/
    ├── config.php        # Sudah dikonfigurasi untuk XAMPP
    └── database.php      # Sudah dikonfigurasi untuk MySQL
```

## Yang Perlu Dilakukan untuk Migrasi Penuh

### 1. Install CodeIgniter 2

```bash
# Download CI2 dari https://github.com/bcit-ci/CodeIgniter/archive/2.2.6.zip
# Extract ke workspace
# Konfigurasi application/config/config.php dan database.php
```

### 2. Struktur MVC untuk Setiap Modul

Untuk setiap modul (Pasien, Dokter, Poli, Obat, Tindakan, Pendaftaran, Janji Temu, Antrian, Rekam Medis, Surat, Billing, Laporan, Users, Pengaturan):

**Controller** (`application/controllers/NamaModul.php`):
```php
<?php if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class NamaModul extends CI_Controller {
    public function __construct() {
        parent::__construct();
        $this->load->database();
        $this->load->helper('url');
        $this->load->library('session');
        $this->check_login();
    }

    public function index() {
        // Query data menggunakan $this->db
        $data['records'] = $this->db->get('nama_tabel')->result_array();
        $this->load->view('templates/header', $data);
        $this->load->view('namamodul/index', $data);
        $this->load->view('templates/footer');
    }

    private function check_login() {
        if (!$this->session->userdata('user')) {
            redirect(base_url('index.php/auth/login'));
        }
    }
}
```

**View** (`application/views/namamodul/index.php`):
```php
<!-- Gunakan base_url() untuk assets -->
<link rel="stylesheet" href="<?php echo base_url('assets/css/adminlte.min.css'); ?>" />
```

### 3. Konversi Query ke Active Record CI2

**Native PHP (lama):**
```php
$stmt = $db->prepare('SELECT * FROM pasien WHERE nama LIKE ?');
$stmt->execute(["%$q%"]);
$rows = $stmt->fetchAll();
```

**CI2 Active Record (baru):**
```php
$this->db->like('nama', $q);
$rows = $this->db->get('pasien')->result_array();
```

### 4. Konversi Routing

**Native PHP (lama):**
```php
// index.php?page=pasien&action=create
// index.php?page=pasien&action=edit&id=1
```

**CI2 (baru):**
```php
// index.php/pasien
// index.php/pasien/create
// index.php/pasien/edit/1
```

### 5. Konfigurasi Tambahan

**application/config/routes.php:**
```php
$route['default_controller'] = 'auth/login';
$route['404_override'] = '';
```

**application/config/autoload.php:**
```php
$autoload['libraries'] = ['database', 'session'];
$autoload['helper'] = ['url'];
```

## Keuntungan CodeIgniter 2

- **Lebih ringan** dari CI3/CI4, cocok untuk shared hosting
- **MVC jelas**: Controller → Model → View
- **Active Record**: query lebih aman dan readable
- **Session management**: lebih robust dari native PHP
- **URL routing**: lebih bersih dari query string

## Catatan Penting

- CI2 adalah legacy framework (EOL), tapi stabil dan banyak digunakan
- Untuk production, pertimbangkan upgrade ke CI3 atau CI4
- Semua fitur SIMRS (antrian, rekam medis, billing, laporan, dll) perlu dimigrasi satu per satu

## Status Migrasi

- [x] Auth/Login
- [x] Dashboard
- [ ] Pasien
- [ ] Dokter
- [ ] Poli
- [ ] Obat
- [ ] Tindakan
- [ ] Pendaftaran
- [ ] Janji Temu
- [ ] Antrian
- [ ] Rekam Medis
- [ ] Surat
- [ ] Billing
- [ ] Laporan
- [ ] Users
- [ ] Pengaturan
