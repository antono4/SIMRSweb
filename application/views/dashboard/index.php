<div class="row g-3 mb-2">
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-teal"><i class="bi bi-people-fill"></i></div>
            <div class="stat-body">
                <span class="stat-label">Total Pasien</span>
                <p class="stat-value"><?php echo $totalPasien; ?></p>
                <a href="<?php echo base_url('index.php/pasien'); ?>" class="stat-link">Kelola pasien <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-blue"><i class="bi bi-clipboard2-pulse"></i></div>
            <div class="stat-body">
                <span class="stat-label">Kunjungan Hari Ini</span>
                <p class="stat-value"><?php echo $kunjunganHari; ?></p>
                <a href="<?php echo base_url('index.php/pendaftaran'); ?>" class="stat-link">Lihat pendaftaran <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-amber"><i class="bi bi-hourglass-split"></i></div>
            <div class="stat-body">
                <span class="stat-label">Antrian Menunggu</span>
                <p class="stat-value"><?php echo $antrianMenunggu; ?></p>
                <a href="<?php echo base_url('index.php/antrian'); ?>" class="stat-link">Proses antrian <i class="bi bi-arrow-right"></i></a>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="stat-card">
            <div class="stat-chip chip-rose"><i class="bi bi-receipt"></i></div>
            <div class="stat-body">
                <span class="stat-label">Tagihan Belum Lunas</span>
                <p class="stat-value"><?php echo $tagihanBelum ? 'Rp ' . number_format($tagihanBelum, 0, ',', '.') : 'Rp 0'; ?></p>
                <a href="<?php echo base_url('index.php/billing', ['status' => 'belum']); ?>" class="stat-link">Ke kasir <i class="bi bi-arrow-right"></i></a>
            </div>
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
                    <thead><tr><th>No. Registrasi</th><th>Pasien</th><th>Poli</th><th>Tanggal</th><th>Status</th></tr></thead>
                    <tbody>
                        <?php foreach ($terbaru ?? [] as $t): ?>
                            <tr>
                                <td><?php echo $t['no_registrasi']; ?></td>
                                <td><?php echo $t['pasien']; ?></td>
                                <td><?php echo $t['poli']; ?></td>
                                <td><?php echo date('d M Y H:i', strtotime($t['tanggal'])); ?></td>
                                <td><span class="badge text-bg-<?php echo $t['status'] === 'selesai' ? 'success' : ($t['status'] === 'menunggu' ? 'warning' : 'info'); ?>"><?php echo ucfirst($t['status']); ?></span></td>
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
                        <?php foreach ($perPoli ?? [] as $pp): ?>
                            <tr><td><?php echo $pp['nama']; ?></td><td class="text-end"><?php echo (int) $pp['jml']; ?></td></tr>
                        <?php endforeach; ?>
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
                        <?php foreach ($stokTipis ?? [] as $o): ?>
                            <tr>
                                <td><?php echo $o['nama']; ?></td>
                                <td class="text-end"><span class="badge text-bg-<?php echo $o['stok'] < 50 ? 'danger' : 'warning'; ?>"><?php echo (int) $o['stok'] . ' ' . $o['satuan']; ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card mb-4">
            <div class="card-header"><h3 class="card-title">Ringkasan</h3></div>
            <div class="card-body">
                <p class="mb-2"><i class="bi bi-person-badge me-2"></i>Dokter aktif: <b><?php echo $totalDokter; ?></b></p>
                <p class="mb-0"><i class="bi bi-capsule me-2"></i>Jenis obat: <b><?php echo $totalObat; ?></b></p>
            </div>
        </div>
    </div>
</div>
<script src="<?php echo base_url('assets/vendor/apexcharts/apexcharts.min.js'); ?>"></script>
<script>
const chart = new ApexCharts(document.querySelector("#chart-kunjungan"), {
    chart: { type: "area", height: 300, toolbar: { show: false }, fontFamily: "Plus Jakarta Sans, sans-serif" },
    series: [{ name: "Kunjungan", data: <?php echo json_encode($chartValues ?? []); ?> }],
    xaxis: { categories: <?php echo json_encode($chartLabels ?? []); ?> },
    colors: ["#0d9488"],
    dataLabels: { enabled: false },
    stroke: { curve: "smooth", width: 2.5 },
    fill: { type: "gradient", gradient: { opacityFrom: 0.35, opacityTo: 0.05 } },
    grid: { borderColor: "#e2e8f0", strokeDashArray: 4 },
    tooltip: { theme: "light" },
});
chart.render();
</script>
