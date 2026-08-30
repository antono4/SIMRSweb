            </div>
        </div>
    </main>
    <footer class="app-footer">
        <div class="float-end d-none d-sm-inline">SIMRS v1.1</div>
        <strong>&copy; <?php echo date('Y'); ?> <?php echo $nama_rs ?? 'SIMRS'; ?></strong> — Sistem Informasi Manajemen Rumah Sakit.
    </footer>
</div>
<script src="<?php echo base_url('assets/vendor/overlayscrollbars/overlayscrollbars.browser.es6.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/popper/popper.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/vendor/bootstrap/bootstrap.min.js'); ?>"></script>
<script src="<?php echo base_url('assets/js/adminlte.min.js'); ?>"></script>
<script>
    const SELECTOR_SIDEBAR_WRAPPER = '.sidebar-wrapper';
    const Default = { scrollbarTheme: 'os-theme-light', scrollbarAutoHide: 'leave', scrollbarClickScroll: true };
    document.addEventListener('DOMContentLoaded', function () {
        const sidebarWrapper = document.querySelector(SELECTOR_SIDEBAR_WRAPPER);
        if (sidebarWrapper && typeof OverlayScrollbarsGlobal?.OverlayScrollbars !== 'undefined') {
            OverlayScrollbarsGlobal.OverlayScrollbars(sidebarWrapper, {
                scrollbars: {
                    theme: Default.scrollbarTheme,
                    autoHide: Default.scrollbarAutoHide,
                    clickScroll: Default.scrollbarClickScroll,
                },
            });
        }
    });
</script>
</body>
</html>
