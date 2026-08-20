<?php

declare(strict_types=1);

require_role('admin');

$pageTitle = 'Manajemen User';
$db = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $username = trim((string) $_POST['username']);
    $nama     = trim((string) $_POST['nama']);
    $role     = in_array($_POST['role'] ?? '', ['admin', 'petugas', 'dokter'], true) ? $_POST['role'] : 'petugas';
    $password = (string) ($_POST['password'] ?? '');

    if ($username === '' || $nama === '') {
        flash('danger', 'Username dan nama wajib diisi.');
        redirect(url('users', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }

    try {
        if ($id) {
            if ($password !== '') {
                $db->prepare('UPDATE users SET username=?, nama=?, role=?, password=? WHERE id=?')
                   ->execute([$username, $nama, $role, password_hash($password, PASSWORD_DEFAULT), $id]);
            } else {
                $db->prepare('UPDATE users SET username=?, nama=?, role=? WHERE id=?')->execute([$username, $nama, $role, $id]);
            }
            flash('success', 'User berhasil diperbarui.');
        } else {
            if (strlen($password) < 6) {
                flash('danger', 'Password minimal 6 karakter.');
                redirect(url('users', ['action' => 'create']));
            }
            $db->prepare('INSERT INTO users (username, password, nama, role) VALUES (?,?,?,?)')
               ->execute([$username, password_hash($password, PASSWORD_DEFAULT), $nama, $role]);
            flash('success', 'User baru berhasil ditambahkan.');
        }
    } catch (PDOException) {
        flash('danger', "Username $username sudah digunakan.");
    }
    redirect(url('users'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    $id = (int) $_POST['hapus'];
    if ($id === current_user()['id']) {
        flash('danger', 'Anda tidak dapat menghapus akun sendiri.');
    } else {
        $db->prepare('DELETE FROM users WHERE id = ?')->execute([$id]);
        flash('success', 'User berhasil dihapus.');
    }
    redirect(url('users'));
}

if ($action === 'create' || $action === 'edit') {
    $row = ['id' => 0, 'username' => '', 'nama' => '', 'role' => 'petugas'];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM users WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-6">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> User</h3></div>
        <form method="post" action="<?= e(url('users')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Username <span class="text-danger">*</span></label>
                    <input type="text" name="username" class="form-control" value="<?= e($row['username']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Role</label>
                    <select name="role" class="form-select">
                        <?php foreach (['admin' => 'Admin', 'petugas' => 'Petugas', 'dokter' => 'Dokter'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $row['role'] === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Password <?= $action === 'edit' ? '<small class="text-muted">(kosongkan jika tidak diubah)</small>' : '<span class="text-danger">*</span>' ?></label>
                    <input type="password" name="password" class="form-control" <?= $action === 'create' ? 'required' : '' ?> />
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('users')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$rows = $db->query('SELECT id, username, nama, role, created_at FROM users ORDER BY username')->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar User</h3>
        <a href="<?= e(url('users', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah User</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Username</th><th>Nama</th><th>Role</th><th>Terdaftar</th><th style="width:110px">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><b><?= e($r['username']) ?></b></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><span class="badge text-bg-<?= $r['role'] === 'admin' ? 'danger' : ($r['role'] === 'dokter' ? 'info' : 'secondary') ?>"><?= e(ucfirst($r['role'])) ?></span></td>
                        <td><?= e(tgl($r['created_at'])) ?></td>
                        <td class="table-actions">
                            <a href="<?= e(url('users', ['action' => 'edit', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <?php if ($r['id'] !== current_user()['id']): ?>
                                <form method="post" action="<?= e(url('users')) ?>" onsubmit="return confirm('Hapus user <?= e($r['username']) ?>?')">
                                    <?= csrf_field() ?>
                                    <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
