<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect(base_url('index.php'));
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($username, $password)) {
        redirect(base_url('index.php'));
    }
    $error = 'Username atau password salah.';
}

$logoSvg = '<svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5V4z" fill="#fff"/></svg>';
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Masuk | SIMRS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/fonts/plus-jakarta-sans.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/css/adminlte.min.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/css/custom.css')) ?>" />
</head>
<body class="login-page">
<div class="login-wrap">
    <aside class="login-brand-panel">
        <div class="brand-row">
            <span class="brand-mark"><?= $logoSvg ?></span>
            <span class="brand-name">SIMRS</span>
        </div>
        <h1><?= e(nama_rs()) ?></h1>
        <p class="brand-sub">Sistem Informasi Manajemen Rumah Sakit</p>
        <p class="lead">
            <?= e(setting('alamat_rs')) ?> &middot; <?= e(setting('telepon_rs')) ?><br />
            Platform terpadu untuk mengelola pendaftaran pasien, pelayanan poli,
            rekam medis elektronik, farmasi, dan kasir dalam satu sistem yang aman.
        </p>
        <ul class="login-feature">
            <li><span class="fi"><i class="bi bi-clipboard2-pulse"></i></span>Pendaftaran &amp; antrian pasien real-time</li>
            <li><span class="fi"><i class="bi bi-file-earmark-medical"></i></span>Rekam medis elektronik &amp; e-resep</li>
            <li><span class="fi"><i class="bi bi-capsule-pill"></i></span>Stok farmasi terpotong otomatis saat peresepan</li>
            <li><span class="fi"><i class="bi bi-shield-lock"></i></span>Kontrol akses berbasis peran &amp; audit aman</li>
        </ul>
    </aside>
    <main class="login-form-panel">
        <div class="login-card">
            <span class="login-mark"><?= $logoSvg ?></span>
            <h2>Selamat Datang</h2>
            <p class="sub">Masuk untuk mengelola operasional rumah sakit</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="<?= e(base_url('login.php')) ?>">
                <?= csrf_field() ?>
                <div class="mb-3">
                    <label class="form-label" for="username">Username</label>
                    <div class="input-group">
                        <input type="text" id="username" name="username" class="form-control"
                               placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" required autofocus />
                        <span class="input-group-text"><i class="bi bi-person"></i></span>
                    </div>
                </div>
                <div class="mb-4">
                    <label class="form-label" for="password">Password</label>
                    <div class="input-group">
                        <input type="password" id="password" name="password" class="form-control"
                               placeholder="Masukkan password" required />
                        <span class="input-group-text"><i class="bi bi-lock"></i></span>
                    </div>
                </div>
                <button type="submit" class="btn btn-primary btn-login w-100">
                    <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
                </button>
            </form>
            <div class="login-hint">
                Akun demo: <code>admin / admin123</code>
            </div>
        </div>
    </main>
</div>
</body>
</html>
