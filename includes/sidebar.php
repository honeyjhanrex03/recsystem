<?php
$current_page = basename($_SERVER['PHP_SELF']);
?>
<div id="sidebar-wrapper" class="border-0 shadow-lg">
    <div class="sidebar-heading border-0 py-4 px-4">
        <div class="d-flex align-items-center gap-3">
            <div class="bg-white rounded-circle p-1 shadow-sm d-flex align-items-center justify-content-center" style="width: 45px; height: 45px;">
                <img src="<?php echo BASE_URL; ?>assets/images/logo.png" alt="Logo" width="32">
            </div>
            <div class="lh-1">
                <span class="d-block fw-bold" style="font-size: 1.1rem; letter-spacing: 0.5px;">DNSC REC</span>
                <small class="text-gold opacity-75 fw-bold" style="font-size: 0.65rem; letter-spacing: 1px;">SYSTEM</small>
            </div>
        </div>
    </div>
    <div class="list-group list-group-flush mt-3">
        <a class="list-group-item <?php echo ($current_page == 'index.php' && strpos($_SERVER['PHP_SELF'], $_SESSION['role']) !== false) ? 'active' : ''; ?>"
            href="<?php echo BASE_URL . $_SESSION['role']; ?>/">
            <i class="fas fa-th-large"></i> Dashboard
        </a>

        <a class="list-group-item <?php echo ($current_page == 'document_center.php') ? 'active' : ''; ?>"
            href="<?php echo BASE_URL; ?>document_center">
            <i class="fas fa-file-invoice"></i> REC Forms
        </a>

        <?php if ($_SESSION['role'] == 'admin'):
            // Fetch counts for badges
            $pendingUsers = $pdo->query("SELECT COUNT(*) FROM users WHERE status = 'pending'")->fetchColumn();
            $pendingAdmins = $pdo->query("SELECT COUNT(*) FROM admins WHERE status = 'pending'")->fetchColumn();
            $pendingResets = $pdo->query("SELECT COUNT(*) FROM password_resets WHERE status = 'pending'")->fetchColumn();
            $totalAlerts = $pendingAdmins + $pendingResets;
            ?>
            <a class="list-group-item <?php echo ($current_page == 'users.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>admin/users">
                <i class="fas fa-users-gear"></i> Staff Users
                <?php if ($totalAlerts > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto"
                        style="font-size: 0.6rem;"><?php echo $totalAlerts; ?></span>
                <?php endif; ?>
            </a>
            <a class="list-group-item <?php echo ($current_page == 'researchers.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>admin/researchers">
                <i class="fas fa-user-graduate"></i> Researchers
                <?php if ($pendingUsers > 0): ?>
                    <span class="badge bg-warning rounded-pill ms-auto"
                        style="font-size: 0.6rem;"><?php echo $pendingUsers; ?></span>
                <?php endif; ?>
            </a>
            <a class="list-group-item <?php echo ($current_page == 'protocols.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>admin/protocols">
                <i class="fas fa-folder-open"></i> All Research
            </a>
            <a class="list-group-item <?php echo ($current_page == 'audit.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>admin/audit">
                <i class="fas fa-shield-halved"></i> Activity Logs
            </a>
            <a class="list-group-item <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>admin/reports">
                <i class="fas fa-chart-bar"></i> Statistics
            </a>
        <?php endif; ?>


        <?php if ($_SESSION['role'] == 'rec_staff' || $_SESSION['role'] == 'rec_chair' || $_SESSION['role'] == 'rec_secretary'): ?>
            <a class="list-group-item <?php echo ($current_page == 'protocols.php' && strpos($_SERVER['PHP_SELF'], '/staff/') !== false) ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>rec_staff/protocols">
                <i class="fas fa-file-arrow-up"></i>
                <?php 
                if($_SESSION['role'] == 'rec_chair') echo 'Staff Files';
                elseif($_SESSION['role'] == 'rec_secretary') echo 'All Research Files';
                else echo 'Research Files'; 
                ?>
            </a>
            <?php if ($_SESSION['role'] == 'rec_staff'): ?>
                <a class="list-group-item <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>rec_staff/reports">
                    <i class="fas fa-chart-pie"></i> Reports
                </a>
            <?php endif; ?>
            <?php if ($_SESSION['role'] == 'rec_secretary'): ?>
                <a class="list-group-item <?php echo ($current_page == 'reports.php') ? 'active' : ''; ?>"
                    href="<?php echo BASE_URL; ?>rec_secretary/reports">
                    <i class="fas fa-chart-line"></i> Reporting <small class="badge bg-secondary ms-1">Soon</small>
                </a>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'rec_chair'): ?>
            <a class="list-group-item <?php echo ($current_page == 'protocols.php' && strpos($_SERVER['PHP_SELF'], '/chair/') !== false) ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>rec_chair/protocols">
                <i class="fas fa-user-check"></i> Choose Reviewers
            </a>
            <a class="list-group-item <?php echo ($current_page == 'secretary_assignment.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>rec_chair/secretary_assignment">
                <i class="fas fa-user-tie"></i> REC Secretary Settings
            </a>
            <a class="list-group-item <?php echo ($current_page == 'decisions.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>rec_chair/decisions">
                <i class="fas fa-gavel"></i> Final Decisions
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'rec_member' || $_SESSION['role'] == 'rec_chair' || $_SESSION['role'] == 'rec_secretary'): ?>
            <a class="list-group-item <?php echo ($current_page == 'protocols.php' && strpos($_SERVER['PHP_SELF'], '/member/') !== false) ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>rec_member/protocols">
                <i class="fas fa-pen-nib"></i>
                <?php echo ($_SESSION['role'] == 'rec_chair' || $_SESSION['role'] == 'rec_secretary') ? 'My Reviews' : 'My Reviews'; ?>
            </a>
        <?php endif; ?>

        <?php if ($_SESSION['role'] == 'author'): ?>
            <a class="list-group-item <?php echo ($current_page == 'submit.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>author/submit">
                <i class="fas fa-upload"></i> Submit Research
            </a>
            
            <div class="mt-4 px-4 small text-gold text-uppercase fw-bold" style="letter-spacing: 2px; font-size: 0.65rem; opacity: 0.85;">Management</div>
            <a class="list-group-item <?php echo ($current_page == 'insights.php') ? 'active' : ''; ?>"
                href="<?php echo BASE_URL; ?>author/insights">
                 <i class="fas fa-chart-line"></i> Stats
            </a>
        <?php endif; ?>

        <div class="mt-4 px-4 small text-gold text-uppercase fw-bold"
            style="letter-spacing: 2px; opacity: 0.85; font-size: 0.65rem;">System</div>

        <a class="list-group-item <?php echo (strpos($_SERVER['PHP_SELF'], 'profile.php') !== false) ? 'active' : ''; ?>" 
            href="<?php echo BASE_URL; ?>shared/profile">
            <i class="fas fa-user-circle"></i> My Profile
        </a>

        <a class="list-group-item text-danger" href="javascript:void(0)"
            onclick="confirmLogout('<?php echo BASE_URL; ?>logout')">
            <i class="fas fa-power-off"></i> Logout
        </a>
    </div>
</div>
