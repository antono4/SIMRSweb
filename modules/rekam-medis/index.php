<?php

declare(strict_types=1);

$pageTitle = 'Rekam Medis';
$db = db();
$action = $_GET['action'] ?? 'list';

// ---------- SIMPAN REKAM MEDIS + RESEP ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    verify_csrf();
    $pendaftaranId = (int) $_POST['pendaftaran_id'];

    $stmt = $db->prepare(
        'SELECT r.*, r.pasien_id AS pid FROM pendaftaran r WHERE r.id = ?'
    );
    $stmt->execute([$pendaftaranId]);
    $reg = $stmt->fetch();

    if (!$reg) {
        flash('danger', 'Data pendaftaran tidak ditemukan.');
        redirect(url('pendaftaran'));
    }

    $obatIds   = $_POST['obat_id'] ?? [];
    $jumlahs   = $_POST['jumlah'] ?? [];
    $aturans   = $_POST['aturan_pakai'] ?? [];

    try {
        $db->beginTransaction();

        $db->prepare(
            'INSERT INTO rekam_medis
             (pendaftaran_id, pasien_id, dokter_id, anamnesa, tekanan_darah, suhu, berat_badan, tinggi_badan,
              pemeriksaan_fisik, diagnosis, tindakan, biaya_tindakan, catatan)
             VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)'
        )->execute([
            $pendaftaranId,
            $reg['pasien_id'],
            $reg['dokter_id'],
            trim((string) $_POST['anamnesa']) ?: null,
            trim((string) $_POST['tekanan_darah']) ?: null,
            ($_POST['suhu'] ?? '') !== '' ? (float) $_POST['suhu'] : null,
            ($_POST['berat_badan'] ?? '') !== '' ? (float) $_POST['berat_badan'] : null,
            ($_POST['tinggi_badan'] ?? '') !== '' ? (float) $_POST['tinggi_badan'] : null,
            trim((string) $_POST['pemeriksaan_fisik']) ?: null,
            trim((string) $_POST['diagnosis']) ?: null,
            trim((string) $_POST['tindakan']) ?: null,
            max(0, (float) ($_POST['biaya_tindakan'] ?? 0)),
            trim((string) $_POST['catatan']) ?: null,
        ]);
        $rmId = (int) $db->lastInsertId();

        foreach ($obatIds as $i => $obatId) {
            $obatId = (int) $obatId;
            $jumlah = max(0, (int) ($jumlahs[$i] ?? 0));
            if (!$obatId || !$jumlah) {
                continue;
            }

            // Kunci baris stok agar aman dari pengurangan ganda
            $stokStmt = $db->prepare('SELECT stok, nama FROM obat WHERE id = ? FOR UPDATE');
            $stokStmt->execute([$obatId]);
            $obat = $stokStmt->fetch();
            if (!$obat || (int) $obat['stok'] < $jumlah) {
                throw new RuntimeException('Stok ' . ($obat['nama'] ?? 'obat') . ' tidak mencukupi.');
            }

            $db->prepare('UPDATE obat SET stok = stok - ? WHERE id = ?')->execute([$jumlah, $obatId]);
            $db->prepare('INSERT INTO resep (rekam_medis_id, obat_id, jumlah, aturan_pakai) VALUES (?,?,?,?)')
               ->execute([$rmId, $obatId, $jumlah, trim((string) ($aturans[$i] ?? '')) ?: null]);
            $db->prepare("INSERT INTO mutasi_stok (obat_id, tipe, jumlah, keterangan, referensi, user_id) VALUES (?, 'keluar', ?, ?, ?, ?)")
               ->execute([$obatId, $jumlah, 'Resep untuk ' . $reg['no_registrasi'], 'RM-' . $rmId, current_user()['id'] ?? null]);
        }

        $db->prepare("UPDATE pendaftaran SET status = 'selesai' WHERE id = ?")->execute([$pendaftaranId]);

        $db->commit();
        flash('success', 'Rekam medis berhasil disimpan dan stok obat diperbarui.');
    } catch (Throwable $e) {
        $db->rollBack();
        flash('danger', 'Gagal menyimpan rekam medis: ' . $e->getMessage());
        redirect(url('rekam-medis', ['action' => 'create', 'pendaftaran_id' => $pendaftaranId]));
    }
    redirect(url('rekam-medis', ['action' => 'detail', 'id' => $rmId ?? 0]));
}

