<aside class="app-sidebar bg-body-secondary shadow" data-bs-theme="dark">
    <div class="sidebar-brand">
        <a href="<?php echo base_url('index.php/dashboard'); ?>" class="brand-link">
            <span class="brand-mark"><svg viewBox="0 0 24 24" fill="none"><path d="M9.5 4h5v5.5H20v5h-5.5V20h-5v-5.5H4v-5h5.5V4z" fill="#fff"/></svg></span>
            <span class="brand-text-wrap">
                <span class="brand-name">SIMRS</span>
                <span class="brand-tagline"><?php echo $nama_rs ?? 'SIMRS'; ?></span>
            </span>
        </a>
    </div>
    <div class="sidebar-wrapper">
        <nav class="mt-2">
            <ul class="nav sidebar-menu flex-column" data-lte-toggle="treeview" role="menu" data-accordion="false">
                <li class="nav-header">UTAMA</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/dashboard'); ?>" class="nav-link<?php echo ($currentPage ?? '') === 'dashboard' ? ' active' : ''; ?>"><i class="nav-icon bi bi-speedometer2"></i><p>Dashboard</p></a></li>
                <li class="nav-header">MASTER DATA</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/pasien'); ?>" class="nav-link"><i class="nav-icon bi bi-people-fill"></i><p>Data Pasien</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/dokter'); ?>" class="nav-link"><i class="nav-icon bi bi-person-badge"></i><p>Data Dokter</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/poli'); ?>" class="nav-link"><i class="nav-icon bi bi-hospital"></i><p>Data Poli</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/obat'); ?>" class="nav-link"><i class="nav-icon bi bi-capsule"></i><p>Data Obat</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/tindakan'); ?>" class="nav-link"><i class="nav-icon bi bi-tags"></i><p>Tarif Tindakan</p></a></li>
                <li class="nav-header">PELAYANAN</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/pendaftaran'); ?>" class="nav-link"><i class="nav-icon bi bi-clipboard-plus"></i><p>Pendaftaran</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/janji-temu'); ?>" class="nav-link"><i class="nav-icon bi bi-calendar-check"></i><p>Janji Temu</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/antrian'); ?>" class="nav-link"><i class="nav-icon bi bi-megaphone"></i><p>Papan Antrian</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/rekam-medis'); ?>" class="nav-link"><i class="nav-icon bi bi-file-medical"></i><p>Rekam Medis</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/surat'); ?>" class="nav-link"><i class="nav-icon bi bi-file-earmark-text"></i><p>Surat Keterangan</p></a></li>
                <li class="nav-header">KEUANGAN</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/billing'); ?>" class="nav-link"><i class="nav-icon bi bi-receipt"></i><p>Billing / Kasir</p></a></li>
                <li class="nav-header">LAPORAN</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/laporan'); ?>" class="nav-link"><i class="nav-icon bi bi-bar-chart-line"></i><p>Laporan</p></a></li>
                <li class="nav-header">SISTEM</li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/users'); ?>" class="nav-link"><i class="nav-icon bi bi-person-gear"></i><p>Manajemen User</p></a></li>
                <li class="nav-item"><a href="<?php echo base_url('index.php/pengaturan'); ?>" class="nav-link"><i class="nav-icon bi bi-gear"></i><p>Pengaturan RS</p></a></li>
            </ul>
        </nav>
    </div>
</aside>
