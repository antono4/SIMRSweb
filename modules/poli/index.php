<?php

declare(strict_types=1);

$pageTitle = 'Data Poli';
$db = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $kode = strtoupper(trim((string) $_POST['kode']));
    $nama = trim((string) $_POST['nama']);
    $keterangan = trim((string) $_POST['keterangan']) ?: null;

    if ($kode === '' || $nama === '') {
        flash('danger', 'Kode dan nama poli wajib diisi.');
        redirect(url('poli', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }

    try {
        if ($id) {
            $db->prepare('UPDATE poli SET kode=?, nama=?, keterangan=? WHERE id=?')->execute([$kode, $nama, $keterangan, $id]);
            flash('success', 'Data poli berhasil diperbarui.');
        } else {
            $db->prepare('INSERT INTO poli (kode, nama, keterangan) VALUES (?,?,?)')->execute([$kode, $nama, $keterangan]);
            flash('success', 'Poli baru berhasil ditambahkan.');
        }
    } catch (PDOException) {
        flash('danger', "Kode poli $kode sudah digunakan.");
    }
    redirect(url('poli'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    try {
        $db->prepare('DELETE FROM poli WHERE id = ?')->execute([(int) $_POST['hapus']]);
        flash('success', 'Data poli berhasil dihapus.');
    } catch (PDOException) {
        flash('danger', 'Poli tidak dapat dihapus karena terkait data pelayanan.');
    }
    redirect(url('poli'));
}

if ($action === 'create' || $action === 'edit') {
    $row = ['id' => 0, 'kode' => '', 'nama' => '', 'keterangan' => ''];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM poli WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-6">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> Poli</h3></div>
        <form method="post" action="<?= e(url('poli')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Kode Poli <span class="text-danger">*</span></label>
                    <input type="text" name="kode" class="form-control" maxlength="10" value="<?= e($row['kode']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Poli <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <textarea name="keterangan" class="form-control" rows="2"><?= e($row['keterangan']) ?></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('poli')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$rows = $db->query(
    'SELECT p.*, (SELECT COUNT(*) FROM dokter d WHERE d.poli_id = p.id) AS jml_dokter,
            (SELECT COUNT(*) FROM pendaftaran r WHERE r.poli_id = p.id) AS jml_kunjungan
     FROM poli p ORDER BY p.nama'
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h3 class="card-title mb-0">Daftar Poli</h3>
        <a href="<?= e(url('poli', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah Poli</a>
    </div>
    <div class="card-body p-0">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Kode</th><th>Nama</th><th>Keterangan</th><th>Dokter</th><th>Kunjungan</th><th style="width:110px">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge text-bg-primary"><?= e($r['kode']) ?></span></td>
                        <td><b><?= e($r['nama']) ?></b></td>
                        <td><?= e($r['keterangan'] ?: '-') ?></td>
                        <td><?= (int) $r['jml_dokter'] ?></td>
                        <td><?= (int) $r['jml_kunjungan'] ?></td>
                        <td class="table-actions">
                            <a href="<?= e(url('poli', ['action' => 'edit', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('poli')) ?>" onsubmit="return confirm('Hapus poli <?= e($r['nama']) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
