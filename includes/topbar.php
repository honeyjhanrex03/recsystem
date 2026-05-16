<nav class="navbar navbar-expand-lg border-0 shadow-sm px-3 px-md-4 py-3 sticky-top" style="background: rgba(255, 255, 255, 0.8) !important; backdrop-filter: blur(15px); -webkit-backdrop-filter: blur(15px); border-bottom: 1px solid var(--glass-border) !important;">
    <div class="container-fluid p-0">
        <button id="sidebarToggle" class="btn btn-light border-0 rounded-circle me-3">
            <i class="fas fa-align-left text-navy"></i>
        </button>
        <div class="d-none d-md-block">
            <h5 class="mb-0 fw-bold text-navy"><?php echo $workspaceTitle ?? 'Institutional Workspace'; ?></h5>
            <p class="text-muted small mb-0"><?php echo $workspaceSubtitle ?? 'Research Ethics Compliance System'; ?></p>
        </div>
        <div class="ms-auto d-flex align-items-center">
            
            <!-- Notification Bell -->
            <div class="dropdown me-2 me-md-4">
                <button class="btn btn-light border-0 rounded-circle position-relative" type="button" id="notifDropdown" data-bs-toggle="dropdown" data-bs-display="static" aria-expanded="false" onclick="markNotificationsRead()">
                    <i class="fas fa-bell text-navy"></i>
                    <span id="notifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" style="font-size:0.55rem;">
                        0
                    </span>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow border-0 rounded-4 p-0 mt-2 notif-dropdown" aria-labelledby="notifDropdown">
                    <div class="p-3 border-bottom bg-light rounded-top-4 d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 fw-bold text-navy">Notifications</h6>
                    </div>
                    <div id="notifList">
                        <!-- Notifications populated via AJAX -->
                        <div class="p-4 text-center text-muted small">Loading...</div>
                    </div>
                </div>
            </div>

            <div class="me-3 text-end d-none d-sm-block">
                <p class="mb-0 fw-bold text-navy"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Authorized User'); ?></p>
                <span class="badge bg-soft-gold text-gold border-0 fw-bold px-3 py-1 rounded-pill" style="font-size:0.65rem; letter-spacing: 0.8px;">
                    <?php 
                    $roleLabel = strtoupper($_SESSION['role'] ?? 'REC MEMBER');
                    if($roleLabel == 'AUTHOR') $roleLabel = 'RESEARCHER/COMMITTEE';
                    if($roleLabel == 'REC_CHAIR') $roleLabel = 'REC CHAIR';
                    if($roleLabel == 'REC_STAFF') $roleLabel = 'REC STAFF';
                    if($roleLabel == 'REC_MEMBER') $roleLabel = 'REC MEMBER';
                    if($roleLabel == 'REC_SECRETARY') $roleLabel = 'REC SECRETARY';
                    if($roleLabel == 'ADMIN') $roleLabel = 'SYSTEM ADMIN';
                    echo $roleLabel;
                    ?>
                </span>
            </div>
            <div class="avatar-wrapper p-1 rounded-circle border border-gold border-2">
                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['name'] ?? 'U'); ?>&background=1a2b4b&color=fff&size=40&bold=true" 
                     class="rounded-circle shadow-sm" width="40" height="40">
            </div>
        </div>
    </div>
</nav>

<style>
.btn-light:hover i { color: #c5a059 !important; }
.notif-item { transition: background 0.2s; border-bottom: 1px solid #f1f5f9; }
.notif-item:hover { background: #f8fafc; }
.notif-item.unread { background: #f0f9ff; }
.notif-item:last-child { border-bottom: none; }
.notif-dropdown { width: 320px; max-height: 400px; overflow-y: auto; }
@media (max-width: 576px) {
    .notif-dropdown {
        width: 300px;
        right: -50px !important; 
    }
}
@media (max-width: 380px) {
    .notif-dropdown {
        width: 280px;
        right: -60px !important; 
    }
}
</style>

<script>
let lastNotifHTML = '';
let lastUnreadCount = -1;

function fetchNotifications() {
    fetch('<?php echo BASE_URL; ?>ajax_notifications?action=fetch')
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                // Update badge if changed
                if(data.unread_count !== lastUnreadCount) {
                    const badge = document.getElementById('notifBadge');
                    if(data.unread_count > 0) {
                        badge.textContent = data.unread_count;
                        badge.classList.remove('d-none');
                    } else {
                        badge.classList.add('d-none');
                    }
                    lastUnreadCount = data.unread_count;
                }

                // Update list if changed
                let newHTML = '';
                if(data.notifications.length === 0) {
                    newHTML = '<div class="p-4 text-center text-muted small">No new notifications</div>';
                } else {
                    newHTML = data.notifications.map(n => `
                        <a href="${n.link ? '<?php echo BASE_URL; ?>' + n.link : '#'}" class="text-decoration-none d-block p-3 notif-item ${n.is_read == 0 ? 'unread' : ''}">
                            <div class="d-flex w-100 justify-content-between">
                                <h6 class="mb-1 text-navy fw-bold" style="font-size:0.85rem;">${n.title}</h6>
                            </div>
                            <p class="mb-1 text-muted" style="font-size:0.75rem;">${n.message}</p>
                            <small class="text-muted" style="font-size:0.65rem;">${new Date(n.created_at).toLocaleString()}</small>
                        </a>
                    `).join('');
                }
                
                if (newHTML !== lastNotifHTML) {
                    document.getElementById('notifList').innerHTML = newHTML;
                    lastNotifHTML = newHTML;
                }
            }
        });
}

function markNotificationsRead() {
    const badge = document.getElementById('notifBadge');
    if(!badge.classList.contains('d-none')) {
        fetch('<?php echo BASE_URL; ?>ajax_notifications?action=mark_read')
            .then(() => {
                badge.classList.add('d-none');
                document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
            });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    fetchNotifications();
    setInterval(fetchNotifications, 3000); // Poll every 3 seconds for real-time updates
});
</script>