// ---------- BUAT SURAT KETERANGAN ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['buat_surat'])) {
    verify_csrf();
    $rmId  = (int) $_POST['rekam_medis_id'];
    $jenis = in_array($_POST['jenis'] ?? '', ['sakit', 'rujukan'], true) ? $_POST['jenis'] : 'sakit';

    $isi = '';
    if ($jenis === 'sakit') {
        $hari = max(1, (int) ($_POST['istirahat_hari'] ?? 1));
        $isi = 'Istirahat sakit selama ' . $hari . ' hari, terhitung mulai ' . tgl(date('Y-m-d')) . '.';
    } else {
        $tujuan = trim((string) ($_POST['rujukan_tujuan'] ?? ''));
        if ($tujuan === '') {
            flash('danger', 'Tujuan rujukan wajib diisi.');
            redirect(url('rekam-medis', ['action' => 'detail', 'id' => $rmId]));
        }
        $isi = 'Dirujuk ke ' . $tujuan . ' untuk pemeriksaan/penanganan lebih lanjut.';
    }

    $noSurat = next_number('surat_keterangan', 'no_surat', 'SK-' . date('Ymd') . '-', 3);
    $db->prepare('INSERT INTO surat_keterangan (no_surat, rekam_medis_id, jenis, isi) VALUES (?,?,?,?)')
       ->execute([$noSurat, $rmId, $jenis, $isi]);

    redirect(url('surat', ['action' => 'cetak', 'id' => (int) $db->lastInsertId()]));
}

