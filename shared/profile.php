<?php
require_once '../includes/auth_check.php';
// All logged in users can access their profile
if (!isset($_SESSION['user_id'])) {
    header("Location: ../systemadministrator");
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$is_author = false;
$stmt = $pdo->prepare("SELECT * FROM admins WHERE admin_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if (!$user) {
    $stmtA = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
    $stmtA->execute([$user_id]);
    $user = $stmtA->fetch();
    if ($user) {
        $user['name'] = trim($user['first_name'] . ' ' . $user['middle_initial'] . ' ' . $user['last_name']);
        $user['role'] = 'author';
        $is_author = true;
    } else {
        die("User not found");
    }
}

$success = "";
$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $new_password = $_POST['new_password'] ?? '';
    $sig_data = $_POST['signature_data'] ?? '';
    $rank = trim($_POST['academic_rank'] ?? '');
    $degree = trim($_POST['academic_degree'] ?? '');
    $prof_file = $_FILES['profile_image'] ?? null;

    try {
        $pdo->beginTransaction();

        $sig_filename = $user['signature'];

        // Handle new signature drawing
        if (!$is_author && !empty($sig_data)) {
            $sig_data = str_replace('data:image/png;base64,', '', $sig_data);
            $sig_data = str_replace(' ', '+', $sig_data);
            $data = base64_decode($sig_data);
            $sig_filename = 'sig_' . time() . '_' . uniqid() . '.png';
            if(!is_dir('../uploads/signatures/')) mkdir('../uploads/signatures/', 0777, true);
            file_put_contents('../uploads/signatures/' . $sig_filename, $data);
        }
        // Handle new signature file upload
        elseif (!$is_author && isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === 0) {
            $ext = strtolower(pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION));
            if ($ext === 'png') {
                $sig_filename = 'sig_file_' . time() . '_' . uniqid() . '.png';
                if(!is_dir('../uploads/signatures/')) mkdir('../uploads/signatures/', 0777, true);
                move_uploaded_file($_FILES['signature_file']['tmp_name'], '../uploads/signatures/' . $sig_filename);
            }
        }

        $prof_filename = $user['profile_image'] ?? null;
        if ($prof_file && $prof_file['error'] === 0) {
            $ext = strtolower(pathinfo($prof_file['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                $prof_filename = 'prof_' . time() . '_' . uniqid() . '.' . $ext;
                if (!is_dir('../uploads/profiles/')) mkdir('../uploads/profiles/', 0777, true);
                move_uploaded_file($prof_file['tmp_name'], '../uploads/profiles/' . $prof_filename);
            }
        }

        // Update user
        if (!empty($new_password)) {
            $hashed = password_hash($new_password, PASSWORD_DEFAULT);
            if ($is_author) {
                $parts = explode(' ', $name, 2);
                $fname = $parts[0];
                $lname = $parts[1] ?? 'Unknown';
                $update = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, signature = ?, password = ? WHERE user_id = ?");
                $update->execute([$fname, $lname, $email, $sig_filename, $hashed, $user_id]);
            } else {
                $update = $pdo->prepare("UPDATE admins SET name = ?, email = ?, signature = ?, password = ?, academic_rank = ?, academic_degree = ?, profile_image = ? WHERE admin_id = ?");
                $update->execute([$name, $email, $sig_filename, $hashed, $rank, $degree, $prof_filename, $user_id]);
            }
        } else {
            if ($is_author) {
                $parts = explode(' ', $name, 2);
                $fname = $parts[0];
                $lname = $parts[1] ?? 'Unknown';
                $update = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ? WHERE user_id = ?");
                $update->execute([$fname, $lname, $email, $user_id]);
            } else {
                $update = $pdo->prepare("UPDATE admins SET name = ?, email = ?, signature = ?, academic_rank = ?, academic_degree = ?, profile_image = ? WHERE admin_id = ?");
                $update->execute([$name, $email, $sig_filename, $rank, $degree, $prof_filename, $user_id]);
            }
        }

        $msg = $is_author ? "Updated personal profile" : "Updated personal profile and signature";
        $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $log->execute([$user_id, $msg]);

        $pdo->commit();
        $success = "Profile updated successfully.";
        
        // Refresh session name if changed
        $_SESSION['name'] = $name;

        // Refresh user data
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
        $workspaceTitle = "My System Profile";
        $workspaceSubtitle = "Digital Identity & Institutional Authorization Settings";
        include '../includes/topbar.php'; 
        ?>
        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-sm rounded-4 animate-up">
                        <div class="card-body p-4 p-md-5">
                            <form method="POST" id="profileForm" enctype="multipart/form-data">
                                <div class="row g-4">
                                    <div class="col-md-6">
                                        <h4 class="fw-bold text-navy mb-4">Account Information</h4>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Full Name</label>
                                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($user['name']); ?>" required>
                                            <small class="text-muted" style="font-size:0.7rem;">Format: Last Name, First Name, M.I.</small>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Email Address</label>
                                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">System Role</label>
                                            <input type="text" class="form-control bg-light" value="<?php echo strtoupper($user['role']); ?>" readonly disabled>
                                        </div>

                                        <?php if(!$is_author): ?>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Academic Rank</label>
                                            <input type="text" name="academic_rank" class="form-control" value="<?php echo htmlspecialchars($user['academic_rank'] ?? ''); ?>" placeholder="e.g. Assistant Professor II">
                                        </div>
                                        <div class="mb-4">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Highest Degree</label>
                                            <input type="text" name="academic_degree" class="form-control" value="<?php echo htmlspecialchars($user['academic_degree'] ?? ''); ?>" placeholder="e.g. MA in Education">
                                        </div>
                                        <?php endif; ?>

                                        <div class="bg-light p-3 rounded-4 mt-2">
                                            <h6 class="fw-bold text-navy mb-3"><i class="fas fa-key me-2 text-gold"></i>Change Password</h6>
                                            <div class="mb-0">
                                                <label class="form-label small text-muted fw-bold">NEW PASSWORD</label>
                                                <input type="password" name="new_password" class="form-control" placeholder="••••••••">
                                                <small class="text-muted" style="font-size: 0.65rem;">Leave blank to keep current password.</small>
                                            </div>
                                        </div>
                                    </div>

                                    <?php if (!$is_author): ?>
                                    <div class="col-md-6 border-start ps-md-4">
                                        <div class="mb-4">
                                            <label class="form-label fw-bold small text-muted text-uppercase d-block">Profile Photo</label>
                                            <div class="d-flex align-items-center gap-3">
                                                <div class="avatar-preview shadow-sm rounded-circle overflow-hidden border" style="width: 80px; height: 80px; background: #f1f5f9;">
                                                    <?php if(!empty($user['profile_image'])): ?>
                                                        <img src="../uploads/profiles/<?php echo $user['profile_image']; ?>" alt="Profile" style="width:100%; height:100%; object-fit:cover;">
                                                    <?php else: ?>
                                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['name']); ?>&background=1a2b4b&color=fff" style="width:100%; height:100%; object-fit:cover;">
                                                    <?php endif; ?>
                                                </div>
                                                <div class="flex-grow-1">
                                                    <input type="file" name="profile_image" class="form-control form-control-sm" accept="image/*">
                                                    <small class="text-muted mt-1 d-block" style="font-size:0.65rem;">Recommended: Square JPG/PNG.</small>
                                                </div>
                                            </div>
                                        </div>

                                        <h4 class="fw-bold text-navy mb-4">Digital E-Signature</h4>
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase d-block">Current Signature</label>
                                            <div class="p-3 border rounded bg-light text-center" style="min-height: 100px;">
                                                <?php if (!empty($user['signature'])): ?>
                                                    <img src="../uploads/signatures/<?php echo $user['signature']; ?>" alt="Signature" style="max-height: 80px; width: auto;">
                                                <?php else: ?>
                                                    <div class="py-3 text-muted small">No signature registered.</div>
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted text-uppercase">Update Signature</label>
                                            <ul class="nav nav-tabs mb-2" id="sigTab" role="tablist">
                                                <li class="nav-item"><button class="nav-link active py-1 small" id="draw-tab" data-bs-toggle="tab" data-bs-target="#draw" type="button">Draw</button></li>
                                                <li class="nav-item"><button class="nav-link py-1 small" id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload" type="button">Upload</button></li>
                                            </ul>
                                            <div class="tab-content border rounded p-2 bg-white" id="sigTabContent">
                                                <div class="tab-pane fade show active" id="draw">
                                                    <canvas id="signature-pad" class="signature-pad" width="500" height="300" style="touch-action: none; width: 100%; min-height: 300px; cursor: crosshair; background: #fafafa;"></canvas>
                                                    <div class="text-end mt-1">
                                                        <button type="button" class="btn btn-sm btn-link text-danger p-0 small" id="clear-sig">Clear Drawing</button>
                                                    </div>
                                                    <input type="hidden" name="signature_data" id="signature_data">
                                                </div>
                                                <div class="tab-pane fade" id="upload">
                                                    <input type="file" name="signature_file" class="form-control form-control-sm" accept="image/png">
                                                    <small class="text-muted" style="font-size: 0.7rem;">Transparency (PNG) recommended.</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="d-flex justify-content-between mt-5 pt-3 border-top">
                                    <a href="../<?php echo $_SESSION['role']; ?>/" class="btn btn-link text-muted text-decoration-none">← Cancel Changes</a>
                                    <button type="submit" class="btn btn-navy px-5 rounded-pill fw-bold shadow">Save Profile Updates</button>
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

        document.getElementById('profileForm').addEventListener('submit', function (e) {
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
    <script>Swal.fire({ title: 'Success', text: '<?php echo $success; ?>', icon: 'success' });</script>
<?php endif; ?>
<?php if ($error): ?>
    <script>Swal.fire({ title: 'Error', text: '<?php echo addslashes($error); ?>', icon: 'error' });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
