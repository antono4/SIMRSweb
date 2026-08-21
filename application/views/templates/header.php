<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8" />
    <title><?php echo $pageTitle ?? 'SIMRS'; ?> | <?php echo $nama_rs ?? 'SIMRS'; ?></title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/fonts/plus-jakarta-sans.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/vendor/bootstrap-icons/bootstrap-icons.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/adminlte.min.css'); ?>" />
    <link rel="stylesheet" href="<?php echo base_url('assets/css/custom.css'); ?>" />
</head>
<body class="layout-fixed sidebar-expand-lg bg-body-tertiary">
<div class="app-wrapper">
    <nav class="app-header navbar navbar-expand bg-body">
        <div class="container-fluid">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" data-lte-toggle="sidebar" href="#" role="button"><i class="bi bi-list"></i></a>
                </li>
                <li class="nav-item d-none d-md-block"><span class="nav-link"><?php echo $pageTitle ?? ''; ?></span></li>
            </ul>
            <ul class="navbar-nav ms-auto">
                <li class="nav-item dropdown user-menu">
                    <a href="#" class="nav-link dropdown-toggle" data-bs-toggle="dropdown">
                        <img src="<?php echo base_url('assets/img/avatar.jpg'); ?>" class="user-image rounded-circle shadow" alt="User" />
                        <span class="d-none d-md-inline"><?php echo $user['nama'] ?? 'User'; ?></span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-end">
                        <li class="user-header text-bg-primary">
                            <img src="<?php echo base_url('assets/img/avatar.jpg'); ?>" class="rounded-circle shadow" alt="User" />
                            <p><?php echo $user['nama'] ?? 'User'; ?><small><?php echo ucfirst($user['role'] ?? 'user'); ?></small></p>
                        </li>
                        <li class="user-footer">
                            <a href="<?php echo base_url('index.php/profil'); ?>" class="btn btn-default btn-flat">Profil</a>
                            <a href="<?php echo base_url('index.php/auth/logout'); ?>" class="btn btn-danger btn-flat float-end">Keluar</a>
                        </li>
                    </ul>
                </li>
            </ul>
        </div>
    </nav>
    <?php $this->load->view('templates/sidebar'); ?>
    <main class="app-main">
        <div class="app-content-header">
            <div class="container-fluid">
                <div class="row">
                    <div class="col-sm-6"><h3 class="mb-0"><?php echo $pageTitle ?? ''; ?></h3></div>
                    <div class="col-sm-6">
                        <ol class="breadcrumb float-sm-end">
                            <li class="breadcrumb-item"><a href="<?php echo base_url('index.php/dashboard'); ?>">Beranda</a></li>
                            <li class="breadcrumb-item active"><?php echo $pageTitle ?? ''; ?></li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>
        <div class="app-content">
            <div class="container-fluid">
