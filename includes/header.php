<?php
$user = current_user();
$currentPage = $_GET['page'] ?? 'dashboard';
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title><?= e($pageTitle ?? 'SIMRS') ?> | <?= e(nama_rs()) ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="color-scheme" content="light dark" />
    <script>
        // Terapkan tema tersimpan sebelum render; default: siang (light)
        (function () {
            var theme = 'light';
            try { theme = localStorage.getItem('lte-theme') || 'light'; } catch (e) {}
            if (theme !== 'light' && theme !== 'dark') theme = 'light';
            document.documentElement.setAttribute('data-bs-theme', theme);
            document.documentElement.style.colorScheme = theme;
            document.documentElement.setAttribute('data-lte-theme-resolved', '');
        })();
    </script>
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/fonts/plus-jakarta-sans.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/overlayscrollbars/overlayscrollbars.min.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/css/adminlte.min.css')) ?>" />
    <link rel="stylesheet" href="<?= e(base_url('assets/css/custom.css')) ?>" />
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button">
                        <i class="bi bi-list"></i>
                    </a>
                </li>
                <li class="nav-item d-none d-md-block"><span class="nav-link"><?= e($pageTitle ?? '') ?></span></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link" href="#" id="bd-theme" aria-label="Mode siang / malam"
                       data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="bi bi-sun-fill" data-lte-theme-icon="light"></i>
                        <i class="bi bi-moon-stars-fill d-none" data-lte-theme-icon="dark"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme" style="--bs-dropdown-min-width: 8rem">
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="light">
                                <i class="bi bi-sun-fill me-2"></i> Siang
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                        <li>
                            <button type="button" class="dropdown-item d-flex align-items-center" data-bs-theme-value="dark">
                                <i class="bi bi-moon-stars-fill me-2"></i> Malam
                                <i class="bi bi-check-lg ms-auto d-none"></i>
                            </button>
                        </li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#" data-lte-toggle="fullscreen">
                        <i data-lte-icon="maximize" class="bi bi-arrows-fullscreen"></i>
                        <i data-lte-icon="minimize" class="bi bi-fullscreen-exit" style="display: none"></i>
                    </a>
                </li>
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?= e(base_url('assets/img/avatar.jpg')) ?>" class="user-image rounded-circle shadow" alt="User" />
                        <span class="d-none d-md-inline"><?= e($user['nama']) ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="/assets/img/avatar.jpg" class="rounded-circle shadow" alt="User" />
                            <p>
                                <?= e($user['nama']) ?>
                                <small><?= e(ucfirst($user['role'])) ?></small>
                            </p>
                        </li>
                        <li class="user-footer">
                            <a href="<?= e(url('profil')) ?>" class="btn btn-default btn-flat">Profil</a>
                            <a href="<?= e(base_url('logout.php')) ?>" class="btn btn-danger btn-flat float-end">Keluar</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <?php require __DIR__ . '/sidebar.php'; ?>
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?= e($pageTitle ?? '') ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?= e(url('dashboard')) ?>">Beranda</a></li>
                            <li class="breadcrumb-item active"><?= e($pageTitle ?? '') ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
                <?php foreach (get_flashes() as $f): ?>
                    <div class="alert alert-<?= e($f['type']) ?> alert-dismissible fade show" role="alert">
                        <?= e($f['message']) ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endforeach; ?>
