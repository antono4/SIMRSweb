<?php

declare(strict_types=1);

$pageTitle = 'Pendaftaran Pasien';
$db = db();
$action = $_GET['action'] ?? 'list';

// ---------- SIMPAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $pasienId = (int) $_POST['pasien_id'];
    $poliId   = (int) $_POST['poli_id'];
    $dokterId = (int) $_POST['dokter_id'] ?: null;
    $keluhan  = trim((string) $_POST['keluhan']) ?: null;

    if (!$pasienId || !$poliId) {
        flash('danger', 'Pasien dan poli wajib dipilih.');
        redirect(url('pendaftaran', ['action' => 'create']));
    }

    $noReg = next_number('pendaftaran', 'no_registrasi', 'REG-' . date('Ymd') . '-', 4);
    $noAntrian = next_queue_number($poliId, date('Y-m-d'));
    $db->prepare(
        'INSERT INTO pendaftaran (no_registrasi, no_antrian, pasien_id, poli_id, dokter_id, keluhan) VALUES (?,?,?,?,?,?)'
    )->execute([$noReg, $noAntrian, $pasienId, $poliId, $dokterId, $keluhan]);

    flash('success', "Pendaftaran berhasil. No. Registrasi $noReg, Nomor Antrian $noAntrian.");
    redirect(url('pendaftaran'));
}

// ---------- UBAH STATUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_status'])) {
    verify_csrf();
    $id = (int) $_POST['set_status'];
    $status = (string) $_POST['status'];
    $kembali = $_POST['kembali'] ?? 'pendaftaran';
    if (in_array($status, ['menunggu', 'dipanggil', 'diperiksa', 'selesai', 'batal'], true)) {
        if ($status === 'dipanggil') {
            $db->prepare('UPDATE pendaftaran SET status = ?, dipanggil_pada = NOW() WHERE id = ?')->execute([$status, $id]);
        } else {
            $db->prepare('UPDATE pendaftaran SET status = ? WHERE id = ?')->execute([$status, $id]);
        }
        $label = ['dipanggil' => 'dipanggil', 'diperiksa' => 'masuk ruang periksa', 'selesai' => 'selesai', 'batal' => 'dibatalkan'][$status] ?? 'diperbarui';
        flash('success', "Antrian $label.");
    }
    redirect($kembali === 'antrian' ? url('antrian') : url('pendaftaran'));
}

// ---------- HAPUS ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus'])) {
    verify_csrf();
    try {
        $db->prepare('DELETE FROM pendaftaran WHERE id = ?')->execute([(int) $_POST['hapus']]);
        flash('success', 'Pendaftaran berhasil dihapus.');
    } catch (PDOException) {
        flash('danger', 'Pendaftaran tidak dapat dihapus karena sudah memiliki rekam medis/tagihan.');
    }
    redirect(url('pendaftaran'));
}

