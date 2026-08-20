<?php

declare(strict_types=1);

$pageTitle = 'Surat Keterangan';
$db = db();
$action = $_GET['action'] ?? 'list';

// ---------- HALAMAN CETAK (tanpa layout admin) ----------
if ($action === 'cetak') {
    $stmt = $db->prepare(
        'SELECT s.*, rm.tanggal AS tgl_periksa, rm.diagnosis,
                ps.nama AS pasien, ps.no_rm, ps.jenis_kelamin, ps.tanggal_lahir, ps.pekerjaan, ps.alamat AS alamat_pasien,
                d.nama AS dokter
         FROM surat_keterangan s
         JOIN rekam_medis rm ON rm.id = s.rekam_medis_id
         JOIN pasien ps ON ps.id = rm.pasien_id
         LEFT JOIN dokter d ON d.id = rm.dokter_id
         WHERE s.id = ?'
    );
    $stmt->execute([(int) $_GET['id']]);
    $s = $stmt->fetch();
    if (!$s) {
        flash('danger', 'Surat tidak ditemukan.');
        redirect(url('surat'));
    }
    $judul = $s['jenis'] === 'sakit' ? 'SURAT KETERANGAN SAKIT' : 'SURAT RUJUKAN';
    ?>
    <!doctype html>
    <html lang="id">
    <head>
        <meta charset="utf-8" />
        <title><?= e($judul) ?> <?= e($s['no_surat']) ?></title>
        <link rel="stylesheet" href="<?php echo e(base_url("")); ?>"assets/vendor/fonts/plus-jakarta-sans.css" />
        <style>
            body { font-family: 'Plus Jakarta Sans', serif; color: #0f172a; margin: 0; background: #e2e8f0; }
            .paper { width: 148mm; min-height: 200mm; margin: 20px auto; background: #fff; padding: 16mm 18mm; box-shadow: 0 4px 20px rgba(0,0,0,0.15); }
            .kop { display: flex; align-items: center; gap: 14px; border-bottom: 3px double #0f172a; padding-bottom: 12px; }
            .kop .logo { width: 52px; height: 52px; border-radius: 10px; background: linear-gradient(135deg,#14b8a6,#0f766e); display: grid; place-items: center; }
            .kop .logo svg { width: 30px; height: 30px; }
            .kop h1 { font-size: 17pt; margin: 0; font-weight: 800; }
            .kop p { margin: 2px 0 0; font-size: 8.5pt; color: #475569; }
            .judul { text-align: center; margin: 26px 0 6px; font-size: 13pt; font-weight: 800; letter-spacing: 0.06em; text-decoration: underline; }
            .nomor { text-align: center; font-size: 9pt; color: #475569; margin-bottom: 24px; }
            .isi { font-size: 10.5pt; line-height: 1.75; }
            table.data { margin: 12px 0; }
            table.data td { padding: 2px 10px 2px 0; vertical-align: top; font-size: 10.5pt; }
            .ttd { margin-top: 40px; display: flex; justify-content: flex-end; }
            .ttd .box { text-align: center; width: 220px; }
            .ttd .nama { margin-top: 64px; font-weight: 700; text-decoration: underline; }
            .toolbar { text-align: center; margin: 16px; }
            .toolbar a, .toolbar button { font-family: inherit; font-size: 10pt; padding: 8px 18px; border-radius: 8px; border: 1px solid #0d9488; background: #0d9488; color: #fff; cursor: pointer; text-decoration: none; margin: 0 4px; }
            .toolbar .kembali { background: #fff; color: #0d9488; }
            @media print {
                body { background: #fff; }
                .paper { margin: 0; box-shadow: none; width: auto; }
                .toolbar { display: none; }
            }
        </style>
    </head>
    <body>
        <div class="toolbar">
            <button onclick="window.print()">Cetak</button>
            <a class="kembali" href="<?= e(url('rekam-medis', ['action' => 'detail', 'id' => $s['rekam_medis_id']])) ?>">Kembali</a>
        </div>
        <div class="paper">
            <div class="kop">
                <div class="logo">
                    <svg viewBox="0 0 24 24" fill="none"><path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5V4z" fill="#fff"/></svg>
                </div>
                <div>
                    <h1><?= e(nama_rs()) ?></h1>
                    <p><?= e(setting('alamat_rs')) ?> &middot; Telp. <?= e(setting('telepon_rs')) ?></p>
                </div>
            </div>
            <div class="judul"><?= e($judul) ?></div>
            <div class="nomor">Nomor: <?= e($s['no_surat']) ?></div>
            <div class="isi">
                <p>Yang bertanda tangan di bawah ini menerangkan bahwa:</p>
                <table class="data">
                    <tr><td>Nama</td><td>:</td><td><b><?= e($s['pasien']) ?></b></td></tr>
                    <tr><td>No. RM</td><td>:</td><td><?= e($s['no_rm']) ?></td></tr>
                    <tr><td>Jenis Kelamin</td><td>:</td><td><?= $s['jenis_kelamin'] === 'L' ? 'Laki-laki' : 'Perempuan' ?></td></tr>
                    <tr><td>Tanggal Lahir</td><td>:</td><td><?= e(tgl($s['tanggal_lahir'])) ?> (<?= e(umur($s['tanggal_lahir'])) ?>)</td></tr>
                    <?php if ($s['pekerjaan']): ?><tr><td>Pekerjaan</td><td>:</td><td><?= e($s['pekerjaan']) ?></td></tr><?php endif; ?>
                    <tr><td>Alamat</td><td>:</td><td><?= e($s['alamat_pasien'] ?: '-') ?></td></tr>
                    <tr><td>Diagnosis</td><td>:</td><td><?= e(mb_strimwidth((string) $s['diagnosis'], 0, 90, '...')) ?></td></tr>
                </table>
                <p><?= e($s['isi']) ?></p>
                <p>Demikian surat keterangan ini dibuat untuk dipergunakan sebagaimana mestinya.</p>
            </div>
            <div class="ttd">
                <div class="box">
                    <?= e(setting('alamat_rs') ? preg_replace('/.*,\s*/', '', setting('alamat_rs')) : '') ?>, <?= e(tgl((string) $s['created_at'])) ?><br />
                    Dokter Pemeriksa
                    <div class="nama"><?= e($s['dokter'] ?: '(________________)') ?></div>
                </div>
            </div>
        </div>
    </body>
    </html>
    <?php
    return;
}

// ---------- DAFTAR ----------
$stmt = $db->query(
    'SELECT s.*, ps.nama AS pasien, ps.no_rm, d.nama AS dokter
     FROM surat_keterangan s
     JOIN rekam_medis rm ON rm.id = s.rekam_medis_id
     JOIN pasien ps ON ps.id = rm.pasien_id
     LEFT JOIN dokter d ON d.id = rm.dokter_id
     ORDER BY s.created_at DESC LIMIT 100'
);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header"><h3 class="card-title mb-0">Arsip Surat Keterangan</h3></div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>No. Surat</th><th>Jenis</th><th>Pasien</th><th>Dokter</th><th>Tanggal</th><th style="width:90px">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><b><?= e($r['no_surat']) ?></b></td>
                        <td><span class="badge text-bg-<?= $r['jenis'] === 'sakit' ? 'info' : 'warning' ?>"><?= $r['jenis'] === 'sakit' ? 'Sakit' : 'Rujukan' ?></span></td>
                        <td><?= e($r['no_rm'] . ' - ' . $r['pasien']) ?></td>
                        <td><?= e($r['dokter'] ?: '-') ?></td>
                        <td><?= e(tgl($r['created_at'], true)) ?></td>
                        <td><a href="<?= e(url('surat', ['action' => 'cetak', 'id' => $r['id']])) ?>" target="_blank" class="btn btn-sm btn-outline-primary"><i class="bi bi-printer"></i> Cetak</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada surat dibuat. Buat dari halaman detail rekam medis.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
