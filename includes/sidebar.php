<?php
$menu = [
    ['group' => 'UTAMA'],
    ['page' => 'dashboard',    'icon' => 'bi-speedometer2',   'label' => 'Dashboard'],
    ['group' => 'MASTER DATA'],
    ['page' => 'pasien',       'icon' => 'bi-people-fill',    'label' => 'Data Pasien'],
    ['page' => 'dokter',       'icon' => 'bi-person-badge',   'label' => 'Data Dokter'],
    ['page' => 'poli',         'icon' => 'bi-hospital',       'label' => 'Data Poli'],
    ['page' => 'obat',         'icon' => 'bi-capsule',        'label' => 'Data Obat'],
    ['group' => 'PELAYANAN'],
    ['page' => 'pendaftaran',  'icon' => 'bi-clipboard-plus', 'label' => 'Pendaftaran'],
    ['page' => 'antrian',      'icon' => 'bi-megaphone',      'label' => 'Papan Antrian'],
    ['page' => 'rekam-medis',  'icon' => 'bi-file-medical',   'label' => 'Rekam Medis'],
    ['group' => 'KEUANGAN'],
    ['page' => 'billing',      'icon' => 'bi-receipt',        'label' => 'Billing / Kasir'],
];
if (in_array($user['role'] ?? '', ['admin', 'petugas'], true)) {
    $menu[] = ['group' => 'LAPORAN'];
    $menu[] = ['page' => 'laporan', 'icon' => 'bi-bar-chart-line', 'label' => 'Laporan'];
}
if (($user['role'] ?? '') === 'admin') {
    $menu[] = ['group' => 'SISTEM'];
    $menu[] = ['page' => 'users', 'icon' => 'bi-person-gear', 'label' => 'Manajemen User'];
    $menu[] = ['page' => 'pengaturan', 'icon' => 'bi-gear', 'label' => 'Pengaturan RS'];
}
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= e(url('dashboard')) ?>" class="brand-link">
            <span class="brand-mark">
                <svg viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5V4z" fill="#fff" />
                </svg>
            </span>
            <span class="brand-text-wrap">
                <span class="brand-name">SIMRS</span>
                <span class="brand-tagline"><?= e(nama_rs()) ?></span>
            </span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <?php foreach ($menu as $item): ?>
                    <?php if (isset($item['group'])): ?>
                        <li class="nav-header"><?= e($item['group']) ?></li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a href="<?= e(url($item['page'])) ?>"
                               class="nav-link<?= $currentPage === $item['page'] ? ' active' : '' ?>">
                                <i class="nav-icon bi <?= e($item['icon']) ?>"></i>
                                <p><?= e($item['label']) ?></p>
                            </a>
                        </li>
                    <?php endif; ?>
                <?php endforeach; ?>
            </ul>
        </nav>
    </div>
</aside>
