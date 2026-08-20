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
    ['page' => 'rekam-medis',  'icon' => 'bi-file-medical',   'label' => 'Rekam Medis'],
    ['group' => 'KEUANGAN'],
    ['page' => 'billing',      'icon' => 'bi-receipt',        'label' => 'Billing / Kasir'],
];
if (($user['role'] ?? '') === 'admin') {
    $menu[] = ['group' => 'SISTEM'];
    $menu[] = ['page' => 'users', 'icon' => 'bi-person-gear', 'label' => 'Manajemen User'];
}
?>
<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?= e(url('dashboard')) ?>" class="brand-link">
            <img src="/assets/img/AdminLTELogo.png" alt="SIMRS" class="brand-image opacity-75 shadow" />
            <span class="brand-text fw-light">SIMRS</span>
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