// ---------- FORM CREATE ----------
if ($action === 'create') {
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
        redirect(url('pendaftaran'));
    }

    $obatList = $db->query('SELECT id, kode, nama, satuan, stok, harga FROM obat WHERE stok > 0 ORDER BY nama')->fetchAll();
    $tindakanList = $db->query('SELECT id, nama, tarif FROM tindakan ORDER BY nama')->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <form method="post" action="<?= e(url('rekam-medis')) ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="pendaftaran_id" value="<?= (int) $reg['id'] ?>" />

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Informasi Kunjungan</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3"><b>No. Registrasi:</b> <?= e($reg['no_registrasi']) ?></div>
                    <div class="col-md-3"><b>Pasien:</b> <?= e($reg['no_rm'] . ' - ' . $reg['pasien']) ?></div>
                    <div class="col-md-3"><b>Poli:</b> <?= e($reg['poli']) ?></div>
                    <div class="col-md-3"><b>Dokter:</b> <?= e($reg['dokter'] ?: '-') ?></div>
                </div>
                <div class="mt-2"><b>Keluhan:</b> <?= e($reg['keluhan'] ?: '-') ?></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header"><h3 class="card-title">Pemeriksaan</h3></div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tekanan Darah (mmHg)</label>
                        <input type="text" name="tekanan_darah" class="form-control" placeholder="120/80" />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Suhu (°C)</label>
                        <input type="number" name="suhu" class="form-control" step="0.1" min="30" max="45" placeholder="36.5" />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Berat Badan (kg)</label>
                        <input type="number" name="berat_badan" class="form-control" step="0.1" min="0" />
                    </div>
                    <div class="col-md-3 mb-3">
                        <label class="form-label">Tinggi Badan (cm)</label>
                        <input type="number" name="tinggi_badan" class="form-control" step="0.1" min="0" />
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label">Anamnesa</label>
                    <textarea name="anamnesa" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label">Pemeriksaan Fisik</label>
                    <textarea name="pemeriksaan_fisik" class="form-control" rows="2"></textarea>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Diagnosis <span class="text-danger">*</span></label>
                        <textarea name="diagnosis" class="form-control" rows="2" required></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Biaya Tindakan (Rp)</label>
                        <input type="number" name="biaya_tindakan" id="biaya_tindakan" class="form-control" min="0" step="1000" value="0" />
                        <div class="form-text">Terisi otomatis dari tindakan yang dipilih (bisa diubah manual).</div>
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-8 mb-3">
                        <label class="form-label">Tindakan <small class="text-muted">(pilih dari master tarif)</small></label>
                        <div id="daftar-tindakan"></div>
                        <button type="button" class="btn btn-sm btn-outline-primary mt-1" onclick="tambahTindakan()"><i class="bi bi-plus-lg"></i> Tambah Tindakan</button>
                        <textarea name="tindakan" id="tindakan_teks" class="form-control mt-2" rows="1" style="display:none"></textarea>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label class="form-label">Catatan</label>
                        <textarea name="catatan" class="form-control" rows="2"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h3 class="card-title mb-0">Resep Obat</h3>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="tambahBaris()"><i class="bi bi-plus-lg"></i> Tambah Obat</button>
            </div>
            <div class="card-body p-0">
                <table class="table mb-0" id="tabel-resep">
                    <thead><tr><th style="width:45%">Obat</th><th style="width:15%">Jumlah</th><th>Aturan Pakai</th><th style="width:60px"></th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>

        <div class="mb-4">
            <button type="submit" name="simpan" value="1" class="btn btn-primary"><i class="bi bi-save"></i> Simpan Rekam Medis</button>
            <a href="<?= e(url('pendaftaran')) ?>" class="btn btn-secondary">Batal</a>
        </div>
    </form>

    <template id="tpl-baris">
        <tr>
            <td>
                <select name="obat_id[]" class="form-select form-select-sm">
                    <option value="">- Pilih Obat -</option>
                    <?php foreach ($obatList as $o): ?>
                        <option value="<?= (int) $o['id'] ?>">
                            <?= e($o['nama'] . ' (stok: ' . $o['stok'] . ' ' . $o['satuan'] . ')') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </td>
            <td><input type="number" name="jumlah[]" class="form-control form-control-sm" min="1" value="1" /></td>
            <td><input type="text" name="aturan_pakai[]" class="form-control form-control-sm" placeholder="3x1 sehari" /></td>
            <td><button type="button" class="btn btn-sm btn-outline-danger" onclick="this.closest('tr').remove()"><i class="bi bi-x"></i></button></td>
        </tr>
    </template>
    <template id="tpl-tindakan">
        <div class="input-group input-group-sm mb-1 baris-tindakan">
            <select class="form-select pilih-tindakan">
                <option value="">- Pilih Tindakan -</option>
                <?php foreach ($tindakanList as $t): ?>
                    <option value="<?= (int) $t['id'] ?>" data-nama="<?= e($t['nama']) ?>" data-tarif="<?= (int) $t['tarif'] ?>">
                        <?= e($t['nama'] . ' — ' . rupiah($t['tarif'])) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button type="button" class="btn btn-outline-danger" onclick="this.closest('.baris-tindakan').remove(); hitungTindakan();"><i class="bi bi-x"></i></button>
        </div>
    </template>
    <script>
        function tambahBaris() {
            const tpl = document.getElementById('tpl-baris');
            document.querySelector('#tabel-resep tbody').appendChild(tpl.content.cloneNode(true));
        }
        tambahBaris();

        const tplTindakan = document.getElementById('tpl-tindakan');
        const wadahTindakan = document.getElementById('daftar-tindakan');

        function tambahTindakan() {
            const node = tplTindakan.content.cloneNode(true);
            node.querySelector('.pilih-tindakan').addEventListener('change', hitungTindakan);
            wadahTindakan.appendChild(node);
        }

        function hitungTindakan() {
            let total = 0;
            const nama = [];
            wadahTindakan.querySelectorAll('.pilih-tindakan').forEach(function (sel) {
                const opt = sel.selectedOptions[0];
                if (opt && opt.value) {
                    total += parseFloat(opt.dataset.tarif) || 0;
                    nama.push(opt.dataset.nama);
                }
            });
            document.getElementById('biaya_tindakan').value = total;
            document.getElementById('tindakan_teks').value = nama.join('; ');
        }

        tambahTindakan();
    </script>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DETAIL ----------
