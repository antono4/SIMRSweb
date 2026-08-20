<?php

declare(strict_types=1);

require_role('admin', 'petugas');

$pageTitle = 'Laporan';
$db = db();

$dari   = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['dari'] ?? '')) ? $_GET['dari'] : date('Y-m-01');
$sampai = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) ($_GET['sampai'] ?? '')) ? $_GET['sampai'] : date('Y-m-d');
$range  = [$dari . ' 00:00:00', $sampai . ' 23:59:59'];

// ---------- Ringkasan ----------
$stmtK = $db->prepare('SELECT COUNT(*) FROM pendaftaran WHERE tanggal BETWEEN ? AND ?');
$stmtK->execute($range);
$kunjungan = (int) $stmtK->fetchColumn();

$stmtP = $db->prepare("SELECT COALESCE(SUM(total),0) FROM tagihan WHERE status = 'lunas' AND dibayar_pada BETWEEN ? AND ?");
$stmtP->execute($range);
$pendapatan = (float) $stmtP->fetchColumn();

$stmtB = $db->prepare('SELECT COUNT(*) FROM pasien WHERE created_at BETWEEN ? AND ?');
$stmtB->execute($range);
$pasienBaru = (int) $stmtB->fetchColumn();

$stmtO = $db->prepare(
    'SELECT COALESCE(SUM(rs.jumlah),0) FROM resep rs
     JOIN rekam_medis rm ON rm.id = rs.rekam_medis_id
     WHERE rm.tanggal BETWEEN ? AND ?'
);
$stmtO->execute($range);
$obatKeluar = (int) $stmtO->fetchColumn();

// ---------- Kunjungan per hari ----------
$perHari = $db->prepare(
    "SELECT DATE(r.tanggal) AS tgl, COUNT(*) AS jml,
            COALESCE(SUM(CASE WHEN t.status = 'lunas' THEN t.total ELSE 0 END),0) AS pendapatan
     FROM pendaftaran r
     LEFT JOIN tagihan t ON t.pendaftaran_id = r.id
     WHERE r.tanggal BETWEEN ? AND ?
     GROUP BY DATE(r.tanggal) ORDER BY tgl"
);
$perHari->execute($range);
$rowsHari = $perHari->fetchAll();

// ---------- Per poli ----------
$perPoli = $db->prepare(
    "SELECT pl.nama, COUNT(r.id) AS kunjungan,
            COALESCE(SUM(CASE WHEN t.status = 'lunas' THEN t.total ELSE 0 END),0) AS pendapatan
     FROM pendaftaran r
     JOIN poli pl ON pl.id = r.poli_id
     LEFT JOIN tagihan t ON t.pendaftaran_id = r.id
     WHERE r.tanggal BETWEEN ? AND ?
     GROUP BY pl.nama ORDER BY kunjungan DESC"
);
$perPoli->execute($range);
$rowsPoli = $perPoli->fetchAll();

// ---------- Obat terlaris ----------
$obatTerlaris = $db->prepare(
    'SELECT o.nama, o.satuan, SUM(rs.jumlah) AS jml, SUM(rs.jumlah * o.harga) AS nilai
     FROM resep rs
     JOIN obat o ON o.id = rs.obat_id
     JOIN rekam_medis rm ON rm.id = rs.rekam_medis_id
     WHERE rm.tanggal BETWEEN ? AND ?
     GROUP BY o.id ORDER BY jml DESC LIMIT 10'
);
$obatTerlaris->execute($range);
$rowsObat = $obatTerlaris->fetchAll();

