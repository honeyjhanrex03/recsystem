<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id)
    die("Invalid User ID");

$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user)
    die("User not found");

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $role = $_POST['role'];
    $new_password = $_POST['new_password'] ?? '';
    $sig_data = $_POST['signature_data'] ?? '';
    $resolve_id = $_GET['resolve_reset'] ?? null;

    try {
        $pdo->beginTransaction();

        $sig_filename = $user['signature'];

        // Handle new signature drawing
        if (!empty($sig_data)) {
            $sig_data = str_replace('data:image/png;base64,', '', $sig_data);
            $sig_data = str_replace(' ', '+', $sig_data);
            $data = base64_decode($sig_data);
            $sig_filename = 'sig_' . time() . '_' . uniqid() . '.png';
            file_put_contents('../uploads/signatures/' . $sig_filename, $data);
        }
        // Handle new signature file upload
        elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $sig_filename = 'sig_file_' . time() . '_' . uniqid() . '.png';
                move_uploaded_file($_FILES['signature_file']['tmp_name'], '../uploads/signatures/' . $sig_filename);
            }
        }

        // Update user (including password if provided)
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            $update = $pdo->prepare("UPDATE admins SET name = ?, email = ?, role = ?, signature = ?, password = ? WHERE admin_id = ?");
            $update->execute([$name, $email, $role, $sig_filename, $hashed, $user_id]);
        } else {
            $update = $pdo->prepare("UPDATE admins SET name = ?, email = ?, role = ?, signature = ? WHERE admin_id = ?");
            $update->execute([$name, $email, $role, $sig_filename, $user_id]);
        }

        // Mark reset request as completed if applicable
        if ($resolve_id) {
            $stmtR = $pdo->prepare("UPDATE password_resets SET status = 'completed' WHERE reset_id = ? AND user_id = ?");
            $stmtR->execute([$resolve_id, $user_id]);
        }

        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $log_msg = !empty($new_password) ? "Manually updated profile and reset password for $name" : "Updated profile and signature for $name";
        $log->execute([$_SESSION['user_id'], "$log_msg (ID $user_id)"]);

        $pdo->commit();
        $success = "User assets synchronized successfully.";

        // Refresh
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = $e->getMessage();
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Personnel Record Management";
        $workspaceSubtitle = "REC User Identity & Authorization Control";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 animate-up">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" id="editUserForm" enctype="multipart/form-data">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h4 class="fw-bold text-navy mb-4">Identity Profile</h4>
                                        
                                        <?php if (isset($_GET['resolve_reset'])): ?>
                                            <div class="alert alert-warning border-0 shadow-sm small py-2 mb-4">
                                                <i class="fas fa-exclamation-triangle me-2"></i> Resolving Reset Request #<?php echo $_GET['resolve_reset']; ?>
                                            </div>
                                        <?php endif; ?>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Identification</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Email System ID</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Privilege Level</label>
                                            <select name="role" class="form-select">
                                                <option value="admin" <?php echo $user['role'] == 'admin' ? 'selected' : ''; ?>>Administrative</option>
                                                <option value="rec_chair" <?php echo $user['role'] == 'rec_chair' ? 'selected' : ''; ?>>REC Chair</option>
                                                <option value="rec_staff" <?php echo $user['role'] == 'rec_staff' ? 'selected' : ''; ?>>REC Staff (Staff)</option>
                                                <option value="rec_member" <?php echo $user['role'] == 'rec_member' ? 'selected' : ''; ?>>Ethics Reviewer</option>
                                                <option value="rec_secretary" <?php echo $user['role'] == 'rec_secretary' ? 'selected' : ''; ?>>REC Secretary</option>
                                            </select>
                                        </div>

                                        <div class="bg-light p-3 rounded-4 mt-2">
                                            <h6 class="fw-bold text-navy mb-3"><i class="fas fa-shield-alt me-2 text-gold"></i>Security Override</h6>
                                            <div class="mb-0">
                                                <label class="form-label small text-muted fw-bold">FORCE NEW PASSWORD</label>
                                                <div class="position-relative">
                                                    <input type="password" name="new_password" id="new_password" class="form-control" placeholder="••••••••" autocomplete="new-password">
                                                    <i class="far fa-eye position-absolute" id="toggleP" style="right: 15px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #64748b;"></i>
                                                </div>
                                                <small class="text-muted" style="font-size: 0.65rem;">Leave empty if you don't want to change the password.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <script>
                                        document.getElementById('toggleP').addEventListener('click', function() {
                                            const input = document.getElementById('new_password');
                                            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                                            input.setAttribute('type', type);
                                            this.classList.toggle('fa-eye');
                                            this.classList.toggle('fa-eye-slash');
                                        });
                                    </script>

                                    <div class="col-md-6 border-start ps-md-4">
                                        <h4 class="fw-bold text-navy mb-4">Digital Authorization</h4>
                                        <div class="mb-3">
                                            <label
                                                class="form-label fw-bold small text-muted text-uppercase d-block">Current
                                                Signature Asset</label>
                                            <div class="p-3 border rounded bg-light text-center"
                                                style="min-height: 100px;">
                                                <?php if ($user['signature']): ?>
                                                    <img src="../uploads/signatures/<?php echo $user['signature']; ?>"
                                                        alt="Signature" style="max-height: 80px; width: auto;">
                                                <?php else: ?>
                                                    <div class="py-3 text-muted small">No signature registered.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Update
                                                Authorization Asset</label>
                                            <ul class="nav nav-tabs mb-2" id="sigTab" role="tablist">
                                                <li class="nav-item"><button class="nav-link active py-1 small"
                                                        id="draw-tab" data-bs-toggle="tab" data-bs-target="#draw"
                                                        type="button">Draw</button></li>
                                                <li class="nav-item"><button class="nav-link py-1 small" id="upload-tab"
                                                        data-bs-toggle="tab" data-bs-target="#upload"
                                                        type="button">Upload</button></li>
                                            </ul>
                                            <div class="tab-content border rounded p-2 bg-white" id="sigTabContent">
                                                <div class="tab-pane fade show active" id="draw">
                                                    <canvas id="signature-pad" class="signature-pad" width="500"
                                                        height="300"
                                                        style="touch-action: none; width: 100%; min-height: 300px; cursor: crosshair;"></canvas>
                                                    <div class="text-end mt-1">
                                                        <button type="button"
                                                            class="btn btn-sm btn-link text-danger p-0 small"
                                                            id="clear-sig">Clear Drawing</button>
                                                    </div>
                                                    <input type="hidden" name="signature_data" id="signature_data">
                                                </div>
                                                <div class="tab-pane fade" id="upload">
                                                    <input type="file" name="signature_file"
                                                        class="form-control form-control-sm" accept="image/png">
                                                    <small class="text-muted" style="font-size: 0.7rem;">Transparency
                                                        (PNG) recommended.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                                    <a href="users" class="btn btn-link text-muted text-decoration-none">← Return to
                                        Registry</a>
                                    <button type="submit"
                                        class="btn btn-navy px-5 rounded-pill fw-bold shadow">Synchronize
                                        Account</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(26, 43, 75)'
        });

        document.getElementById('clear-sig').addEventListener('click', () => signaturePad.clear());

        document.getElementById('editUserForm').addEventListener('submit', function (e) {
            if (!signaturePad.isEmpty()) {
                document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
            }
        });

        function resizeCanvas() {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext("2d").scale(ratio, ratio);
            signaturePad.clear();
        }
        window.onresize = resizeCanvas;
        resizeCanvas();
    });
</script>

<?php if ($success): ?>
    <script>Swal.fire({ title: 'System Updated', text: '<?php echo $success; ?>', icon: 'success' }).then(() => { window.location.href = 'users'; });</script>
<?php endif; ?>
<?php if ($error): ?>
    <script>Swal.fire({ title: 'Update Error', text: '<?php echo addslashes($error); ?>', icon: 'error' });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
