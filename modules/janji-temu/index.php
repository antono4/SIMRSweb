<?php

declare(strict_types=1);

$pageTitle = 'Janji Temu';
$db = db();
$action = $_GET['action'] ?? 'list';

// ---------- SIMPAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $pasienId = (int) $_POST['pasien_id'];
    $poliId   = (int) $_POST['poli_id'];
    $dokterId = (int) $_POST['dokter_id'] ?: null;
    $tanggal  = $_POST['tanggal'] ?? '';
    $jam      = $_POST['jam'] ?? '';
    $keluhan  = trim((string) $_POST['keluhan']) ?: null;

    if (!$pasienId || !$poliId || !$tanggal || !$jam) {
        flash('danger', 'Pasien, poli, tanggal, dan jam wajib diisi.');
        redirect(url('janji-temu', ['action' => 'create']));
    }
    if ($tanggal < date('Y-m-d')) {
        flash('danger', 'Tanggal janji temu tidak boleh di masa lalu.');
        redirect(url('janji-temu', ['action' => 'create']));
    }

    $db->prepare(
        'INSERT INTO janji_temu (pasien_id, poli_id, dokter_id, tanggal, jam, keluhan) VALUES (?,?,?,?,?,?)'
    )->execute([$pasienId, $poliId, $dokterId, $tanggal, $jam, $keluhan]);

    flash('success', 'Janji temu berhasil dijadwalkan untuk ' . tgl($tanggal) . ' pukul ' . substr($jam, 0, 5) . '.');
    redirect(url('janji-temu'));
}

// ---------- KONFIRMASI KEHADIRAN → buat pendaftaran otomatis ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hadir'])) {
    verify_csrf();
    $id = (int) $_POST['hadir'];

    $stmt = $db->prepare('SELECT * FROM janji_temu WHERE id = ?');
    $stmt->execute([$id]);
    $jt = $stmt->fetch();

    if ($jt && $jt['status'] === 'dijadwalkan') {
        $db->beginTransaction();
        $noReg = next_number('pendaftaran', 'no_registrasi', 'REG-' . date('Ymd') . '-', 4);
        $noAntrian = next_queue_number((int) $jt['poli_id'], date('Y-m-d'));
        $db->prepare(
            'INSERT INTO pendaftaran (no_registrasi, no_antrian, pasien_id, poli_id, dokter_id, keluhan) VALUES (?,?,?,?,?,?)'
        )->execute([$noReg, $noAntrian, $jt['pasien_id'], $jt['poli_id'], $jt['dokter_id'], $jt['keluhan']]);
        $regId = (int) $db->lastInsertId();
        $db->prepare("UPDATE janji_temu SET status = 'hadir', pendaftaran_id = ? WHERE id = ?")->execute([$regId, $id]);
        $db->commit();
        flash('success', "Pasien hadir. Terdaftar dengan antrian $noAntrian.");
    } else {
        flash('warning', 'Janji temu sudah diproses sebelumnya.');
    }
    redirect(url('janji-temu'));
}

// ---------- BATALKAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['batalkan'])) {
    verify_csrf();
    $db->prepare("UPDATE janji_temu SET status = 'batal' WHERE id = ? AND status = 'dijadwalkan'")
       ->execute([(int) $_POST['batalkan']]);
    flash('success', 'Janji temu dibatalkan.');
    redirect(url('janji-temu'));
}

// ---------- FORM ----------
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
        <div class="card-header"><h3 class="card-title">Buat Janji Temu</h3></div>
        <form method="post" action="<?= e(url('janji-temu')) ?>">
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
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Poli <span class="text-danger">*</span></label>
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
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Tanggal <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal" class="form-control" min="<?= date('Y-m-d') ?>" value="<?= date('Y-m-d') ?>" required />
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Jam <span class="text-danger">*</span></label>
                        <input type="time" name="jam" class="form-control" value="08:00" required />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Keluhan / Catatan</label>
                    <textarea name="keluhan" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-calendar-check"></i> Jadwalkan</button>
                <a href="<?= e(url('janji-temu')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <script>
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
$filterTanggal = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['tanggal'] ?? '')) ? $_GET['tanggal'] : '';
$filterStatus  = $_GET['status'] ?? '';

