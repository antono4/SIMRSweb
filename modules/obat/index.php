<?php

declare(strict_types=1);

$pageTitle = 'Data Obat';
$db = db();
$action = $_GET['action'] ?? 'list';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $id = (int) ($_POST['id'] ?? 0);
    $data = [
        'kode'       => strtoupper(trim((string) $_POST['kode'])),
        'nama'       => trim((string) $_POST['nama']),
        'satuan'     => trim((string) $_POST['satuan']) ?: 'tablet',
        'stok'       => max(0, (int) $_POST['stok']),
        'harga'      => max(0, (float) str_replace(['.', ','], ['', '.'], (string) $_POST['harga'])),
        'kadaluarsa' => $_POST['kadaluarsa'] ?: null,
    ];

    if ($data['kode'] === '' || $data['nama'] === '') {
        flash('danger', 'Kode dan nama obat wajib diisi.');
        redirect(url('obat', ['action' => $id ? 'edit' : 'create', 'id' => $id]));
    }

    try {
        if ($id) {
            $db->prepare(
                'UPDATE obat SET kode=:kode, nama=:nama, satuan=:satuan, stok=:stok, harga=:harga, kadaluarsa=:kadaluarsa WHERE id=:id'
            )->execute($data + ['id' => $id]);
            flash('success', 'Data obat berhasil diperbarui.');
        } else {
            $db->prepare(
                'INSERT INTO obat (kode, nama, satuan, stok, harga, kadaluarsa) VALUES (:kode, :nama, :satuan, :stok, :harga, :kadaluarsa)'
            )->execute($data);
            flash('success', 'Obat baru berhasil ditambahkan.');
        }
    } catch (PDOException) {
        flash('danger', "Kode obat {$data['kode']} sudah digunakan.");
    }
    redirect(url('obat'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    try {
        $db->prepare('DELETE FROM obat WHERE id = ?')->execute([(int) $_POST['hapus']]);
        flash('success', 'Data obat berhasil dihapus.');
    } catch (PDOException) {
        flash('danger', 'Obat tidak dapat dihapus karena pernah diresepkan.');
    }
    redirect(url('obat'));
}

// ---------- STOK MASUK ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['stok_masuk'])) {
    verify_csrf();
    $obatId  = (int) $_POST['obat_id'];
    $jumlah  = (int) $_POST['jumlah'];
    $ket     = trim((string) $_POST['keterangan']) ?: 'Penambahan stok';
    if ($obatId && $jumlah > 0) {
        $db->beginTransaction();
        $db->prepare('UPDATE obat SET stok = stok + ? WHERE id = ?')->execute([$jumlah, $obatId]);
        $db->prepare("INSERT INTO mutasi_stok (obat_id, tipe, jumlah, keterangan, user_id) VALUES (?, 'masuk', ?, ?, ?)")
           ->execute([$obatId, $jumlah, $ket, current_user()['id']]);
        $db->commit();
        flash('success', "Stok bertambah $jumlah.");
    } else {
        flash('danger', 'Jumlah stok masuk tidak valid.');
    }
    redirect(url('obat'));
}

// ---------- KARTU STOK ----------
if ($action === 'mutasi') {
    $stmt = $db->prepare('SELECT * FROM obat WHERE id = ?');
    $stmt->execute([(int) $_GET['id']]);
    $obat = $stmt->fetch();
    if (!$obat) {
        flash('danger', 'Obat tidak ditemukan.');
        redirect(url('obat'));
    }
    $mutasi = $db->prepare(
        'SELECT m.*, u.nama AS user FROM mutasi_stok m LEFT JOIN users u ON u.id = m.user_id
         WHERE m.obat_id = ? ORDER BY m.created_at DESC, m.id DESC LIMIT 100'
    );
    $mutasi->execute([(int) $obat['id']]);
    $mutasiRows = $mutasi->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Kartu Stok: <?= e($obat['nama']) ?> (stok saat ini: <b><?= (int) $obat['stok'] ?> <?= e($obat['satuan']) ?></b>)</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Waktu</th><th>Tipe</th><th class="text-end">Jumlah</th><th>Keterangan</th><th>Referensi</th><th>Oleh</th></tr></thead>
                <tbody>
                    <?php foreach ($mutasiRows as $m): ?>
                        <tr>
                            <td><?= e(tgl($m['created_at'], true)) ?></td>
                            <td><span class="badge text-bg-<?= $m['tipe'] === 'masuk' ? 'success' : 'danger' ?>"><?= $m['tipe'] === 'masuk' ? 'Masuk' : 'Keluar' ?></span></td>
                            <td class="text-end"><?= $m['tipe'] === 'masuk' ? '+' : '-' ?><?= (int) $m['jumlah'] ?></td>
                            <td><?= e($m['keterangan'] ?: '-') ?></td>
                            <td><?= e($m['referensi'] ?: '-') ?></td>
                            <td><?= e($m['user'] ?: '-') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$mutasiRows): ?>
                        <tr><td colspan="6" class="text-center text-muted py-4">Belum ada mutasi stok.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer"><a href="<?= e(url('obat')) ?>" class="btn btn-secondary">Kembali</a></div>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

