<?php

declare(strict_types=1);

$pageTitle = 'Data Pasien';
$db = db();
$action = $_GET['action'] ?? 'list';

// ---------- SIMPAN (tambah / ubah) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'nik'             => trim((string) $_POST['nik']) ?: null,
        'nama'            => trim((string) $_POST['nama']),
        'jenis_kelamin'   => $_POST['jenis_kelamin'] === 'P' ? 'P' : 'L',
        'tempat_lahir'    => trim((string) $_POST['tempat_lahir']) ?: null,
        'tanggal_lahir'   => $_POST['tanggal_lahir'] ?: null,
        'golongan_darah'  => in_array($_POST['golongan_darah'] ?? '-', ['A', 'B', 'AB', 'O'], true) ? $_POST['golongan_darah'] : '-',
        'alamat'          => trim((string) $_POST['alamat']) ?: null,
        'telepon'         => trim((string) $_POST['telepon']) ?: null,
        'pekerjaan'       => trim((string) $_POST['pekerjaan']) ?: null,
        'penjamin'        => in_array($_POST['penjamin'] ?? 'Umum', ['Umum', 'BPJS', 'Asuransi'], true) ? $_POST['penjamin'] : 'Umum',
    ];

    if ($data['nama'] === '') {
        flash('danger', 'Nama pasien wajib diisi.');
        redirect(url('pasien', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }

    if ($id) {
        $stmt = $db->prepare(
            'UPDATE pasien SET nik=:nik, nama=:nama, jenis_kelamin=:jenis_kelamin, tempat_lahir=:tempat_lahir,
             tanggal_lahir=:tanggal_lahir, golongan_darah=:golongan_darah, alamat=:alamat, telepon=:telepon,
             pekerjaan=:pekerjaan, penjamin=:penjamin WHERE id=:id'
        );
        $stmt->execute($data + ['id' => $id]);
        flash('success', 'Data pasien berhasil diperbarui.');
    } else {
        $noRm = next_number('pasien', 'no_rm', 'RM-');
        $stmt = $db->prepare(
            'INSERT INTO pasien (no_rm, nik, nama, jenis_kelamin, tempat_lahir, tanggal_lahir, golongan_darah, alamat, telepon, pekerjaan, penjamin)
             VALUES (:no_rm, :nik, :nama, :jenis_kelamin, :tempat_lahir, :tanggal_lahir, :golongan_darah, :alamat, :telepon, :pekerjaan, :penjamin)'
        );
        $stmt->execute($data + ['no_rm' => $noRm]);
        flash('success', "Pasien baru terdaftar dengan No. RM $noRm.");
    }
    redirect(url('pasien'));
}

// ---------- HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    $id = (int) $_POST['hapus'];
    try {
        $db->prepare('DELETE FROM pasien WHERE id = ?')->execute([$id]);
        flash('success', 'Data pasien berhasil dihapus.');
    } catch (PDOException) {
        flash('danger', 'Pasien tidak dapat dihapus karena memiliki riwayat pendaftaran/rekam medis.');
    }
    redirect(url('pasien'));
}

// ---------- FORM ----------
if ($action === 'create' || $action === 'edit') {
    $row = [
        'id' => 0, 'no_rm' => '', 'nik' => '', 'nama' => '', 'jenis_kelamin' => 'L',
        'tempat_lahir' => '', 'tanggal_lahir' => '', 'golongan_darah' => '-',
        'alamat' => '', 'telepon' => '', 'pekerjaan' => '', 'penjamin' => 'Umum',
    ];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM pasien WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> Pasien</h3></div>
        <form method="post" action="<?= e(url('pasien')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <?php if ($row['id']): ?>
                    <div class="mb-3">
                        <label class="form-label">No. Rekam Medis</label>
                        <input type="text" class="form-control" value="<?= e($row['no_rm']) ?>" disabled />
                    </div>
                <?php endif; ?>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIK</label>
                        <input type="text" name="nik" class="form-control" value="<?= e($row['nik']) ?>" maxlength="20" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Jenis Kelamin</label>
                        <select name="jenis_kelamin" class="form-select">
                            <option value="L" <?= $row['jenis_kelamin'] === 'L' ? 'selected' : '' ?>>Laki-laki</option>
                            <option value="P" <?= $row['jenis_kelamin'] === 'P' ? 'selected' : '' ?>>Perempuan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tempat Lahir</label>
                        <input type="text" name="tempat_lahir" class="form-control" value="<?= e($row['tempat_lahir']) ?>" />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tanggal Lahir</label>
                        <input type="date" name="tanggal_lahir" class="form-control" value="<?= e($row['tanggal_lahir']) ?>" />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Golongan Darah</label>
                        <select name="golongan_darah" class="form-select">
                            <?php foreach (['-', 'A', 'B', 'AB', 'O'] as $gd): ?>
                                <option value="<?= $gd ?>" <?= $row['golongan_darah'] === $gd ? 'selected' : '' ?>><?= $gd ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Alamat</label>
                    <textarea name="alamat" class="form-control" rows="2"><?= e($row['alamat']) ?></textarea>
                </div>
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon" class="form-control" value="<?= e($row['telepon']) ?>" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Pekerjaan</label>
                        <input type="text" name="pekerjaan" class="form-control" value="<?= e($row['pekerjaan']) ?>" />
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Penjamin</label>
                        <select name="penjamin" class="form-select">
                            <?php foreach (['Umum', 'BPJS', 'Asuransi'] as $pj): ?>
                                <option value="<?= $pj ?>" <?= $row['penjamin'] === $pj ? 'selected' : '' ?>><?= $pj ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('pasien')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DAFTAR ----------
$q = trim((string) ($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE nama LIKE ? OR no_rm LIKE ? OR nik LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM pasien $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();

$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));
$stmt = $db->prepare("SELECT * FROM pasien $where ORDER BY id DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Pasien</h3>
            <div class="d-flex gap-2">
                <form method="get" action="<?php echo e(base_url("/")); ?>"index.php" class="d-flex gap-1">
                    <input type="hidden" name="page" value="pasien" />
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama / No. RM / NIK..." value="<?= e($q) ?>" />
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
                <a href="<?= e(url('pasien', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Pasien
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr>
                    <th>No. RM</th><th>Nama</th><th>L/P</th><th>Tgl Lahir</th><th>Umur</th>
                    <th>Telepon</th><th>Penjamin</th><th style="width: 140px">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><b><?= e($r['no_rm']) ?></b></td>
                        <td><?= e($r['nama']) ?></td>
                        <td><?= e($r['jenis_kelamin']) ?></td>
                        <td><?= e(tgl($r['tanggal_lahir'])) ?></td>
                        <td><?= e(umur($r['tanggal_lahir'])) ?></td>
                        <td><?= e($r['telepon'] ?: '-') ?></td>
                        <td><span class="badge text-bg-secondary"><?= e($r['penjamin']) ?></span></td>
                        <td class="table-actions">
                            <a href="<?= e(url('rekam-medis', ['action' => 'riwayat', 'pasien_id' => $r['id']])) ?>"
                               class="btn btn-sm btn-outline-info" title="Riwayat"><i class="bi bi-file-medical"></i></a>
                            <a href="<?= e(url('pasien', ['action' => 'edit', 'id' => $r['id']])) ?>"
                               class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('pasien')) ?>"
                                  onsubmit="return confirm('Hapus pasien <?= e($r['nama']) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>"
                                        class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada data pasien.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: <?= $total ?> pasien</small>
        <?= render_pagination('pasien', $pg, ['q' => $q]) ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
