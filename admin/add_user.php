<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save_user'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $sig_data = $_POST['signature_data'] ?? '';

    if (empty($name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } else {
        try {
            $pdo->beginTransaction();

            $sig_filename = null;
            if (!empty($sig_data)) {
                $sig_data = str_replace('data:image/png;base64,', '', $sig_data);
                $sig_data = str_replace(' ', '+', $sig_data);
                $data = base64_decode($sig_data);
                $sig_filename = 'sig_' . time() . '_' . uniqid() . '.png';
                file_put_contents('../uploads/signatures/' . $sig_filename, $data);
            } elseif (isset($_FILES['signature_file']) && $_FILES['signature_file']['error'] === 0) {
                $ext = strtolower(pathinfo($_FILES['signature_file']['name'], PATHINFO_EXTENSION));
                if ($ext === 'png') {
                    $sig_filename = 'sig_file_' . time() . '_' . uniqid() . '.png';
                    move_uploaded_file($_FILES['signature_file']['tmp_name'], '../uploads/signatures/' . $sig_filename);
                }
            }

            $hashed_pass = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role, status, signature) VALUES (?, ?, ?, ?, 'active', ?)");
            $stmt->execute([$name, $email, $hashed_pass, $role, $sig_filename]);

            $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $log->execute([$_SESSION['user_id'], "Manually created user $name with role $role"]);

            $pdo->commit();
            $success = "User $name has been created and activated.";
        } catch (PDOException $e) {
            $pdo->rollBack();
            if ($e->getCode() == 23000) {
                $error = "This email address is already registered.";
            } else {
                $error = "Error: " . $e->getMessage();
            }
        }
    }
}

include '../includes/header.php';
?>

<div class="d-flex dashboard-page" id="wrapper">
    <?php include '../includes/sidebar.php'; ?>
    <div id="page-content-wrapper">
        <?php 
        $workspaceTitle = "Create User Account";
        $workspaceSubtitle = "Add new staff and reviewers to the system";
        include '../includes/topbar.php'; 
        ?>

        <div class="container-fluid p-4">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card border-0 shadow-lg rounded-4 overflow-hidden animate-up">
                        <div class="card-header bg-navy text-white text-center py-4">
                            <i class="fas fa-user-plus fa-3x mb-2 text-warning"></i>
                            <h3 class="fw-bold mb-0">Add New User Account</h3>
                        </div>
                        <div class="card-body p-4 p-md-5">
                            <form action="add_user" method="POST" id="addUserForm" enctype="multipart/form-data">
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Full
                                            Name</label>
                                        <input type="text" name="name" class="form-control form-control-lg"
                                            placeholder="e.g. Dr. John Doe" required>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Email
                                            Address</label>
                                        <input type="email" name="email" class="form-control form-control-lg"
                                            placeholder="user@dnsc.edu.ph" required>
                                    </div>
                                </div>
                                <div class="row g-4 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">System
                                            Role</label>
                                        <select name="role" class="form-select form-select-lg" required>
                                            <option value="rec_member">Reviewer (REC Member)</option>
                                            <option value="rec_staff">REC Staff</option>
                                            <option value="rec_chair">REC Chair</option>
                                            <option value="admin">System Admin</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label fw-bold text-navy small text-uppercase">Initial
                                            Password</label>
                                        <input type="password" name="password" class="form-control form-control-lg"
                                            required>
                                    </div>
                                </div>

                                <div class="border-top pt-4 mt-4">
                                    <label
                                        class="form-label fw-bold text-navy small text-uppercase d-block mb-3">User 
                                        Signature</label>
                                    <div class="row g-4">
                                        <div class="col-md-8">
                                            <ul class="nav nav-pills mb-3" id="sigTab" role="tablist">
                                                <li class="nav-item"><button
                                                        class="nav-link active py-2 px-4 shadow-sm me-2" id="draw-tab"
                                                        data-bs-toggle="tab" data-bs-target="#draw"
                                                        type="button">Digital Draw</button></li>
                                                <li class="nav-item"><button class="nav-link py-2 px-4 shadow-sm"
                                                        id="upload-tab" data-bs-toggle="tab" data-bs-target="#upload"
                                                        type="button">File Upload</button></li>
                                            </ul>
                                            <div class="tab-content border rounded-4 p-3 bg-light shadow-inner"
                                                id="sigTabContent">
                                                <div class="tab-pane fade show active" id="draw">
                                                    <canvas id="signature-pad" class="signature-pad w-100" height="300"
                                                        style="touch-action: none; background: #fff; border-radius: 12px; cursor: crosshair; min-height: 300px;"></canvas>
                                                    <div class="text-end mt-2">
                                                        <button type="button" class="btn btn-sm text-danger fw-bold"
                                                            id="clear-sig"><i class="fas fa-eraser me-1"></i>
                                                            Clear</button>
                                                    </div>
                                                    <input type="hidden" name="signature_data" id="signature_data">
                                                </div>
                                                <div class="tab-pane fade" id="upload">
                                                    <div class="py-4 text-center">
                                                        <i class="fas fa-cloud-upload-alt fa-3x text-muted mb-3"></i>
                                                        <input type="file" name="signature_file" class="form-control"
                                                            accept="image/png">
                                                        <p class="text-muted small mt-2">Upload a high-resolution PNG
                                                            with transparent background.</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div
                                                class="bg-warning-light p-3 rounded-4 h-100 border border-warning border-opacity-25">
                                                <h6 class="fw-bold text-warning mb-2"><i
                                                        class="fas fa-info-circle me-1"></i> Note</h6>
                                                <p class="small text-muted mb-0">Signatures provided here will be
                                                    automatically authorized for the system operative's future
                                                    validation tasks.</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="d-grid mt-5">
                                    <button type="submit" name="save_user"
                                        class="btn btn-navy py-3 fs-5 fw-bold rounded-pill shadow">
                                        <i class="fas fa-check-circle me-2"></i> Create User Account
                                    </button>
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

        document.getElementById('addUserForm').addEventListener('submit', function (e) {
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

<?php if ($error): ?>
    <script>Swal.fire({ icon: 'error', title: 'Action Blocked', text: '<?php echo addslashes($error); ?>' });</script>
<?php endif; ?>
<?php if ($success): ?>
    <script>Swal.fire({ icon: 'success', title: 'User Created', text: '<?php echo $success; ?>' }).then(() => { window.location.href = 'users'; });</script>
<?php endif; ?>

<?php include '../includes/footer.php'; ?>