if ($action === 'detail') {
    $stmt = $db->prepare(
        'SELECT rm.*, r.no_registrasi, ps.nama AS pasien, ps.no_rm, pl.nama AS poli, d.nama AS dokter
         FROM rekam_medis rm
         JOIN pendaftaran r ON r.id = rm.pendaftaran_id
         JOIN pasien ps ON ps.id = rm.pasien_id
         JOIN poli pl ON pl.id = r.poli_id
         LEFT JOIN dokter d ON d.id = rm.dokter_id
         WHERE rm.id = ?'
    );
    $stmt->execute([(int) $_GET['id']]);
    $rm = $stmt->fetch();
    if (!$rm) {
        flash('danger', 'Rekam medis tidak ditemukan.');
        redirect(url('rekam-medis'));
    }

    $resepStmt = $db->prepare(
        'SELECT rs.*, o.nama AS obat, o.satuan, o.harga FROM resep rs JOIN obat o ON o.id = rs.obat_id WHERE rs.rekam_medis_id = ?'
    );
    $resepStmt->execute([(int) $rm['id']]);
    $reseps = $resepStmt->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card mb-3">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h3 class="card-title mb-0">Rekam Medis <?= e($rm['no_registrasi']) ?></h3>
            <div class="d-flex gap-1">
                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#modalSurat">
                    <i class="bi bi-file-earmark-text"></i> Buat Surat
                </button>
                <a href="<?= e(url('billing', ['action' => 'buat', 'pendaftaran_id' => $rm['pendaftaran_id']])) ?>" class="btn btn-sm btn-success">
                    <i class="bi bi-receipt"></i> Buat Tagihan
                </a>
            </div>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-3"><b>Pasien:</b> <?= e($rm['no_rm'] . ' - ' . $rm['pasien']) ?></div>
                <div class="col-md-3"><b>Poli:</b> <?= e($rm['poli']) ?></div>
                <div class="col-md-3"><b>Dokter:</b> <?= e($rm['dokter'] ?: '-') ?></div>
                <div class="col-md-3"><b>Tanggal:</b> <?= e(tgl($rm['tanggal'], true)) ?></div>
            </div>
            <div class="row mb-3">
                <div class="col-md-3"><b>TD:</b> <?= e($rm['tekanan_darah'] ?? '-') ?> mmHg</div>
                <div class="col-md-3"><b>Suhu:</b> <?= e((string) ($rm['suhu'] ?? '-')) ?> °C</div>
                <div class="col-md-3"><b>BB:</b> <?= e((string) ($rm['berat_badan'] ?? '-')) ?> kg</div>
                <div class="col-md-3"><b>TB:</b> <?= e((string) ($rm['tinggi_badan'] ?? '-')) ?> cm</div>
            </div>
            <table class="table table-bordered">
                <tr><th style="width:180px">Anamnesa</th><td><?= nl2br(e($rm['anamnesa'] ?? '-')) ?></td></tr>
                <tr><th>Pemeriksaan Fisik</th><td><?= nl2br(e($rm['pemeriksaan_fisik'] ?? '-')) ?></td></tr>
                <tr><th>Diagnosis</th><td><b><?= nl2br(e($rm['diagnosis'] ?? '-')) ?></b></td></tr>
                <tr><th>Tindakan</th><td><?= nl2br(e($rm['tindakan'] ?? '-')) ?></td></tr>
                <tr><th>Biaya Tindakan</th><td><?= e(rupiah($rm['biaya_tindakan'])) ?></td></tr>
                <tr><th>Catatan</th><td><?= nl2br(e($rm['catatan'] ?? '-')) ?></td></tr>
            </table>

            <h5 class="mt-4">Resep Obat</h5>
            <table class="table table-striped">
                <thead><tr><th>Obat</th><th>Jumlah</th><th>Aturan Pakai</th><th class="text-end">Subtotal</th></tr></thead>
                <tbody>
                    <?php $totalObat = 0; foreach ($reseps as $rs): $sub = $rs['jumlah'] * $rs['harga']; $totalObat += $sub; ?>
                        <tr>
                            <td><?= e($rs['obat']) ?></td>
                            <td><?= (int) $rs['jumlah'] ?> <?= e($rs['satuan']) ?></td>
                            <td><?= e($rs['aturan_pakai'] ?: '-') ?></td>
                            <td class="text-end"><?= e(rupiah($sub)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$reseps): ?>
                        <tr><td colspan="4" class="text-center text-muted">Tanpa resep obat.</td></tr>
                    <?php endif; ?>
                </tbody>
                <?php if ($reseps): ?>
                    <tfoot><tr><th colspan="3" class="text-end">Total Obat</th><th class="text-end"><?= e(rupiah($totalObat)) ?></th></tr></tfoot>
                <?php endif; ?>
            </table>
        </div>
        <div class="card-footer">
            <a href="<?= e(url('rekam-medis')) ?>" class="btn btn-secondary">Kembali</a>
        </div>
    </div>

    <div class="modal fade" id="modalSurat" tabindex="-1">
        <div class="modal-dialog">
            <form method="post" action="<?= e(url('rekam-medis')) ?>" class="modal-content">
                <?= csrf_field() ?>
                <input type="hidden" name="rekam_medis_id" value="<?= (int) $rm['id'] ?>" />
                <div class="modal-header">
                    <h5 class="modal-title">Buat Surat Keterangan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Jenis Surat</label>
                        <select name="jenis" id="surat-jenis" class="form-select">
                            <option value="sakit">Surat Keterangan Sakit (istirahat)</option>
                            <option value="rujukan">Surat Rujukan</option>
                        </select>
                    </div>
                    <div class="mb-3" id="wrap-istirahat">
                        <label class="form-label">Istirahat (hari)</label>
                        <input type="number" name="istirahat_hari" class="form-control" min="1" value="1" />
                    </div>
                    <div class="mb-3" id="wrap-rujukan" style="display:none">
                        <label class="form-label">Tujuan Rujukan</label>
                        <input type="text" name="rujukan_tujuan" class="form-control" placeholder="Contoh: RSUP Dr. Sardjito — Poli Jantung" />
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" name="buat_surat" value="1" class="btn btn-primary"><i class="bi bi-printer"></i> Buat & Cetak</button>
                </div>
            </form>
        </div>
    </div>
    <script>
        document.getElementById('surat-jenis').addEventListener('change', function () {
            document.getElementById('wrap-istirahat').style.display = this.value === 'sakit' ? '' : 'none';
            document.getElementById('wrap-rujukan').style.display = this.value === 'rujukan' ? '' : 'none';
        });
    </script>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- RIWAYAT PASIEN ----------
if ($action === 'riwayat') {
    $pasienId = (int) ($_GET['pasien_id'] ?? 0);
    $stmt = $db->prepare('SELECT * FROM pasien WHERE id = ?');
    $stmt->execute([$pasienId]);
    $pasien = $stmt->fetch();
    if (!$pasien) {
        flash('danger', 'Pasien tidak ditemukan.');
        redirect(url('pasien'));
    }

    $riwayat = $db->prepare(
        'SELECT rm.*, pl.nama AS poli, d.nama AS dokter
         FROM rekam_medis rm
         JOIN pendaftaran r ON r.id = rm.pendaftaran_id
         JOIN poli pl ON pl.id = r.poli_id
         LEFT JOIN dokter d ON d.id = rm.dokter_id
         WHERE rm.pasien_id = ? ORDER BY rm.tanggal DESC'
    );
    $riwayat->execute([$pasienId]);
    $rows = $riwayat->fetchAll();

    require __DIR__ . '/../../includes/header.php';
    ?>
    <div class="card">
        <div class="card-header"><h3 class="card-title">Riwayat Rekam Medis: <?= e($pasien['nama']) ?> (<?= e($pasien['no_rm']) ?>)</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead><tr><th>Tanggal</th><th>Poli</th><th>Dokter</th><th>Diagnosis</th><th></th></tr></thead>
                <tbody>
                    <?php foreach ($rows as $r): ?>
                        <tr>
                            <td><?= e(tgl($r['tanggal'], true)) ?></td>
                            <td><?= e($r['poli']) ?></td>
                            <td><?= e($r['dokter'] ?: '-') ?></td>
                            <td><?= e(mb_strimwidth((string) $r['diagnosis'], 0, 60, '...')) ?></td>
                            <td><a href="<?= e(url('rekam-medis', ['action' => 'detail', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i></a></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$rows): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Belum ada riwayat rekam medis.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        <div class="card-footer"><a href="<?= e(url('pasien')) ?>" class="btn btn-secondary">Kembali ke Data Pasien</a></div>
    </div>
    <?php
    require __DIR__ . '/../../includes/footer.php';
    return;
}

// ---------- DAFTAR ----------
$q = trim((string) ($_GET['q'] ?? ''));
$where = '';
$params = [];
if ($q !== '') {
    $where = 'WHERE ps.nama LIKE ? OR ps.no_rm LIKE ? OR rm.diagnosis LIKE ?';
    $params = ["%$q%", "%$q%", "%$q%"];
}

$totalStmt = $db->prepare("SELECT COUNT(*) FROM rekam_medis rm JOIN pasien ps ON ps.id = rm.pasien_id $where");
$totalStmt->execute($params);
$total = (int) $totalStmt->fetchColumn();
$pg = paginate($total, 10, (int) ($_GET['p'] ?? 1));

$stmt = $db->prepare(
    "SELECT rm.*, ps.nama AS pasien, ps.no_rm, d.nama AS dokter
     FROM rekam_medis rm
     JOIN pasien ps ON ps.id = rm.pasien_id
     LEFT JOIN dokter d ON d.id = rm.dokter_id
     $where ORDER BY rm.tanggal DESC LIMIT {$pg['per_page']} OFFSET {$pg['offset']}"
);
$stmt->execute($params);
$rows = $stmt->fetchAll();

require __DIR__ . '/../../includes/header.php';
?>
<div class="card">
    <div class="card-header">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h3 class="card-title mb-0">Daftar Rekam Medis</h3>
            <form method="get" action="/index.php" class="d-flex gap-1">
                <input type="hidden" name="page" value="rekam-medis" />
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari pasien / diagnosis..." value="<?= e($q) ?>" />
                <button class="btn btn-sm btn-outline-primary"><i class="bi bi-search"></i></button>
            </form>
        </div>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-striped table-hover mb-0">
            <thead><tr><th>Tanggal</th><th>No. RM</th><th>Pasien</th><th>Dokter</th><th>Diagnosis</th><th style="width:80px">Aksi</th></tr></thead>
            <tbody>
                <?php foreach ($rows as $r): ?>
                    <tr>
                        <td><?= e(tgl($r['tanggal'], true)) ?></td>
                        <td><?= e($r['no_rm']) ?></td>
                        <td><b><?= e($r['pasien']) ?></b></td>
                        <td><?= e($r['dokter'] ?: '-') ?></td>
                        <td><?= e(mb_strimwidth((string) $r['diagnosis'], 0, 50, '...')) ?></td>
                        <td><a href="<?= e(url('rekam-medis', ['action' => 'detail', 'id' => $r['id']])) ?>" class="btn btn-sm btn-outline-info"><i class="bi bi-eye"></i> Detail</a></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$rows): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">Belum ada rekam medis.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
    <div class="card-footer d-flex justify-content-between align-items-center">
        <small class="text-muted">Total: <?= $total ?> rekam medis</small>
        <?= render_pagination('rekam-medis', $pg, ['q' => $q]) ?>
    </div>
</div>
<?php require __DIR__ . '/../../includes/footer.php'; ?>
