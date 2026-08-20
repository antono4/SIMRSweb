<?php

declare(strict_types=1);

$pageTitle = 'Profil Saya';
$db = db();
$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $nama = trim((string) $_POST['nama']);
    $password = (string) ($_POST['password'] ?? '');

    if ($nama === '') {
        flash('danger', 'Nama wajib diisi.');
        redirect(url('profil'));
    }

    if ($password !== '') {
        if (strlen($password) < 6) {
            flash('danger', 'Password baru minimal 6 karakter.');
            redirect(url('profil'));
        }
        $db->prepare('UPDATE users SET nama=?, password=? WHERE id=?')
           ->execute([$nama, password_hash($password, PASSWORD_DEFAULT), $user['id']]);
    } else {
        $db->prepare('UPDATE users SET nama=? WHERE id=?')->execute([$nama, $user['id']]);
    }

    $_SESSION['user']['nama'] = $nama;
    flash('success', 'Profil berhasil diperbarui.');
    redirect(url('profil'));
}

require __DIR__ . '/../includes/header.php';
?>
<div class="card col-lg-6">
    <div class="card-header"><h3 class="card-title">Profil Saya</h3></div>
    <form method="post" action="<?= e(url('profil')) ?>">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Username</label>
                <input type="text" class="form-control" value="<?= e($user['username']) ?>" disabled />
            </div>
            <div class="mb-3">
                <label class="form-label">Role</label>
                <input type="text" class="form-control" value="<?= e(ucfirst($user['role'])) ?>" disabled />
            </div>
            <div class="mb-3">
                <label class="form-label">Nama Lengkap</label>
                <input type="text" name="nama" class="form-control" value="<?= e($user['nama']) ?>" required />
            </div>
            <div class="mb-3">
                <label class="form-label">Password Baru <small class="text-muted">(kosongkan jika tidak diubah)</small></label>
                <input type="password" name="password" class="form-control" autocomplete="new-password" />
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../includes/footer.php'; ?>