$where = [];
$params = [];
if ($filterTanggal) {
    $where[] = 'j.tanggal = ?';
    $params[] = $filterTanggal;
}
if (in_array($filterStatus, ['dijadwalkan', 'hadir', 'batal'], true)) {
    $where[] = 'j.status = ?';
    $params[] = $filterStatus;
}
$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$stmt = $db->prepare(
    "SELECT j.*, ps.nama AS pasien, ps.no_rm, pl.nama AS poli, d.nama AS dokter
     FROM janji_temu j
     JOIN pasien ps ON ps.id = j.pasien_id
     JOIN poli pl ON pl.id = j.poli_id
     LEFT JOIN dokter d ON d.id = j.dokter_id
     $whereSql
     ORDER BY j.tanggal DESC, j.jam ASC LIMIT 100"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$badgeJt = ['dijadwalkan' => ['Dijadwalkan', 'info'], 'hadir' => ['Hadir', 'success'], 'batal' => ['Batal', 'danger']];

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Janji Temu</h3>
            <div class="d-flex gap-2 flex-wrap">
                <form method="get" action="<?= e(base_url("index.php")) ?>" class="d-flex gap-1">
                    <input type="hidden" name="page" value="janji-temu" />
                    <input type="date" name="tanggal" class="form-control form-control-sm" value="<?= e($filterTanggal) ?>" />
                    <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">Semua Status</option>
                        <?php foreach ($badgeJt as $k => [$v]) : ?>
                            <option value="<?= $k ?>" <?= $filterStatus === $k ? 'selected' : '' ?>><?= $v ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button class="btn btn-sm btn-outline-primary"><i class="bi bi-funnel"></i></button>
                </form>
                <a href="<?= e(url('janji-temu', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg"></i> Janji Temu Baru</a>
            </div>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead>
                <tr><th>Tanggal</th><th>Jam</th><th>Pasien</th><th>Poli</th><th>Dokter</th><th>Keluhan</th><th>Status</th><th style="width:150px">Aksi</th></tr>
            </thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <?php [$label, $warna] = $badgeJt[$r['status']]; ?>
                    <tr>
                        <td><?= e(tgl($r['tanggal'])) ?></td>
                        <td><?= e(substr($r['jam'], 0, 5)) ?></td>
                        <td><b><?= e($r['no_rm'] . ' - ' . $r['pasien']) ?></b></td>
                        <td><?= e($r['poli']) ?></td>
                        <td><?= e($r['dokter'] ?: '-') ?></td>
                        <td><?= e(mb_strimwidth((string) $r['keluhan'], 0, 35, '...')) ?></td>
                        <td><span class="badge text-bg-<?= $warna ?>"><?= $label ?></span></td>
                        <td class="table-actions">
                            <?php if ($r['status'] === 'dijadwalkan'): ?>
                                <form method="post" action="<?= e(url('janji-temu')) ?>">
                                    <?= csrf_field() ?>
                                    <button name="hadir" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-success" title="Konfirmasi hadir (buat pendaftaran)">
                                        <i class="bi bi-person-check"></i> Hadir
                                    </button>
                                </form>
                                <form method="post" action="<?= e(url('janji-temu')) ?>" onsubmit="return confirm('Batalkan janji temu ini?')">
                                    <?= csrf_field() ?>
                                    <button name="batalkan" value="<?= (int) $r['id'] ?>" class="btn btn-sm btn-outline-danger" title="Batalkan"><i class="bi bi-x"></i></button>
                                </form>
                            <?php endif; ?>
                            <?php if ($r['status'] === 'hadir' && $r['pendaftaran_id']): ?>
                                <a href="<?= e(url('pendaftaran', ['q' => ''])) ?>" class="btn btn-sm btn-outline-info" title="Lihat pendaftaran"><i class="bi bi-link-45deg"></i></a>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="8" class="text-center text-muted py-4">Tidak ada janji temu.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
