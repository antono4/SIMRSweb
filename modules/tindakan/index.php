<?php

declare(strict_types=1);

$pageTitle = 'Master Tarif Tindakan';
$db = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id    = (int) ($_POST['id'] ?? 0);
    $kode  = strtoupper(trim((string) $_POST['kode']));
    $nama  = trim((string) $_POST['nama']);
    $tarif = max(0, (float) $_POST['tarif']);

    if ($kode === '' || $nama === '') {
        flash('danger', 'Kode dan nama tindakan wajib diisi.');
        redirect(url('tindakan', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }
    try {
        if ($id) {
            $db->prepare('UPDATE tindakan SET kode=?, nama=?, tarif=? WHERE id=?')->execute([$kode, $nama, $tarif, $id]);
            flash('success', 'Tarif tindakan diperbarui.');
        } else {
            $db->prepare('INSERT INTO tindakan (kode, nama, tarif) VALUES (?,?,?)')->execute([$kode, $nama, $tarif]);
            flash('success', 'Tindakan baru ditambahkan.');
        }
    } catch (PDOException) {
        flash('danger', "Kode $kode sudah digunakan.");
    }
    redirect(url('tindakan'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    $db->prepare('DELETE FROM tindakan WHERE id = ?')->execute([(int) $_POST['hapus']]);
    flash('success', 'Tindakan dihapus.');
    redirect(url('tindakan'));
}

if ($action === 'create' || $action === 'edit') {
    $row = ['id' => 0, 'kode' => '', 'nama' => '', 'tarif' => 0];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM tindakan WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }
    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-6">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> Tindakan</h3></div>
        <form method="post" action="<?= e(url('tindakan')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Kode <span class="text-danger">*</span></label>
                    <input type="text" name="kode" class="form-control" maxlength="15" value="<?= e($row['kode']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Tindakan <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Tarif (Rp)</label>
                    <input type="number" name="tarif" class="form-control" min="0" step="500" value="<?= (int) $row['tarif'] ?>" />
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('tindakan')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$rows = $db->query('SELECT * FROM tindakan ORDER BY kode')->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar Tarif Tindakan</h3>
        <a href="<?= e(url('tindakan', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah Tindakan</a>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Kode</th><th>Nama Tindakan</th><th class="text-end">Tarif</th><th style="width:110px">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge text-bg-secondary"><?= e($r['kode']) ?></span></td>
                        <td><b><?= e($r['nama']) ?></b></td>
                        <td class="text-end"><?= e(rupiah($r['tarif'])) ?></td>
                        <td class="table-actions">
                            <a href="<?= e(url('tindakan', ['action' => 'edit', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('tindakan')) ?>" onsubmit="return confirm('Hapus tindakan <?= e($r['nama']) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="4" class="text-center text-muted py-4">Belum ada master tindakan.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
