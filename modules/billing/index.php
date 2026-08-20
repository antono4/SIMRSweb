<?php

declare(strict_types=1);

$pageTitle = 'Billing / Kasir';
$db = db();
$action = $_GET['action'] ?? 'list';

const BIAYA_KONSULTASI_DEFAULT = 50000;

// ---------- BUAT TAGIHAN ----------
if ($action === 'buat') {
    $pendaftaranId = (int) ($_GET['pendaftaran_id'] ?? 0);

    $stmt = $db->prepare(
        'SELECT r.*, ps.nama AS pasien, ps.no_rm, pl.nama AS poli, d.nama AS dokter
         FROM pendaftaran r
         JOIN pasien ps ON ps.id = r.pasien_id
         JOIN poli pl ON pl.id = r.poli_id
         LEFT JOIN dokter d ON d.id = r.dokter_id
         WHERE r.id = ?'
    );
    $stmt->execute([$pendaftaranId]);
    $reg = $stmt->fetch();
    if (!$reg) {
        flash('danger', 'Pendaftaran tidak ditemukan.');
        redirect(url('billing'));
    }

    // Sudah ada tagihan? arahkan ke detail
    $cek = $db->prepare('SELECT id FROM tagihan WHERE pendaftaran_id = ?');
    $cek->execute([$pendaftaranId]);
    if ($existing = $cek->fetchColumn()) {
        redirect(url('billing', ['action' => 'detail', 'id' => $existing]));
    }

    // Hitung otomatis dari rekam medis + resep
    $rmStmt = $db->prepare('SELECT * FROM rekam_medis WHERE pendaftaran_id = ?');
    $rmStmt->execute([$pendaftaranId]);
    $rm = $rmStmt->fetch();

    $biayaTindakan = $rm ? (float) $rm['biaya_tindakan'] : 0;
    $biayaObat = 0;
    if ($rm) {
        $obatStmt = $db->prepare(
            'SELECT COALESCE(SUM(rs.jumlah * o.harga),0) FROM resep rs JOIN obat o ON o.id = rs.obat_id WHERE rs.rekam_medis_id = ?'
        );
        $obatStmt->execute([$rm['id']]);
        $biayaObat = (float) $obatStmt->fetchColumn();
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
        verify_csrf();
        $konsultasi = max(0, (float) $_POST['biaya_konsultasi']);
        $tindakan   = max(0, (float) $_POST['biaya_tindakan']);
        $obat       = max(0, (float) $_POST['biaya_obat']);
        $total      = $konsultasi + $tindakan + $obat;

        $noInv = next_number('tagihan', 'no_invoice', 'INV-' . date('Ymd') . '-', 4);
        $db->prepare(
            'INSERT INTO tagihan (no_invoice, pendaftaran_id, biaya_konsultasi, biaya_tindakan, biaya_obat, total) VALUES (?,?,?,?,?,?)'
        )->execute([$noInv, $pendaftaranId, $konsultasi, $tindakan, $obat, $total]);

        flash('success', "Tagihan $noInv berhasil dibuat.");
        redirect(url('billing', ['action' => 'detail', 'id' => (int) $db->lastInsertId()]));
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card col-lg-6">
        <div class="card-header"><h3 class="card-title">Buat Tagihan — <?= e($reg['no_registrasi']) ?></h3></div>
        <form method="post" action="<?= e(url('billing', ['action' => 'buat', 'pendaftaran_id' => $pendaftaranId])) ?>">
            <?= csrf_field() ?>
            <div class="card-body">
                <p><b>Pasien:</b> <?= e($reg['no_rm'] . ' - ' . $reg['pasien']) ?><br />
                   <b>Poli:</b> <?= e($reg['poli']) ?> &nbsp; <b>Dokter:</b> <?= e($reg['dokter'] ?: '-') ?></p>
                <div class="mb-3">
                    <label class="form-label">Biaya Konsultasi (Rp)</label>
                    <input type="number" name="biaya_konsultasi" id="biaya_konsultasi" class="form-control" min="0" step="1000"
                           value="<?= BIAYA_KONSULTASI_DEFAULT ?>" oninput="hitungTotal()" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Biaya Tindakan (Rp)</label>
                    <input type="number" name="biaya_tindakan" id="biaya_tindakan" class="form-control" min="0" step="1000"
                           value="<?= (int) $biayaTindakan ?>" oninput="hitungTotal()" />
                </div>
                <div class="mb-3">
                    <label class="form-label">Biaya Obat (Rp)</label>
                    <input type="number" name="biaya_obat" id="biaya_obat" class="form-control" min="0" step="100"
                           value="<?= (int) $biayaObat ?>" oninput="hitungTotal()" />
                </div>
                <div class="alert alert-info d-flex justify-content-between align-items-center">
                    <span>Total Tagihan</span><b id="tampil-total"></b>
                </div>
            </div>
            <div class="card-footer">
                <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Buat Tagihan</button>
                <a href="<?= e(url('billing')) ?>" class="btn btn-secondary">Batal</a>
            </div>
        </form>
    </div>
    <script>
        function hitungTotal() {
            const total = ['biaya_konsultasi', 'biaya_tindakan', 'biaya_obat']
                .map(id => parseFloat(document.getElementById(id).value) || 0)
                .reduce((a, b) => a + b, 0);
            document.getElementById('tampil-total').textContent = 'Rp ' + total.toLocaleString('id-ID');
        }
        hitungTotal();
    </script>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DETAIL + PEMBAYARAN ----------
if ($action === 'detail') {
    $stmt = $db->prepare(
        'SELECT t.*, r.no_registrasi, ps.nama AS pasien, ps.no_rm, pl.nama AS poli
         FROM tagihan t
         JOIN pendaftaran r ON r.id = t.pendaftaran_id
         JOIN pasien ps ON ps.id = r.pasien_id
         JOIN poli pl ON pl.id = r.poli_id
         WHERE t.id = ?'
    );
    $stmt->execute([(int) $_GET['id']]);
    $t = $stmt->fetch();
    if (!$t) {
        flash('danger', 'Tagihan tidak ditemukan.');
        redirect(url('billing'));
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bayar'])) {
        verify_csrf();
        if ($t['status'] === 'lunas') {
            flash('warning', 'Tagihan sudah lunas.');
            redirect(url('billing', ['action' => 'detail', 'id' => $t['id']]));
        }
        $metode = in_array($_POST['metode'] ?? '', ['Tunai', 'Transfer', 'BPJS', 'Asuransi'], true) ? $_POST['metode'] : 'Tunai';
        $db->prepare("UPDATE tagihan SET status = 'lunas', metode_pembayaran = ?, dibayar_pada = NOW() WHERE id = ?")
           ->execute([$metode, $t['id']]);
        flash('success', 'Pembayaran berhasil. Tagihan lunas.');
        redirect(url('billing', ['action' => 'detail', 'id' => $t['id']]));
    }

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="row">
        <div class="col-lg-7">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="card-title mb-0">Invoice <?= e($t['no_invoice']) ?></h3>
                    <?= status_badge($t['status']) ?>
                </div>
                <div class="card-body">
                    <div class="row mb-3">
                        <div class="col-md-6"><b>No. Registrasi:</b> <?= e($t['no_registrasi']) ?></div>
                        <div class="col-md-6"><b>Tanggal:</b> <?= e(tgl($t['tanggal'], true)) ?></div>
                        <div class="col-md-6"><b>Pasien:</b> <?= e($t['no_rm'] . ' - ' . $t['pasien']) ?></div>
                        <div class="col-md-6"><b>Poli:</b> <?= e($t['poli']) ?></div>
                    </div>
                    <table class="table table-bordered">
                        <thead><tr><th>Rincian</th><th class="text-end" style="width:200px">Jumlah</th></tr></thead>
                        <tbody>
                            <tr><td>Biaya Konsultasi</td><td class="text-end"><?= e(rupiah($t['biaya_konsultasi'])) ?></td></tr>
                            <tr><td>Biaya Tindakan</td><td class="text-end"><?= e(rupiah($t['biaya_tindakan'])) ?></td></tr>
                            <tr><td>Biaya Obat</td><td class="text-end"><?= e(rupiah($t['biaya_obat'])) ?></td></tr>
                        </tbody>
                        <tfoot>
                            <tr class="table-primary"><th>Total</th><th class="text-end"><?= e(rupiah($t['total'])) ?></th></tr>
                        </tfoot>
                    </table>
                    <?php if ($t['status'] === 'lunas'): ?>
                        <p class="text-muted mb-0">
                            Dibayar via <b><?= e($t['metode_pembayaran']) ?></b> pada <?= e(tgl($t['dibayar_pada'], true)) ?>
                        </p>
                    <?php endif; ?>
                </div>
                <div class="card-footer">
                    <a href="<?= e(url('billing')) ?>" class="btn btn-secondary">Kembali</a>
                    <button type="button" class="btn btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
                </div>
            </div>
        </div>
        <?php if ($t['status'] === 'belum'): ?>
            <div class="col-lg-5">
                <div class="card">
                    <div class="card-header"><h3 class="card-title">Proses Pembayaran</h3></div>
                    <form method="post" action="<?= e(url('billing', ['action' => 'detail', 'id' => $t['id']])) ?>">
                        <?= csrf_field() ?>
                        <div class="card-body">
                            <div class="mb-3">
                                <label class="form-label">Metode Pembayaran</label>
                                <select name="metode" class="form-select">
                                    <?php foreach (['Tunai', 'Transfer', 'BPJS', 'Asuransi'] as $m): ?>
                                        <option value="<?= $m ?>"><?= $m ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="alert alert-info d-flex justify-content-between">
                                <span>Yang harus dibayar</span><b><?= e(rupiah($t['total'])) ?></b>
                            </div>
                        </div>
                        <div class="card-footer">
                            <button type="submit" name="bayar" value="1" class="btn btn-success w-100"
                                    onclick="return confirm('Proses pembayaran <?= e(rupiah($t['total'])) ?>?')">
                                <i class="bi bi-cash-coin"></i> Bayar Sekarang
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        <?php endif; ?>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DAFTAR ----------
$filterStatus = $_GET['status'] ?? '';
$where = '';
$params = [];
if (in_array($filterStatus, ['belum', 'lunas'], true)) {
    $where = 'WHERE t.status = ?';
    $params[] = $filterStatus;
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM tagihan t $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));

$stmt = $db->prepare(
    "SELECT t.*, r.no_registrasi, ps.nama AS pasien
     FROM tagihan t
     JOIN pendaftaran r ON r.id = t.pendaftaran_id
     JOIN pasien ps ON ps.id = r.pasien_id
     $where ORDER BY t.tanggal DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

// Pendaftaran selesai yang belum ditagih
$belumDitagih = $db->query(
    "SELECT r.id, r.no_registrasi, ps.nama AS pasien, r.tanggal
     FROM pendaftaran r
     JOIN pasien ps ON ps.id = r.pasien_id
     LEFT JOIN tagihan t ON t.pendaftaran_id = r.id
     WHERE t.id IS NULL AND r.status = 'selesai'
     ORDER BY r.tanggal DESC LIMIT 10"
)->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="row">
    <div class="col-lg-8">
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <h3 class="card-title mb-0">Daftar Tagihan</h3>
                    <form method="get" action="/index.php" class="d-flex gap-1">
                        <input type="hidden" name="page" value="billing" />
                        <select name="status" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">Semua</option>
                            <option value="belum" <?= $filterStatus === 'belum' ? 'selected' : '' ?>>Belum Lunas</option>
                            <option value="lunas" <?= $filterStatus === 'lunas' ? 'selected' : '' ?>>Lunas</option>
                        </select>
                    </form>
                </div>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped table-hover mb-0">
                    <thead><tr><th>No. Invoice</th><th>Pasien</th><th>Tanggal</th><th class="text-end">Total</th><th>Status</th><th></th></tr></thead>
                    <tbody>
                        <?php foreach ($rows as $r): ?>
                            <tr>
                                <td><b><?= e($r['no_invoice']) ?></b></td>
                                <td><?= e($r['pasien']) ?></td>
                                <td><?= e(tgl($r['tanggal'])) ?></td>
                                <td class="text-end"><?= e(rupiah($r['total'])) ?></td>
                                <td><?= status_badge($r['status']) ?></td>
                                <td><a href="<?= e(url('billing', ['action' => 'detail', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rows): ?>
                            <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tagihan.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">Total: <?= $total ?> tagihan</small>
                <?= render_pagination('billing', $pg, ['status' => $filterStatus]) ?>
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card">
            <div class="card-header"><h3 class="card-title">Kunjungan Selesai Belum Ditagih</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <tbody>
                        <?php foreach ($belumDitagih as $b): ?>
                            <tr>
                                <td>
                                    <b><?= e($b['no_registrasi']) ?></b><br />
                                    <small class="text-muted"><?= e($b['pasien']) ?> — <?= e(tgl($b['tanggal'])) ?></small>
                                </td>
                                <td class="text-end">
                                    <a href="<?= e(url('billing', ['action' => 'buat', 'pendaftaran_id' => $b['id']])) ?>" class="btn btn-sm btn-success">
                                        <i class="bi bi-receipt"></i> Tagih
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$belumDitagih): ?>
                            <tr><td class="text-center text-muted py-3">Semua kunjungan sudah ditagih.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
