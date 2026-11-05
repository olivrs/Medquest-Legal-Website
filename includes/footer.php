<?php if (is_logged_in()): ?>
        </main><!-- /.app-content -->

        <!-- ====================================================================
             3. CORPORATE FOOTER ALA https://medquest.co.id/
             ==================================================================== -->
        <footer class="mq-footer no-print">
            <div class="mq-footer-main">
                <div class="mq-fluid-bar mq-footer-grid">
                    <!-- Kolom 1: Logo, Profil Perusahaan & Kontak -->
                    <div class="mq-footer-col mq-footer-about">
                        <div class="mq-footer-logo-box">
                            <img src="<?= base_url('assets/img/logo_medquest.png') ?>" alt="PT Medquest Jaya Global">
                        </div>
                        <p class="mq-footer-desc">
                            PT. Medquest Jaya Global since its establishment in 2004 concentrating in distributing medical device and pharmaceutical, by promoting innovation and latest technology. We currently distribute more than 30 leading brands in the world, which includes the equipment in the field of blood bank, diagnostics, research and industry.
                        </p>
                        <ul class="mq-footer-contacts">
                            <li><i class="fas fa-phone-volume"></i> <span>+62-21 39842961</span></li>
                            <li><i class="fas fa-envelope"></i> <a href="mailto:info@medquest.co.id">info@medquest.co.id</a></li>
                            <li><i class="fas fa-map-marker-alt"></i> <span>Menara Salemba 6th Fl Zone 1, Jl. Salemba Raya No. 5 - 5A, Jakarta Pusat 10440</span></li>
                        </ul>
                        <div class="mq-iso-badges">
                            <span class="mq-iso-pill"><i class="fas fa-certificate"></i> ISO 9001:2015 SGS Certified</span>
                            <span class="mq-iso-pill"><i class="fas fa-shield-alt"></i> ISO 37001:2016 Anti-Bribery</span>
                        </div>
                    </div>

                    <!-- Kolom 2: Explore & Navigasi Cepat -->
                    <div class="mq-footer-col mq-footer-links">
                        <h4 class="mq-footer-title">Explore</h4>
                        <ul>
                            <li><a href="<?= base_url('?page=agreements') ?>">Dashboard Agreement</a></li>
                            <li><a href="<?= base_url('?page=create') ?>">Input Agreement Baru</a></li>
                            <li><a href="<?= base_url('?page=notifications') ?>">Monitoring Deadline</a></li>
                            <li><a href="<?= base_url('?page=smtp_settings') ?>">Pengaturan Email SMTP</a></li>
                            <li><a href="https://medquest.co.id/" target="_blank" rel="noopener">Official Website (medquest.co.id)</a></li>
                            <li><a href="https://medquest.co.id/products/" target="_blank" rel="noopener">Products &amp; Calibration</a></li>
                            <li><a href="https://medquest.co.id/whistle-blowing-system/" target="_blank" rel="noopener">Whistle Blowing System (WBS)</a></li>
                        </ul>
                    </div>

                    <!-- Kolom 3: Hotline 24 Hours & Media Sosial -->
                    <div class="mq-footer-col mq-footer-hotline">
                        <h4 class="mq-footer-title">Customer &amp; Internal Support</h4>
                        <div class="mq-hotline-card">
                            <div class="mq-hotline-icon"><i class="fas fa-headset"></i></div>
                            <div>
                                <div class="mq-hotline-label">Hotline Service 24 Hours</div>
                                <div class="mq-hotline-number">+62-21-29260000</div>
                            </div>
                        </div>
                        <div class="mq-footer-socials">
                            <a href="https://www.instagram.com/medquest_id/" target="_blank" rel="noopener" title="Instagram"><i class="fab fa-instagram"></i></a>
                            <a href="https://www.facebook.com/MedquestID" target="_blank" rel="noopener" title="Facebook"><i class="fab fa-facebook-f"></i></a>
                            <a href="https://twitter.com/medquest_id" target="_blank" rel="noopener" title="X / Twitter"><i class="fab fa-twitter"></i></a>
                            <a href="https://medquest.co.id/contact-us/" target="_blank" rel="noopener" title="Contact Us"><i class="fas fa-external-link-alt"></i></a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="mq-footer-bottom">
                <div class="mq-fluid-bar">
                    &copy; <?= date('Y') ?> <strong>PT Medquest Jaya Global</strong> &middot; Document System Monitoring (MJG-FOR-LAC.12-001-03). All Rights Reserved.
                </div>
            </div>
        </footer>
    </div><!-- /.app-main -->
</div><!-- /.app-layout -->
<?php else: ?>
    <footer class="mq-auth-footer no-print">
        &copy; <?= date('Y') ?> <strong>PT Medquest Jaya Global</strong> &middot; Menara Salemba 6th Fl Zone 1, Jakarta Pusat &middot; Document System Monitoring
    </footer>
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (is_logged_in()): ?>
<script>
// Toggle Buka / Tutup Sidebar dengan tombol 3 baris (#sidebarToggleBtn)
(function() {
    const layoutEl = document.querySelector('.app-layout');
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    if (!layoutEl || !toggleBtn) return;

    if (localStorage.getItem('mq_sidebar_collapsed') === '1') {
        layoutEl.classList.add('sidebar-collapsed');
    }

    toggleBtn.addEventListener('click', function() {
        layoutEl.classList.toggle('sidebar-collapsed');
        const isCollapsed = layoutEl.classList.contains('sidebar-collapsed');
        localStorage.setItem('mq_sidebar_collapsed', isCollapsed ? '1' : '0');
    });
})();

// Worker Otomatis di Background: Mengecek dokumen H-90, H-60, H-30, H-14, H-7, H-3 setiap 20 detik
function checkAndSendAutoExpiryEmails() {
    fetch('<?= base_url('cron_notify.php') ?>', { cache: 'no-store' })
        .then(res => res.json())
        .then(data => {
            if (data && data.sent_count > 0 && Array.isArray(data.details)) {
                const container = document.getElementById('autoEmailToastContainer');
                if (!container) return;
                data.details.forEach(item => {
                    const toastEl = document.createElement('div');
                    toastEl.className = 'alert alert-success shadow-lg border-success mb-2 p-3';
                    toastEl.innerHTML = `
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <div class="fw-bold"><i class="fas fa-paper-plane me-1"></i> Email Otomatis ${item.tipe} Terkirim!</div>
                                <div class="small mt-1">Dokumen <strong>${item.customer || ''}</strong> otomatis dikirim ke <strong>${item.recipient} (${item.kode})</strong>.</div>
                            </div>
                            <button type="button" class="btn-close ms-2" onclick="this.parentElement.parentElement.remove()"></button>
                        </div>
                    `;
                    container.appendChild(toastEl);
                });
            }
        })
        .catch(() => {});
}

setTimeout(checkAndSendAutoExpiryEmails, 2000);
setInterval(checkAndSendAutoExpiryEmails, 20000);
</script>
<?php endif; ?>
</body>
</html>