// ---------- Ekspor CSV ----------
if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="laporan-' . $dari . '-' . $sampai . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['LAPORAN ' . nama_rs(), "$dari s/d $sampai"]);
    fputcsv($out, []);
    fputcsv($out, ['Total Kunjungan', $kunjungan]);
    fputcsv($out, ['Pendapatan (lunas)', $pendapatan]);
    fputcsv($out, ['Pasien Baru', $pasienBaru]);
    fputcsv($out, ['Obat Keluar (item)', $obatKeluar]);
    fputcsv($out, []);
    fputcsv($out, ['KUNJUNGAN PER HARI']);
    fputcsv($out, ['Tanggal', 'Kunjungan', 'Pendapatan']);
    foreach ($rowsHari as $r) {
        fputcsv($out, [$r['tgl'], $r['jml'], $r['pendapatan']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['REKAP PER POLI']);
    fputcsv($out, ['Poli', 'Kunjungan', 'Pendapatan']);
    foreach ($rowsPoli as $r) {
        fputcsv($out, [$r['nama'], $r['kunjungan'], $r['pendapatan']]);
    }
    fputcsv($out, []);
    fputcsv($out, ['OBAT TERLARIS']);
    fputcsv($out, ['Obat', 'Jumlah', 'Nilai']);
    foreach ($rowsObat as $r) {
        fputcsv($out, [$r['nama'], $r['jml'] . ' ' . $r['satuan'], $r['nilai']]);
    }
    fclose($out);
    exit;
}

require __DIR__ . '/../../includes/header.php';
?>
<div class="card mb-3">
    <div class="card-body">
        <form method="get" action="<?= e(base_url("index.php")) ?>" class="d-flex align-items-end gap-2 flex-wrap">
            <input type="hidden" name="page" value="laporan" />
            <div>
                <label class="form-label mb-1">Dari Tanggal</label>
                <input type="date" name="dari" class="form-control form-control-sm" value="<?= e($dari) ?>" />
            </div>
            <div>
                <label class="form-label mb-1">Sampai Tanggal</label>
                <input type="date" name="sampai" class="form-control form-control-sm" value="<?= e($sampai) ?>" />
            </div>
            <button class="btn btn-sm btn-primary"><i class="bi bi-funnel"></i> Terapkan</button>
            <a href="<?= e(url('laporan', ['dari' => $dari, 'sampai' => $sampai, 'export' => 'csv'])) ?>" class="btn btn-sm btn-outline-success">
                <i class="bi bi-file-earmark-excel"></i> Ekspor CSV
            </a>
            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="window.print()"><i class="bi bi-printer"></i> Cetak</button>
        </form>
    </div>
</div>

<div class="row g-3 mb-2">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-blue"><i class="bi bi-clipboard2-pulse"></i></div>
            <div class="stat-body"><span class="stat-label">Total Kunjungan</span><p class="stat-value"><?= $kunjungan ?></p></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-teal"><i class="bi bi-cash-stack"></i></div>
            <div class="stat-body"><span class="stat-label">Pendapatan (Lunas)</span><p class="stat-value"><?= rupiah($pendapatan) ?></p></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-amber"><i class="bi bi-person-plus"></i></div>
            <div class="stat-body"><span class="stat-label">Pasien Baru</span><p class="stat-value"><?= $pasienBaru ?></p></div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-rose"><i class="bi bi-capsule"></i></div>
            <div class="stat-body"><span class="stat-label">Obat Keluar (item)</span><p class="stat-value"><?= $obatKeluar ?></p></div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Kunjungan & Pendapatan per Hari</h3></div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Tanggal</th><th class="text-end">Kunjungan</th><th class="text-end">Pendapatan</th></tr></thead>
                    <tbody>
                        <?php foreach ($rowsHari as $r): ?>
                            <tr>
                                <td><?= e(tgl($r['tgl'])) ?></td>
                                <td class="text-end"><?= (int) $r['jml'] ?></td>
                                <td class="text-end"><?= e(rupiah($r['pendapatan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rowsHari): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data pada rentang ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    <div class="col-lg-6">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Rekap per Poli</h3></div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Poli</th><th class="text-end">Kunjungan</th><th class="text-end">Pendapatan</th></tr></thead>
                    <tbody>
                        <?php foreach ($rowsPoli as $r): ?>
                            <tr>
                                <td><?= e($r['nama']) ?></td>
                                <td class="text-end"><?= (int) $r['kunjungan'] ?></td>
                                <td class="text-end"><?= e(rupiah($r['pendapatan'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rowsPoli): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada data.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Obat Terlaris</h3></div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-striped mb-0">
                    <thead><tr><th>Obat</th><th class="text-end">Jumlah Keluar</th><th class="text-end">Nilai</th></tr></thead>
                    <tbody>
                        <?php foreach ($rowsObat as $r): ?>
                            <tr>
                                <td><?= e($r['nama']) ?></td>
                                <td class="text-end"><?= (int) $r['jml'] ?> <?= e($r['satuan']) ?></td>
                                <td class="text-end"><?= e(rupiah($r['nilai'])) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$rowsObat): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">Tidak ada resep pada rentang ini.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
