<?php

declare(strict_types=1);

$pageTitle = 'Papan Antrian';
$db = db();

$poliId = (int) ($_GET['poli_id'] ?? 0);
$poliList = $db->query('SELECT id, kode, nama FROM poli ORDER BY nama')->fetchAll();

// Ambil seluruh antrian hari ini, kelompokkan per poli
$sql = "SELECT r.*, ps.nama AS pasien, ps.no_rm, pl.nama AS poli, pl.kode AS kode_poli, d.nama AS dokter
        FROM pendaftaran r
        JOIN pasien ps ON ps.id = r.pasien_id
        JOIN poli pl ON pl.id = r.poli_id
        LEFT JOIN dokter d ON d.id = r.dokter_id
        WHERE DATE(r.tanggal) = CURDATE() AND r.status != 'batal'";
$params = [];
if ($poliId) {
    $sql .= ' AND r.poli_id = ?';
    $params[] = $poliId;
}
$sql .= " ORDER BY FIELD(r.status, 'dipanggil', 'diperiksa', 'menunggu', 'selesai'), r.dipanggil_pada DESC, r.no_antrian";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$dipanggil = array_filter($rows, fn($r) => $r['status'] === 'dipanggil');
$diperiksa = array_filter($rows, fn($r) => $r['status'] === 'diperiksa');
$menunggu  = array_filter($rows, fn($r) => $r['status'] === 'menunggu');
$selesai   = array_filter($rows, fn($r) => $r['status'] === 'selesai');

function antrian_card(array $r, string $aksi = ''): string
{
    $html = '<div class="queue-card">'
        . '<div class="queue-num">' . e($r['no_antrian'] ?: '-') . '</div>'
        . '<div class="queue-info"><b>' . e($r['pasien']) . '</b><br /><small>' . e($r['poli']) . '</small></div>';
    if ($aksi !== '') {
        $html .= $aksi;
    }
    return $html . '</div>';
}

$btn = function (int $id, string $status, string $ikon, string $warna, string $title) {
    return '<form method="post" action="' . e(url('pendaftaran')) . '" class="ms-auto">'
        . csrf_field()
        . '<input type="hidden" name="status" value="' . $status . '" />'
        . '<input type="hidden" name="kembali" value="antrian" />'
        . '<button name="set_status" value="' . $id . '" class="btn btn-sm btn-' . $warna . '" title="' . e($title) . '">'
        . '<i class="bi ' . $ikon . '"></i></button></form>';
};

require __DIR__ . '/../../includes/header.php';
?>
<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <form method="get" action="<?php echo e(base_url("/")); ?>"index.php" class="d-flex gap-2">
        <input type="hidden" name="page" value="antrian" />
        <select name="poli_id" class="form-select form-select-sm" onchange="this.form.submit()">
            <option value="">Semua Poli</option>
            <?php foreach ($poliList as $p): ?>
                <option value="<?= (int) $p['id'] ?>" <?= $poliId === (int) $p['id'] ? 'selected' : '' ?>><?= e($p['nama']) ?></option>
            <?php endforeach; ?>
        </select>
    </form>
    <div class="d-flex gap-2">
        <a href="<?php echo e(base_url("")); ?>"display-antrian.php" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-tv"></i> Buka Display Antrian
        </a>
        <a href="<?= e(url('pendaftaran', ['action' => 'create'])) ?>" class="btn btn-sm btn-primary">
            <i class="bi bi-plus-lg"></i> Daftar Baru
        </a>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-primary bg-opacity-10">
                <h3 class="card-title mb-0"><i class="bi bi-megaphone me-1"></i> Dipanggil (<?= count($dipanggil) ?>)</h3>
            </div>
            <div class="card-body queue-col">
                <?php foreach ($dipanggil as $r): ?>
                    <?= antrian_card($r, $btn((int) $r['id'], 'diperiksa', 'bi-play-circle', 'info', 'Mulai periksa')) ?>
                <?php endforeach; ?>
                <?php if (!$dipanggil): ?><p class="text-muted small mb-0">Tidak ada antrian dipanggil.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-info bg-opacity-10">
                <h3 class="card-title mb-0"><i class="bi bi-clipboard2-pulse me-1"></i> Diperiksa (<?= count($diperiksa) ?>)</h3>
            </div>
            <div class="card-body queue-col">
                <?php foreach ($diperiksa as $r): ?>
                    <?= antrian_card($r, $btn((int) $r['id'], 'selesai', 'bi-check-circle', 'success', 'Selesaikan')) ?>
                <?php endforeach; ?>
                <?php if (!$diperiksa): ?><p class="text-muted small mb-0">Tidak ada pasien diperiksa.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-warning bg-opacity-10">
                <h3 class="card-title mb-0"><i class="bi bi-hourglass-split me-1"></i> Menunggu (<?= count($menunggu) ?>)</h3>
            </div>
            <div class="card-body queue-col">
                <?php foreach ($menunggu as $r): ?>
                    <?= antrian_card($r, $btn((int) $r['id'], 'dipanggil', 'bi-megaphone', 'warning', 'Panggil')) ?>
                <?php endforeach; ?>
                <?php if (!$menunggu): ?><p class="text-muted small mb-0">Antrian kosong.</p><?php endif; ?>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-md-6">
        <div class="card h-100">
            <div class="card-header bg-success bg-opacity-10">
                <h3 class="card-title mb-0"><i class="bi bi-check2-circle me-1"></i> Selesai (<?= count($selesai) ?>)</h3>
            </div>
            <div class="card-body queue-col">
                <?php foreach ($selesai as $r): ?>
                    <?= antrian_card($r) ?>
                <?php endforeach; ?>
                <?php if (!$selesai): ?><p class="text-muted small mb-0">Belum ada yang selesai.</p><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
