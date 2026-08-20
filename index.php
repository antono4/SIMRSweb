<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

require_login();

$routes = [
    'dashboard'    => 'dashboard/index.php',
    'pasien'       => 'pasien/index.php',
    'dokter'       => 'dokter/index.php',
    'poli'         => 'poli/index.php',
    'obat'         => 'obat/index.php',
    'pendaftaran'  => 'pendaftaran/index.php',
    'rekam-medis'  => 'rekam-medis/index.php',
    'billing'      => 'billing/index.php',
    'users'        => 'users/index.php',
    'pengaturan'   => 'pengaturan/index.php',
    'profil'       => 'profil.php',
];

$page = $_GET['page'] ?? 'dashboard';

if (!isset($routes[$page])) {
    http_response_code(404);
    $pageTitle = 'Halaman Tidak Ditemukan';
    require __DIR__ . '/includes/header.php';
    echo '<div class="alert alert-warning">Halaman <b>' . e($page) . '</b> tidak ditemukan.</div>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

require __DIR__ . '/modules/' . $routes[$page];