if ($action === 'create' || $action === 'edit') {
    $row = ['id' => 0, 'kode' => '', 'nama' => '', 'satuan' => 'tablet', 'stok' => 0, 'harga' => 0, 'kadaluarsa' => ''];
    if ($action === 'edit') {
        $stmt = $db->prepare('SELECT * FROM obat WHERE id = ?');
        $stmt->execute([(int) $_GET['id']]);
        $row = $stmt->fetch() ?: $row;
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-6">
        <div class="card-header"><h3 class="card-title"><?= $action === 'edit' ? 'Ubah' : 'Tambah' ?> Obat</h3></div>
        <form method="post" action="<?= e(url('obat')) ?>">
            <?= csrf_field() ?>
            <input type="hidden" name="id" value="<?= (int) $row['id'] ?>" />
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Kode Obat <span class="text-danger">*</span></label>
                    <input type="text" name="kode" class="form-control" value="<?= e($row['kode']) ?>" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Nama Obat <span class="text-danger">*</span></label>
                    <input type="text" name="nama" class="form-control" value="<?= e($row['nama']) ?>" required />
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Satuan</label>
                        <input type="text" name="satuan" class="form-control" value="<?= e($row['satuan']) ?>" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Stok</label>
                        <input type="number" name="stok" class="form-control" min="0" value="<?= (int) $row['stok'] ?>" />
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Harga Satuan (Rp)</label>
                        <input type="number" name="harga" class="form-control" min="0" step="100" value="<?= (int) $row['harga'] ?>" />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal Kadaluarsa</label>
                        <input type="date" name="kadaluarsa" class="form-control" value="<?= e($row['kadaluarsa']) ?>" />
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan</button>
                <a href="<?= e(url('obat')) ?>" class="btn btn-secondary">Batal</a>
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
    $where = 'WHERE nama LIKE ? OR kode LIKE ?';
    $params = ["%$q%", "%$q%"];
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM obat $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));

$stmt = $db->prepare("SELECT * FROM obat $where ORDER BY nama LIMIT {$pg['per_page']} OFFSET {$pg['offset']}");
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Obat</h3>
            <div class="d-flex gap-2">
                <form method="get" action="<?php echo e(base_url("/")); ?>"index.php" class="d-flex gap-1">
                    <input type="hidden" name="page" value="obat" />
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari nama / kode..." value="<?= e($q) ?>" />
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
                <a href="<?= e(url('obat', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Tambah Obat</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Kode</th><th>Nama</th><th>Satuan</th><th class="text-end">Stok</th><th class="text-end">Harga</th><th>Kadaluarsa</th><th style="width:110px">Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge text-bg-secondary"><?= e($r['kode']) ?></span></td>
                        <td><b><?= e($r['nama']) ?></b></td>
                        <td><?= e($r['satuan']) ?></td>
                        <td class="text-end">
                            <span class="badge text-bg-<?= $r['stok'] < 50 ? 'danger' : ($r['stok'] < 100 ? 'warning' : 'success') ?>">
                                <?= (int) $r['stok'] ?>
                            </span>
                        </td>
                        <td class="text-end"><?= e(rupiah($r['harga'])) ?></td>
                        <td>
                            <?= e(tgl($r['kadaluarsa'])) ?>
                            <?php if ($r['kadaluarsa'] && $r['kadaluarsa'] <= date('Y-m-d', strtotime('+90 days'))): ?>
                                <span class="badge text-bg-<?= $r['kadaluarsa'] < date('Y-m-d') ? 'danger' : 'warning' ?>" title="<?= $r['kadaluarsa'] < date('Y-m-d') ? 'Sudah kadaluarsa' : 'Mendekati kadaluarsa' ?>">
                                    <i class="bi bi-exclamation-triangle"></i>
                                </span>
                            <?php endif; ?>
                        </td>
                        <td class="table-actions">
                            <button type="button" class="btn btn-sm btn-outline-success" title="Stok Masuk"
                                    data-bs-toggle="modal" data-bs-target="#modalStok"
                                    data-id="<?= (int) $r['id'] ?>" data-nama="<?= e($r['nama']) ?>">
                                <i class="bi bi-box-arrow-in-down"></i>
                            </button>
                            <a href="<?= e(url('obat', ['action' => 'mutasi', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-info" title="Kartu Stok"><i class="bi bi-arrow-left-right"></i></a>
                            <a href="<?= e(url('obat', ['action' => 'edit', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-primary" title="Ubah"><i class="bi bi-pencil"></i></a>
                            <form method="post" action="<?= e(url('obat')) ?>" onsubmit="return confirm('Hapus obat <?= e($r['nama']) ?>?')">
                                <?= csrf_field() ?>
                                <button type="submit" name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="7" class="text-center text-muted py-4">Tidak ada data obat.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: <?= $total ?> obat</small>
        <?= render_pagination('obat', $pg, ['q' => $q]) ?>
    </div>
</div>

<div class="modal fade" id="modalStok" tabindex="-1">
    <div class="modal-dialog">
        <form method="post" action="<?= e(url('obat')) ?>" class="modal-content">
            <?= csrf_field() ?>
            <input type="hidden" name="obat_id" id="stok-obat-id" />
            <div class="modal-header">
                <h5 class="modal-title">Stok Masuk — <span id="stok-obat-nama"></span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Jumlah Masuk</label>
                    <input type="number" name="jumlah" class="form-control" min="1" value="1" required />
                </div>
                <div class="mb-3">
                    <label class="form-label">Keterangan</label>
                    <input type="text" name="keterangan" class="form-control" placeholder="Contoh: Pembelian dari distributor" />
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                <button type="submit" name="stok_masuk" value="1" class="btn btn-success"><i class="bi bi-plus-lg"></i> Tambah Stok</button>
            </div>
        </form>
    </div>
</div>
<script>
    document.getElementById('modalStok').addEventListener('show.bs.modal', function (e) {
        const btn = e.relatedTarget;
        document.getElementById('stok-obat-id').value = btn.dataset.id;
        document.getElementById('stok-obat-nama').textContent = btn.dataset.nama;
    });
</script>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
