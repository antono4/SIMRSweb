<?php

declare(strict_types=1);

session_start();
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';
require __DIR__ . '/includes/auth.php';

if (current_user()) {
    redirect('/index.php');
}

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $username = trim((string) ($_POST['username'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');
    if (attempt_login($username, $password)) {
        redirect('/index.php');
    }
    $error = 'Username atau password salah.';
}
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <title>Login | SIMRS</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="/assets/css/adminlte.min.css" />
    <link rel="stylesheet" href="/assets/css/custom.css" />
</head>
<body class="login-page bg-body-secondary">
<div class="login-box">
    <div class="login-logo">
        <a href="#"><b>SIM</b>RS</a>
    </div>
    <div class="card">
        <div class="card-body login-card-body">
            <p class="login-box-msg">Sistem Informasi Manajemen Rumah Sakit</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?= e($error) ?></div>
            <?php endif; ?>
            <form method="post" action="/login.php">
                <?= csrf_field() ?>
                <div class="input-group mb-3">
                    <input type="text" name="username" class="form-control" placeholder="Username"
                           value="<?= e($_POST['username'] ?? '') ?>" required autofocus />
                    <div class="input-group-text"><span class="bi bi-person-fill"></span></div>
                </div>
                <div class="input-group mb-3">
                    <input type="password" name="password" class="form-control" placeholder="Password" required />
                    <div class="input-group-text"><span class="bi bi-lock-fill"></span></div>
                </div>
                <div class="row">
                    <div class="col-12">
                        <button type="submit" class="btn btn-primary btn-block w-100">Masuk</button>
                    </div>
                </div>
            </form>
            <p class="mt-3 mb-0 text-muted small text-center">
                Akun demo: <code>admin / admin123</code>
            </p>
        </div>
    </div>
</div>
</body>
</html>
