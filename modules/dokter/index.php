<?php

declare(strict_types=1);

$pageTitle = 'Data Dokter';
$db = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'nip'          => trim((string) $_POST['nip']) ?: null,
        'nama'         => trim((string) $_POST['nama']),
        'spesialisasi' => trim((string) $_POST['spesialisasi']) ?: null,
        'poli_id'      => (int) $_POST['poli_id'] ?: null,
        'telepon'      => trim((string) $_POST['telepon']) ?: null,
        'jadwal'       => trim((string) $_POST['jadwal']) ?: null,
        'aktif'        => isset($_POST['aktif']) ? 1 : 0,
    ];

    if ($data['nama'] === '') {
        flash('danger', 'Nama dokter wajib diisi.');
        redirect(url('dokter', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }

    if ($id) {
        $db->prepare(
            'UPDATE dokter SET nip=:nip, nama=:nama, spesialisasi=:spesialisasi, poli_id=:poli_id,
             telepon=:telepon, jadwal=:jadwal, aktif=:aktif WHERE id=:id'
        )->execute($data + ['id' => $id]);
        flash('success', 'Data dokter berhasil diperbarui.');
    } else {
        $db->prepare(
            'INSERT INTO dokter (nip, nama, spesialisasi, poli_id, telepon, jadwal, aktif)
             VALUES (:nip, :nama, :spesialisasi, :poli_id, :telepon, :jadwal, :aktif)'
        )->execute($data);
        flash('success', 'Dokter baru berhasil ditambahkan.');
    }
    redirect(url('dokter'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    try {
        $db->prepare('DELETE FROM dokter WHERE id = ?')->execute([(int) $_POST['hapus']]);
        flash('success', 'Data dokter berhasil dihapus.');
    } catch (PDOException) {
        flash('danger', 'Dokter tidak dapat dihapus karena terkait data pelayanan.');
    }
    redirect(url('dokter'));
}

if ($action === 'create' || $action === 'edit') {
    $row = ['id' => 0, 'nip' => '', 'nama' => '', 'spesialisasi' => '', 'poli_id' => '', 'telepon' => '', 'jadwal' => '', 'aktif' => 1];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM dokter WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }
    $poliList = $db->query('SELECT id, nama FROM poli ORDER BY nama')->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> Dokter</h3></div>
        <form method="post" action="<?= e(url('dokter')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Nama Dokter <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">NIP</label>
                        <input type="text" name="nip" class="form-control" value="<?= e($row['nip']) ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Spesialisasi</label>
                        <input type="text" name="spesialisasi" class="form-control" value="<?= e($row['spesialisasi']) ?>" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Poli</label>
                        <select name="poli_id" class="form-select">
                            <option value="">- Pilih Poli -</option>
                            <?php foreach ($poliList as $p): ?>
                                <option value="<?= (int) $p['id'] ?>" <?= (int) $row['poli_id'] === (int) $p['id'] ? 'selected' : '' ?>>
                                    <?= e($p['nama']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Telepon</label>
                        <input type="text" name="telepon" class="form-control" value="<?= e($row['telepon']) ?>" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jadwal Praktek</label>
                        <input type="text" name="jadwal" class="form-control" placeholder="Contoh: Senin-Jumat 08:00-14:00"
                               value="<?= e($row['jadwal']) ?>" />
                    </div>
                </div>
                <div class="form-check">
                    <input type="checkbox" name="aktif" class="form-check-input" id="aktif" <?= $row['aktif'] ? 'checked' : '' ?> />
                    <label class="form-check-label" for="aktif">Aktif praktek</label>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('dokter')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

$q = trim((string) ($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE d.nama LIKE ? OR d.spesialisasi LIKE ?';
    $params = ["%$q%", "%$q%"];
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM dokter d $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));

$stmt = $db->prepare(
    "SELECT d.*, p.nama AS poli FROM dokter d LEFT JOIN poli p ON p.id = d.poli_id
     $where ORDER BY d.nama LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Dokter</h3>
            <div class="d-flex gap-2">
                <form method="get" action="<?= e(base_url("index.php")) ?>" class="d-flex gap-1">
                    <input type="hidden" name="page" value="dokter" />
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama / spesialisasi..." value="<?= e($q) ?>" />
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
                <a href="<?= e(url('dokter', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary">
                    <i class="bi bi-plus-lg"></i> Tambah Dokter
                </a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Nama</th><th>Spesialisasi</th><th>Poli</th><th>Jadwal</th><th>Telepon</th><th>Status</th><th style="width:110px">Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><b><?= e($r['nama']) ?></b></td>
                        <td><?= e($r['spesialisasi'] ?: '-') ?></td>
                        <td><?= e($r['poli'] ?: '-') ?></td>
                        <td><?= e($r['jadwal'] ?: '-') ?></td>
                        <td><?= e($r['telepon'] ?: '-') ?></td>
                        <td><span class="badge text-bg-<?= $r['aktif'] ? 'success' : 'secondary' ?>"><?= $r['aktif'] ? 'Aktif' : 'Nonaktif' ?></span></td>
                        <td class="table-actions">
                            <a href="<?= e(url('dokter', ['action' => 'edit', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('dokter')) ?>" onsubmit="return confirm('Hapus dokter <?= e($r['nama']) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data dokter.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: <?= $total ?> dokter</small>
        <?= render_pagination('dokter', $pg, ['q' => $q]) ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
