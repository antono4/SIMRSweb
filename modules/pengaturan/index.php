<?php

declare(strict_types=1);

require_role('admin');

$pageTitle = 'Pengaturan Rumah Sakit';
$db = db();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $nama = trim((string) $_POST['nama_rs']);
    if ($nama === '') {
        flash('danger', 'Nama rumah sakit wajib diisi.');
        redirect(url('pengaturan'));
    }
    $stmt = $db->prepare('INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)');
    $stmt->execute(['nama_rs', $nama]);
    $stmt->execute(['alamat_rs', trim((string) $_POST['alamat_rs'])]);
    $stmt->execute(['telepon_rs', trim((string) $_POST['telepon_rs'])]);

    flash('success', 'Pengaturan rumah sakit berhasil disimpan.');
    redirect(url('pengaturan'));
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="card col-lg-6">
    <div class="card-header"><h3 class="card-title">Identitas Rumah Sakit</h3></div>
    <form method="post" action="<?= e(url('pengaturan')) ?>">
        <?= csrf_field() ?>
        <div class="card-body">
            <div class="mb-3">
                <label class="form-label">Nama Rumah Sakit <span class="text-danger">*</span></label>
                <input type="text" name="nama_rs" class="form-control" value="<?= e(setting('nama_rs')) ?>" required />
                <div class="form-text">Ditampilkan di sidebar, halaman login, judul halaman, dan invoice.</div>
            </div>
            <div class="mb-3">
                <label class="form-label">Alamat</label>
                <textarea name="alamat_rs" class="form-control" rows="2"><?= e(setting('alamat_rs')) ?></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label">Telepon</label>
                <input type="text" name="telepon_rs" class="form-control" value="<?= e(setting('telepon_rs')) ?>" />
            </div>
        </div>
        <div class="card-footer">
            <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
        </div>
    </form>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
