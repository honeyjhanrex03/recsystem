<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
if (!$id) { header('Location: researchers'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch();
if (!$r) { header('Location: researchers?error=not_found'); exit(); }

// Stats
$stmtStats = $pdo->prepare("SELECT
    COUNT(*) AS total,
    SUM(CASE WHEN status = 'clearance_released' THEN 1 ELSE 0 END) AS cleared,
    SUM(CASE WHEN status NOT IN ('approved','rejected','clearance_released') THEN 1 ELSE 0 END) AS active
    FROM protocols WHERE created_by = ?");
$stmtStats->execute([$id]);
$stats = $stmtStats->fetch();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save'])) {
    $first_name     = trim($_POST['first_name']);
    $last_name      = trim($_POST['last_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $email          = trim($_POST['email']);
    $status         = $_POST['status'];
    $new_pass       = $_POST['new_password'];
    $confirm_pass   = $_POST['confirm_password'];

    if (empty($first_name) || empty($last_name) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } elseif ($new_pass && $new_pass !== $confirm_pass) {
        $error = "New passwords do not match.";
    } else {
        // Check email uniqueness (excluding self)
        $chk = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ? AND user_id != ?");
        $chk->execute([$email, $id]);
        if ($chk->fetchColumn() > 0) {
            $error = "That email address is already used by another account.";
        } else {
            $pdo->prepare("UPDATE users SET first_name=?, last_name=?, middle_initial=?, email=?, status=? WHERE user_id=?")
                ->execute([$first_name, $last_name, $middle_initial, $email, $status, $id]);

            if ($new_pass) {
                $pdo->prepare("UPDATE users SET password=? WHERE user_id=?")
                    ->execute([password_hash($new_pass, PASSWORD_DEFAULT), $id]);
            }

            $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)")
                ->execute([$_SESSION['user_id'], "Updated researcher account: $first_name $last_name (ID #$id)"]);

            $success = "Researcher profile updated successfully.";
            // Refresh data
            $stmt->execute([$id]);
            $r = $stmt->fetch();
        }
    }
}

include '../includes/header.php';
?>

<div id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php
        $workspaceTitle = "Edit Researcher";
        $workspaceSubtitle = "Manage researcher profile & account access";
        include '../includes/topbar.php';
        ?>
        <div class="container-fluid p-4 p-md-5">

            <!-- Breadcrumb -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="researchers" class="text-navy text-decoration-none">Researchers</a></li>
                    <li class="breadcrumb-item active text-muted"><?php echo htmlspecialchars($r['first_name'] . ' ' . $r['last_name']); ?></li>
                </ol>
            </nav>

            <div class="row g-4">
                <!-- LEFT: Researcher Profile Card -->
                <div class="col-lg-4">

                    <!-- Identity Card -->
                    <div class="card border-0 shadow-sm rounded-4 mb-4 text-center p-4">
                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($r['first_name'].' '.$r['last_name']); ?>&background=1a2b4b&color=fff&size=80&bold=true"
                            class="rounded-circle mx-auto shadow mb-3" width="80" height="80">
                        <h5 class="fw-bold text-navy mb-1"><?php echo htmlspecialchars($r['last_name'] . ', ' . $r['first_name'] . ' ' . ($r['middle_initial'] ?? '')); ?></h5>
                        <p class="text-muted small mb-3"><?php echo htmlspecialchars($r['email']); ?></p>
                        <?php
                        $statusColors = ['active' => 'success', 'pending' => 'warning', 'suspended' => 'danger'];
                        $statusIcons  = ['active' => 'check-circle', 'pending' => 'clock', 'suspended' => 'ban'];
                        $sc = $statusColors[$r['status']] ?? 'secondary';
                        $si = $statusIcons[$r['status']] ?? 'circle';
                        ?>
                        <span class="badge bg-soft-<?php echo $sc; ?> text-<?php echo $sc; ?> rounded-pill px-3 py-2">
                            <i class="fas fa-<?php echo $si; ?> me-1"></i><?php echo ucfirst($r['status']); ?>
                        </span>
                    </div>

                    <!-- Submission Stats -->
                    <div class="card border-0 shadow-sm rounded-4 p-4">
                        <h6 class="fw-bold text-navy mb-3"><i class="fas fa-chart-bar me-2 text-gold"></i>Submission Stats</h6>
                        <div class="row g-2 text-center">
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light">
                                    <div class="fw-bold text-navy fs-5"><?php echo $stats['total']; ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">Total</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light">
                                    <div class="fw-bold text-warning fs-5"><?php echo $stats['active']; ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">Active</div>
                                </div>
                            </div>
                            <div class="col-4">
                                <div class="p-3 rounded-3 bg-light">
                                    <div class="fw-bold text-success fs-5"><?php echo $stats['cleared']; ?></div>
                                    <div class="text-muted" style="font-size:0.7rem;">Cleared</div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-3 text-muted small">
                            <i class="fas fa-calendar-alt me-1 opacity-50"></i>
                            Registered: <?php echo date('M d, Y', strtotime($r['created_at'] ?? 'now')); ?>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Edit Form -->
                <div class="col-lg-8">
                    <?php if ($error): ?>
                        <div class="alert alert-danger border-0 shadow-sm rounded-4 mb-4">
                            <i class="fas fa-circle-exclamation me-2"></i><?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>
                    <?php if ($success): ?>
                        <div class="alert alert-success border-0 shadow-sm rounded-4 mb-4">
                            <i class="fas fa-circle-check me-2"></i><?php echo htmlspecialchars($success); ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <!-- Personal Info -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 py-4 px-4 border-bottom">
                                <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-user me-2 text-gold"></i>Personal Information</h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold small text-navy">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" name="last_name" class="form-control bg-light"
                                            value="<?php echo htmlspecialchars($r['last_name']); ?>" required>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label fw-semibold small text-navy">First Name <span class="text-danger">*</span></label>
                                        <input type="text" name="first_name" class="form-control bg-light"
                                            value="<?php echo htmlspecialchars($r['first_name']); ?>" required>
                                    </div>
                                    <div class="col-md-2">
                                        <label class="form-label fw-semibold small text-navy">M.I.</label>
                                        <input type="text" name="middle_initial" maxlength="5" class="form-control bg-light"
                                            value="<?php echo htmlspecialchars($r['middle_initial'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label fw-semibold small text-navy">Email Address <span class="text-danger">*</span></label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                                            <input type="email" name="email" class="form-control bg-light border-start-0"
                                                value="<?php echo htmlspecialchars($r['email']); ?>" required>
                                        </div>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label fw-semibold small text-navy">Account Status</label>
                                        <select name="status" class="form-select bg-light">
                                            <option value="active"    <?php if ($r['status']==='active')    echo 'selected'; ?>>Active</option>
                                            <option value="pending"   <?php if ($r['status']==='pending')   echo 'selected'; ?>>Pending</option>
                                            <option value="suspended" <?php if ($r['status']==='suspended') echo 'selected'; ?>>Suspended</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Password Reset -->
                        <div class="card border-0 shadow-sm rounded-4 mb-4">
                            <div class="card-header bg-white border-0 py-4 px-4 border-bottom">
                                <h5 class="mb-0 fw-bold text-navy"><i class="fas fa-lock me-2 text-gold"></i>Reset Password <small class="text-muted fw-normal fs-6">(leave blank to keep current)</small></h5>
                            </div>
                            <div class="card-body p-4">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-navy">New Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-key"></i></span>
                                            <input type="password" name="new_password" id="newPass" class="form-control bg-light border-start-0 border-end-0" placeholder="New password">
                                            <span class="input-group-text bg-light border-start-0 text-muted" style="cursor:pointer;" onclick="toggleVis('newPass', this)"><i class="far fa-eye"></i></span>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-semibold small text-navy">Confirm Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-shield-alt"></i></span>
                                            <input type="password" name="confirm_password" id="confPass" class="form-control bg-light border-start-0 border-end-0" placeholder="Confirm">
                                            <span class="input-group-text bg-light border-start-0 text-muted" style="cursor:pointer;" onclick="toggleVis('confPass', this)"><i class="far fa-eye"></i></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-3 justify-content-end">
                            <a href="researchers" class="btn btn-light border rounded-pill px-4">
                                <i class="fas fa-arrow-left me-2"></i>Back
                            </a>
                            <button type="submit" name="save" class="btn btn-navy rounded-pill px-5 shadow-sm">
                                <i class="fas fa-save me-2"></i>Save Changes
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function toggleVis(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon = btn.querySelector('i');
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.replace('fa-eye', 'fa-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.replace('fa-eye-slash', 'fa-eye');
    }
}
</script>

<?php include '../includes/footer.php'; ?>