// ---------- FORM TAMBAH ----------
if ($action === 'create') {
    $pasienList = $db->query('SELECT id, no_rm, nama FROM pasien ORDER BY nama')->fetchAll();
    $poliList   = $db->query('SELECT id, nama FROM poli ORDER BY nama')->fetchAll();
    $dokterList = $db->query(
        'SELECT d.id, d.nama, d.poli_id, p.nama AS poli FROM dokter d
         LEFT JOIN poli p ON p.id = d.poli_id WHERE d.aktif = 1 ORDER BY d.nama'
    )->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-8">
        <div class="card-header"><h3 class="card-title">Pendaftaran Baru</h3></div>
        <form method="post" action="<?= e(url('pendaftaran')) ?>">
            <?= csrf_field() ?>
            <div class="card-body">
                <div class="mb-3">
                    <label class="form-label">Pasien <span class="text-danger">*</span></label>
                    <select name="pasien_id" class="form-select" required>
                        <option value="">- Pilih Pasien -</option>
                        <?php foreach ($pasienList as $p): ?>
                            <option value="<?= (int) $p['id'] ?>"><?= e($p['no_rm'] . ' - ' . $p['nama']) ?></option>
                        <?php endforeach; ?>
                    </select>
                    <div class="form-text">Pasien belum terdaftar? <a href="<?= e(url('pasien', ['action' => 'create'])) ?>">Tambah pasien baru</a></div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Poli Tujuan <span class="text-danger">*</span></label>
                        <select name="poli_id" id="select-poli" class="form-select" required>
                            <option value="">- Pilih Poli -</option>
                            <?php foreach ($poliList as $p): ?>
                                <option value="<?= (int) $p['id'] ?>"><?= e($p['nama']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Dokter</label>
                        <select name="dokter_id" id="select-dokter" class="form-select">
                            <option value="">- Pilih Dokter -</option>
                            <?php foreach ($dokterList as $d): ?>
                                <option value="<?= (int) $d['id'] ?>" data-poli="<?= (int) $d['poli_id'] ?>">
                                    <?= e($d['nama'] . ($d['poli'] ? ' (' . $d['poli'] . ')' : '')) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keluhan</label>
                    <textarea name="keluhan" class="form-control" rows="3" placeholder="Keluhan utama pasien..."></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Daftarkan</button>
                <a href="<?= e(url('pendaftaran')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <script>
        // Saring dokter sesuai poli yang dipilih
        const selectPoli = document.getElementById('select-poli');
        const selectDokter = document.getElementById('select-dokter');
        const semuaDokter = Array.from(selectDokter.options);
        selectPoli.addEventListener('change', function () {
            const poliId = this.value;
            selectDokter.innerHTML = '';
            semuaDokter.forEach(function (opt) {
                if (!opt.value || opt.dataset.poli === poliId) selectDokter.appendChild(opt.cloneNode(true));
            });
        });
    </script>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DAFTAR ----------
$filterStatus = $_GET['status'] ?? '';
$q = trim((string) ($_GET['q'] ?? ''));
$where = [];
$params = [];
if ($filterStatus !== '' && in_array($filterStatus, ['menunggu', 'dipanggil', 'diperiksa', 'selesai', 'batal'], true)) {
    $where[] = 'r.status = ?';
    $params[] = $filterStatus;
}
if ($q !== '') {
    $where[] = '(ps.nama LIKE ? OR r.no_registrasi LIKE ?)';
    $params[] = "%$q%";
    $params[] = "%$q%";
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalStmt = $db->prepare("SELECT COUNT(*) FROM pendaftaran r JOIN pasien ps ON ps.id = r.pasien_id $whereSql");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));

$stmt = $db->prepare(
    "SELECT r.*, ps.nama AS pasien, ps.no_rm, pl.nama AS poli, d.nama AS dokter,
            (SELECT COUNT(*) FROM rekam_medis rm WHERE rm.pendaftaran_id = r.id) AS ada_rm
     FROM pendaftaran r
     JOIN pasien ps ON ps.id = r.pasien_id
     JOIN poli pl ON pl.id = r.poli_id
     LEFT JOIN dokter d ON d.id = r.dokter_id
     $whereSql
     ORDER BY r.tanggal DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Pendaftaran</h3>
            <div class="d-flex gap-2 flex-wrap">
                <form method="get" action="<?php echo e(base_url("/")); ?>"index.php" class="d-flex gap-1">
                    <input type="hidden" name="page" value="pendaftaran" />
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <?php foreach (['menunggu' => 'Menunggu', 'dipanggil' => 'Dipanggil', 'diperiksa' => 'Diperiksa', 'selesai' => 'Selesai', 'batal' => 'Batal'] as $k => $v): ?>
                            <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari pasien / no. reg..." value="<?= e($q) ?>" />
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
                </form>
                <a href="<?= e(url('pendaftaran', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Pendaftaran Baru</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Antrian</th><th>No. Registrasi</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Tanggal</th><th>Keluhan</th><th>Status</th><th style="width:190px">Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><span class="badge text-bg-dark fs-6"><?= e($r['no_antrian'] ?: '-') ?></span></td>
                        <td><b><?= e($r['no_registrasi']) ?></b></td>
                        <td><?= e($r['no_rm'] . ' - ' . $r['pasien']) ?></td>
                        <td><?= e($r['poli']) ?></td>
                        <td><?= e($r['dokter'] ?: '-') ?></td>
                        <td><?= e(tgl($r['tanggal'], true)) ?></td>
                        <td><?= e(mb_strimwidth((string) $r['keluhan'], 0, 40, '...')) ?></td>
                        <td><?= status_badge($r['status']) ?></td>
                        <td class="table-actions">
                            <?php if ($r['status'] === 'menunggu'): ?>
                                <form method="post" action="<?= e(url('pendaftaran')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="dipanggil" />
                                    <button name="set_status" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-warning" title="Panggil"><i class="bi bi-megaphone"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'dipanggil'): ?>
                                <form method="post" action="<?= e(url('pendaftaran')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="diperiksa" />
                                    <button name="set_status" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-info" title="Mulai periksa"><i class="bi bi-play-circle"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (in_array($r['status'], ['dipanggil', 'diperiksa'], true)): ?>
                                <form method="post" action="<?= e(url('pendaftaran')) ?>">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="status" value="selesai" />
                                    <button name="set_status" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-success" title="Selesaikan"><i class="bi bi-check-circle"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if (!$r['ada_rm'] && $r['status'] !== 'batal'): ?>
                                <a href="<?= e(url('rekam-medis', ['action' => 'create', 'pendaftaran_id' => $r['id']])) ?>"
                                   class="btn btn-sm btn-outline-primary" title="Isi Rekam Medis"><i class="bi bi-file-medical"></i></a>
                            <?php endif; ?>
                            <?php if (!$r['ada_rm']): ?>
                                <form method="post" action="<?= e(url('pendaftaran')) ?>" onsubmit="return confirm('Hapus pendaftaran ini?')">
                                    <?= csrf_field() ?>
                                    <button name="hapus" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger" title="Hapus"><i class="bi bi-trash"></i></button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="9" class="text-center text-muted py-4">Tidak ada data pendaftaran.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: <?= $total ?> pendaftaran</small>
        <?= render_pagination('pendaftaran', $pg, ['status' => $filterStatus, 'q' => $q]) ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
