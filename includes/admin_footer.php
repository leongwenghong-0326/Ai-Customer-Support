        </main>
        
        <!-- Admin Footer -->
        <footer class="admin-footer px-4 py-3 bg-dark-glass border-top border-dark-subtle text-secondary small d-flex justify-content-between align-items-center">
            <span>&copy; <?php echo date('Y'); ?> NovaServe AI. Internal Administrative System.</span>
            <span>Version 1.0.0-PRO</span>
        </footer>
    </div>
</div>

<!-- Bootstrap 5.3 Bundle JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<!-- Admin Sidebar Toggle Logic -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toggleBtn = document.getElementById('sidebarToggleBtn');
    const closeBtn = document.getElementById('sidebarCloseBtn');
    const sidebar = document.getElementById('adminSidebar');

    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('show');
        });
    }
    if (closeBtn && sidebar) {
        closeBtn.addEventListener('click', () => {
            sidebar.classList.remove('show');
        });
    }

    // Notification Dropdown Interactivity
    const notifContainer = document.getElementById('notifListContainer');
    const markAllBtn = document.getElementById('markAllReadBtn');
    const notifBadge = document.getElementById('notifBadge');
    const notifHeaderCount = document.getElementById('notifHeaderCount');

    // Handle single notification click
    if (notifContainer) {
        notifContainer.addEventListener('click', function(e) {
            const item = e.target.closest('.notif-item');
            if (!item) return;

            const notifId = item.getAttribute('data-id');
            const isUnread = item.classList.contains('unread');

            if (isUnread && notifId) {
                // Post background read marker
                navigator.sendBeacon ? 
                    navigator.sendBeacon('<?php echo BASE_URL; ?>api/notifications.php', JSON.stringify({ notification_id: parseInt(notifId) })) :
                    fetch('<?php echo BASE_URL; ?>api/notifications.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ notification_id: parseInt(notifId) })
                    }).catch(() => {});
            }
        });
    }

    // Handle "Mark all read" button
    if (markAllBtn) {
        markAllBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const origHtml = markAllBtn.innerHTML;
            markAllBtn.innerHTML = '<i class="fa-solid fa-spinner fa-spin me-1"></i> Marking...';
            markAllBtn.disabled = true;

            fetch('<?php echo BASE_URL; ?>api/notifications.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'mark_all_read' })
            })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    if (notifBadge) notifBadge.style.display = 'none';
                    if (notifHeaderCount) {
                        notifHeaderCount.textContent = '0 unread';
                        notifHeaderCount.className = 'badge bg-secondary-subtle text-secondary rounded-pill small';
                    }
                    markAllBtn.style.display = 'none';

                    // Visually change items to read
                    document.querySelectorAll('.notif-item.unread').forEach(el => {
                        el.classList.remove('unread');
                        el.classList.add('text-muted');
                        const dot = el.querySelector('.notif-dot');
                        if (dot) dot.remove();
                    });
                } else {
                    markAllBtn.innerHTML = origHtml;
                    markAllBtn.disabled = false;
                }
            })
            .catch(() => {
                markAllBtn.innerHTML = origHtml;
                markAllBtn.disabled = false;
            });
        });
    }

    // Background polling for real-time notification sync every 25 seconds
    setInterval(function() {
        fetch('<?php echo BASE_URL; ?>api/notifications.php')
            .then(res => res.json())
            .then(data => {
                if (data && data.success) {
                    const count = data.unread_count || 0;
                    if (count > 0) {
                        let badgeEl = document.getElementById('notifBadge');
                        if (!badgeEl) {
                            const btn = document.getElementById('notifBellButton');
                            if (btn) {
                                badgeEl = document.createElement('span');
                                badgeEl.id = 'notifBadge';
                                badgeEl.className = 'position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger';
                                btn.appendChild(badgeEl);
                            }
                        }
                        if (badgeEl) {
                            badgeEl.textContent = count > 99 ? '99+' : count;
                            badgeEl.style.display = '';
                        }
                        if (notifHeaderCount) {
                            notifHeaderCount.textContent = count + ' unread';
                            notifHeaderCount.className = 'badge bg-primary-subtle text-primary rounded-pill small';
                        }
                        if (markAllBtn) markAllBtn.style.display = '';
                    } else {
                        if (notifBadge) notifBadge.style.display = 'none';
                        if (notifHeaderCount) {
                            notifHeaderCount.textContent = '0 unread';
                            notifHeaderCount.className = 'badge bg-secondary-subtle text-secondary rounded-pill small';
                        }
                        if (markAllBtn) markAllBtn.style.display = 'none';
                    }
                }
            })
            .catch(() => {});
    }, 25000);
});
</script>
</body>
</html>
