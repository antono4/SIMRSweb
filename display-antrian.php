<?php

declare(strict_types=1);

// Display antrian publik untuk layar TV ruang tunggu — tanpa login
require __DIR__ . '/config/database.php';
require __DIR__ . '/includes/functions.php';

$db = db();
$stmt = $db->query(
    "SELECT r.no_antrian, r.status, r.dipanggil_pada, pl.nama AS poli, pl.kode
     FROM pendaftaran r
     JOIN poli pl ON pl.id = r.poli_id
     WHERE DATE(r.tanggal) = CURDATE() AND r.status != 'batal'
     ORDER BY r.dipanggil_pada DESC, r.no_antrian"
);
$rows = $stmt->fetchAll();

$dipanggil = array_values(array_filter($rows, fn($r) => $r['status'] === 'dipanggil'));
$diperiksa = array_values(array_filter($rows, fn($r) => $r['status'] === 'diperiksa'));
$menunggu  = array_values(array_filter($rows, fn($r) => $r['status'] === 'menunggu'));

// Terakhir dipanggil per poli (untuk strip bawah)
$terakhirPerPoli = [];
foreach ($dipanggil as $r) {
    $terakhirPerPoli[$r['poli']] ??= $r['no_antrian'];
}
$utama = $dipanggil[0] ?? null;
?>
<!doctype html>
<html lang="id">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <meta http-equiv="refresh" content="15" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Display Antrian | <?= e(nama_rs()) ?></title>
    <link rel="stylesheet" href="/assets/vendor/fonts/plus-jakarta-sans.css" />
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css" />
    <link rel="stylesheet" href="/assets/css/adminlte.min.css" />
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: linear-gradient(160deg, #122036, #0b1526);
            color: #e2e8f0;
            min-height: 100vh;
        }
        .display-header {
            padding: 1.5rem 2.5rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.15);
        }
        .display-header .rs-name { font-size: 1.6rem; font-weight: 800; color: #fff; letter-spacing: -0.01em; }
        .display-header .rs-sub  { color: #64748b; font-size: 0.85rem; font-weight: 500; letter-spacing: 0.1em; text-transform: uppercase; }
        .display-clock { font-size: 2rem; font-weight: 800; color: #5eead4; font-variant-numeric: tabular-nums; }
        .now-wrap { text-align: center; padding: 2rem 1rem 0; }
        .now-label { color: #94a3b8; text-transform: uppercase; letter-spacing: 0.25em; font-size: 0.85rem; font-weight: 700; }
        .now-number {
            font-size: clamp(4rem, 11vw, 9rem);
            font-weight: 800;
            letter-spacing: -0.03em;
            color: #5eead4;
            line-height: 1.05;
            text-shadow: 0 0 60px rgba(45, 212, 191, 0.35);
        }
        .now-poli { font-size: 1.5rem; color: #cbd5e1; font-weight: 600; }
        .panel {
            background: rgba(255, 255, 255, 0.04);
            border: 1px solid rgba(148, 163, 184, 0.14);
            border-radius: 1rem;
            padding: 1.25rem 1.5rem;
            height: 100%;
        }
        .panel h2 {
            font-size: 0.8rem;
            font-weight: 700;
            letter-spacing: 0.15em;
            text-transform: uppercase;
            color: #94a3b8;
            margin-bottom: 1rem;
        }
        .q-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.55rem 0;
            border-bottom: 1px dashed rgba(148, 163, 184, 0.15);
            font-weight: 700;
            font-size: 1.25rem;
            color: #e2e8f0;
        }
        .q-item:last-child { border-bottom: none; }
        .q-item small { color: #64748b; font-weight: 500; font-size: 0.85rem; }
        .strip-poli {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            background: rgba(20, 184, 166, 0.12);
            border: 1px solid rgba(45, 212, 191, 0.25);
            border-radius: 999px;
            padding: 0.5rem 1.25rem;
            margin: 0.3rem;
            color: #99f6e4;
            font-weight: 600;
        }
        .strip-poli b { font-size: 1.25rem; color: #5eead4; }
        .blink { animation: blink 1.6s ease-in-out infinite; }
        @keyframes blink { 0%, 100% { opacity: 1; } 50% { opacity: 0.35; } }
        .sound-btn {
            position: fixed; right: 18px; bottom: 18px; z-index: 10;
            border: 1px solid rgba(45,212,191,0.4); background: rgba(20,184,166,0.15);
            color: #5eead4; border-radius: 999px; padding: 0.55rem 1.1rem;
            font-family: inherit; font-weight: 700; font-size: 0.85rem; cursor: pointer;
        }
        .sound-btn.on { background: #0d9488; color: #fff; }
    </style>
</head>
<body>
    <div class="display-header d-flex justify-content-between align-items-center">
        <div>
            <div class="rs-name"><?= e(nama_rs()) ?></div>
            <div class="rs-sub">Informasi Antrian Pelayanan</div>
        </div>
        <div class="display-clock" id="jam">--:--:--</div>
    </div>
    <button class="sound-btn" id="soundBtn" type="button"><i class="bi bi-volume-mute"></i> Suara Panggilan: Mati</button>

    <div class="container-fluid px-4 pb-4">
        <div class="row g-4 mt-0">
            <div class="col-lg-7">
                <div class="now-wrap">
                    <?php if ($utama): ?>
                        <div class="now-label blink"><i class="bi bi-megaphone-fill me-2"></i>Nomor Dipanggil</div>
                        <div class="now-number"><?= e($utama['no_antrian']) ?></div>
                        <div class="now-poli">Menuju <?= e($utama['poli']) ?></div>
                    <?php else: ?>
                        <div class="now-label">Nomor Dipanggil</div>
                        <div class="now-number" style="color:#475569">—</div>
                        <div class="now-poli">Belum ada panggilan</div>
                    <?php endif; ?>
                </div>
                <div class="text-center mt-4">
                    <?php foreach ($terakhirPerPoli as $poli => $nomor): ?>
                        <span class="strip-poli"><?= e($poli) ?> <b><?= e($nomor) ?></b></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="panel mb-4">
                    <h2><i class="bi bi-hourglass-split me-2"></i>Menunggu (<?= count($menunggu) ?>)</h2>
                    <?php foreach (array_slice($menunggu, 0, 6) as $r): ?>
                        <div class="q-item"><span><?= e($r['no_antrian']) ?></span><small><?= e($r['poli']) ?></small></div>
                    <?php endforeach; ?>
                    <?php if (!$menunggu): ?><div class="q-item"><small>Tidak ada antrian menunggu</small></div><?php endif; ?>
                </div>
                <div class="panel">
                    <h2><i class="bi bi-clipboard2-pulse me-2"></i>Sedang Diperiksa (<?= count($diperiksa) ?>)</h2>
                    <?php foreach (array_slice($diperiksa, 0, 4) as $r): ?>
                        <div class="q-item"><span><?= e($r['no_antrian']) ?></span><small><?= e($r['poli']) ?></small></div>
                    <?php endforeach; ?>
                    <?php if (!$diperiksa): ?><div class="q-item"><small>Belum ada pasien diperiksa</small></div><?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script>
        function jam() {
            const d = new Date();
            document.getElementById('jam').textContent = d.toLocaleTimeString('id-ID', { hour12: false });
        }
        jam(); setInterval(jam, 1000);

        // ---- Suara panggilan (text-to-speech) ----
        const NOMOR_SAAT_INI = <?= json_encode($utama['no_antrian'] ?? null) ?>;
        const POLI_SAAT_INI  = <?= json_encode($utama['poli'] ?? null) ?>;

        let suaraAktif = false;
        try { suaraAktif = localStorage.getItem('antrian-suara') === '1'; } catch (e) {}

        const btn = document.getElementById('soundBtn');
        function tampilTombol() {
            btn.classList.toggle('on', suaraAktif);
            btn.innerHTML = suaraAktif
                ? '<i class="bi bi-volume-up"></i> Suara Panggilan: Aktif'
                : '<i class="bi bi-volume-mute"></i> Suara Panggilan: Mati';
        }
        btn.addEventListener('click', function () {
            suaraAktif = !suaraAktif;
            try { localStorage.setItem('antrian-suara', suaraAktif ? '1' : '0'); } catch (e) {}
            tampilTombol();
            if (suaraAktif && NOMOR_SAAT_INI) panggil(NOMOR_SAAT_INI, POLI_SAAT_INI);
        });
        tampilTombol();

        function panggil(nomor, poli) {
            if (!('speechSynthesis' in window)) return;
            const teks = 'Nomor antrian ' + nomor.split('').join(' ') + ', silakan menuju ' + poli + '.';
            const u = new SpeechSynthesisUtterance(teks);
            u.lang = 'id-ID';
            u.rate = 0.92;
            speechSynthesis.speak(u);
        }

        // Ucapkan nomor yang sedang dipanggil jika nomor berubah sejak load terakhir
        if (suaraAktif && NOMOR_SAAT_INI) {
            let terakhir = null;
            try { terakhir = sessionStorage.getItem('antrian-terakhir'); } catch (e) {}
            if (terakhir !== NOMOR_SAAT_INI) {
                try { sessionStorage.setItem('antrian-terakhir', NOMOR_SAAT_INI); } catch (e) {}
                panggil(NOMOR_SAAT_INI, POLI_SAAT_INI);
            }
        }
    </script>
</body>
</html>
