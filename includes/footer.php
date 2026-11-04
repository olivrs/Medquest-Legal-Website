<?php if (is_logged_in()): ?>
        </div><!-- /.mq-content-area -->
    </div><!-- /.mq-main-panel -->
</div><!-- /.mq-app-wrapper -->
<?php endif; ?>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<?php if (is_logged_in()): ?>
<script>
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
