<?php

declare(strict_types=1);

$pageTitle = 'Dashboard';
$db = db();

$totalPasien    = (int) $db->query('SELECT COUNT(*) FROM pasien')->fetchColumn();
$totalDokter    = (int) $db->query('SELECT COUNT(*) FROM dokter WHERE aktif = 1')->fetchColumn();
$totalObat      = (int) $db->query('SELECT COUNT(*) FROM obat')->fetchColumn();
$kunjunganHari  = (int) $db->query('SELECT COUNT(*) FROM pendaftaran WHERE DATE(tanggal) = CURDATE()')->fetchColumn();
$antrianMenunggu = (int) $db->query("SELECT COUNT(*) FROM pendaftaran WHERE status = 'menunggu'")->fetchColumn();
$tagihanBelum   = (float) $db->query("SELECT COALESCE(SUM(total),0) FROM tagihan WHERE status = 'belum'")->fetchColumn();

// Kunjungan 7 hari terakhir
$chartStmt = $db->query(
    "SELECT DATE(tanggal) AS tgl, COUNT(*) AS jml
     FROM pendaftaran
     WHERE tanggal >= CURDATE() - INTERVAL 6 DAY
     GROUP BY DATE(tanggal)"
);
$chartData = [];
foreach ($chartStmt as $row) {
    $chartData[$row['tgl']] = (int) $row['jml'];
}
$chartLabels = [];
$chartValues = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i day"));
    $chartLabels[] = tgl($d);
    $chartValues[] = $chartData[$d] ?? 0;
}

// Kunjungan per poli (semua waktu)
$perPoli = $db->query(
    'SELECT pl.nama, COUNT(p.id) AS jml
     FROM pendaftaran p JOIN poli pl ON pl.id = p.poli_id
     GROUP BY pl.nama ORDER BY jml DESC'
)->fetchAll();

// Pendaftaran terbaru
$terbaru = $db->query(
    "SELECT p.no_registrasi, ps.nama AS pasien, pl.nama AS poli, p.tanggal, p.status
     FROM pendaftaran p
     JOIN pasien ps ON ps.id = p.pasien_id
     JOIN poli pl ON pl.id = p.poli_id
     ORDER BY p.tanggal DESC LIMIT 8"
)->fetchAll();

// Stok obat menipis
$stokTipis = $db->query('SELECT kode, nama, stok, satuan FROM obat WHERE stok < 100 ORDER BY stok ASC LIMIT 5')->fetchAll();

$extraJs = '<script src="/assets/vendor/apexcharts/apexcharts.min.js"></script>
<script>
const chart = new ApexCharts(document.querySelector("#chart-kunjungan"), {
    chart: { type: "area", height: 300, toolbar: { show: false } },
    series: [{ name: "Kunjungan", data: ' . json_encode($chartValues) . ' }],
    xaxis: { categories: ' . json_encode($chartLabels) . ' },
    colors: ["#0d6efd"],
    dataLabels: { enabled: false },
    stroke: { curve: "smooth" },
    fill: { type: "gradient", gradient: { opacityFrom: 0.5, opacityTo: 0.1 } },
});
chart.render();
</script>';

require __DIR__ . '/../../includes/header.php';
?>
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-primary">
            <div class="inner">
                <h3><?= $totalPasien ?></h3>
                <p>Total Pasien</p>
            </div>
            <i class="small-box-icon bi bi-people-fill"></i>
            <a href="<?= e(url('pasien')) ?>" class="small-box-footer">Lihat <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-success">
            <div class="inner">
                <h3><?= $kunjunganHari ?></h3>
                <p>Kunjungan Hari Ini</p>
            </div>
            <i class="small-box-icon bi bi-clipboard-plus"></i>
            <a href="<?= e(url('pendaftaran')) ?>" class="small-box-footer">Lihat <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-warning">
            <div class="inner">
                <h3><?= $antrianMenunggu ?></h3>
                <p>Antrian Menunggu</p>
            </div>
            <i class="small-box-icon bi bi-hourglass-split"></i>
            <a href="<?= e(url('pendaftaran')) ?>" class="small-box-footer">Lihat <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
    <div class="col-lg-3 col-6">
        <div class="small-box text-bg-danger">
            <div class="inner">
                <h3><?= rupiah($tagihanBelum) ?></h3>
                <p>Tagihan Belum Lunas</p>
            </div>
            <i class="small-box-icon bi bi-receipt"></i>
            <a href="<?= e(url('billing')) ?>" class="small-box-footer">Lihat <i class="bi bi-arrow-right-circle"></i></a>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Kunjungan 7 Hari Terakhir</h3></div>
            <div class="card-body"><div id="chart-kunjungan"></div></div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Pendaftaran Terbaru</h3></div>
            <div class="card-body p-0">
                <table class="table table-striped mb-0">
                    <thead>
                        <tr><th>No. Registrasi</th><th>Pasien</th><th>Poli</th><th>Tanggal</th><th>Status</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($terbaru as $t): ?>
                            <tr>
                                <td><?= e($t['no_registrasi']) ?></td>
                                <td><?= e($t['pasien']) ?></td>
                                <td><?= e($t['poli']) ?></td>
                                <td><?= e(tgl($t['tanggal'], true)) ?></td>
                                <td><?= status_badge($t['status']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Kunjungan per Poli</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Poli</th><th class="text-end">Jumlah</th></tr></thead>
                    <tbody>
                        <?php foreach ($perPoli as $pp): ?>
                            <tr><td><?= e($pp['nama']) ?></td><td class="text-end"><?= (int) $pp['jml'] ?></td></tr>
                        <?php endforeach; ?>
                        <?php if (!$perPoli): ?>
                            <tr><td colspan="2" class="text-center text-muted">Belum ada data</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Stok Obat Menipis</h3></div>
            <div class="card-body p-0">
                <table class="table mb-0">
                    <thead><tr><th>Obat</th><th class="text-end">Stok</th></tr></thead>
                    <tbody>
                        <?php foreach ($stokTipis as $o): ?>
                            <tr>
                                <td><?= e($o['nama']) ?></td>
                                <td class="text-end">
                                    <span class="badge text-bg-<?= $o['stok'] < 50 ? 'danger' : 'warning' ?>">
                                        <?= (int) $o['stok'] ?> <?= e($o['satuan']) ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$stokTipis): ?>
                            <tr><td colspan="2" class="text-center text-muted">Stok aman</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Ringkasan</h3></div>
            <div class="card-body">
                <p class="mb-2"><i class="bi bi-person-badge me-2"></i>Dokter aktif: <b><?= $totalDokter ?></b></p>
                <p class="mb-0"><i class="bi bi-capsule me-2"></i>Jenis obat: <b><?= $totalObat ?></b></p>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
