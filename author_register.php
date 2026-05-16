<?php
session_start();
require_once 'config/database.php';

if (isset($_SESSION['user_id'])) {
    header("Location: " . BASE_URL . $_SESSION['role'] . "/");
    exit();
}

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register_btn'])) {
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $middle_initial = trim($_POST['middle_initial']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $sig_data = $_POST['signature_data'] ?? '';
    $sig_file = $_FILES['signature_file'] ?? null;

    if (empty($first_name) || empty($last_name) || empty($email) || empty($password)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetchColumn() > 0) {
            $error = "Email already registered.";
        } else {
                try {
                    $sig_filename = null;

                    if (!empty($sig_data)) {
                        $sig_data = str_replace('data:image/png;base64,', '', $sig_data);
                        $sig_data = str_replace(' ', '+', $sig_data);
                        $data = base64_decode($sig_data);
                        $sig_filename = 'sig_' . time() . '_' . uniqid() . '.png';
                        file_put_contents('uploads/signatures/' . $sig_filename, $data);
                    } elseif ($sig_file && $sig_file['error'] === 0) {
                        $ext = strtolower(pathinfo($sig_file['name'], PATHINFO_EXTENSION));
                        if ($ext === 'png') {
                            $sig_filename = 'sig_file_' . time() . '_' . uniqid() . '.png';
                            move_uploaded_file($sig_file['tmp_name'], 'uploads/signatures/' . $sig_filename);
                        } else {
                            $error = "Signature must be a PNG file.";
                        }
                    }

                    if (!$error) {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("INSERT INTO users (last_name, first_name, middle_initial, email, password, status, signature) VALUES (?, ?, ?, ?, ?, 'active', ?)");
                        $stmt->execute([$last_name, $first_name, $middle_initial, $email, $hashed_password, $sig_filename]);

                        $success = "Registration successful! You can now log in.";
                    }
                } catch (Exception $e) {
                    $error = "System error: " . $e->getMessage();
                }
        }
    }
}

// Custom body class for consistent styling
$bodyClass = "dashboard-page";
include 'includes/header.php';
?>

<div class="author-login-bg d-flex align-items-center justify-content-center" style="min-height: 100vh; background: url('https://images.unsplash.com/photo-1586281380349-632531db7ed4?auto=format&fit=crop&q=80&w=2000') center/cover fixed; position: relative;">
    <div style="position: absolute; top:0; left:0; width:100%; height:100%; background: rgba(15, 23, 42, 0.9);"></div>
    
    <div class="container position-relative z-1" style="max-width: 1000px; padding: 2rem 1rem;">
        <div class="card border-0 shadow-lg overflow-hidden" style="border-radius: 1.5rem; background: rgba(255,255,255,0.95); backdrop-filter: blur(10px);">
            <div class="row g-0">
                <!-- Branding Side -->
                <div class="col-lg-4 d-none d-lg-flex flex-column justify-content-center p-5 text-white" style="background: linear-gradient(135deg, #1e3a8a 0%, #3b82f6 100%);">
                    <div class="text-center mb-4">
                        <img src="assets/images/logo.png" alt="DNSC Logo" width="100" class="bg-white rounded-circle p-2 shadow-sm mb-3">
                        <h3 class="fw-bold">Committee Registry</h3>
                        <p class="opacity-75 small px-2">Submit and track your research protocols seamlessly through the DNSC REC Registry.</p>
                    </div>
                    <div class="mt-auto text-start">
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-check-circle text-gold me-2"></i>
                            <span class="small opacity-75">Faster Review Cycles</span>
                        </div>
                        <div class="d-flex align-items-center mb-2">
                            <i class="fas fa-check-circle text-gold me-2"></i>
                            <span class="small opacity-75">Real-time status tracking</span>
                        </div>
                        <div class="d-flex align-items-center">
                            <i class="fas fa-check-circle text-gold me-2"></i>
                            <span class="small opacity-75">Direct Verification</span>
                        </div>
                    </div>
                </div>

                <!-- Form Side -->
                <div class="col-lg-8 p-4 p-md-5 bg-white">
                    <div class="mb-4">
                        <h4 class="fw-bold text-navy mb-1">Create Researcher Account</h4>
                        <p class="text-muted small">Fill out the details below to initialize your author profile. An Administrator must verify your account before you can submit protocols.</p>
                    </div>

                    <form action="author_register" method="POST" id="registerForm" enctype="multipart/form-data">
                        <div class="row g-2 mb-3">
                            <div class="col-sm-5">
                                <label class="form-label fw-bold small text-navy mb-1">Last Name</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-user"></i></span>
                                    <input type="text" name="last_name" class="form-control bg-light border-start-0 py-2" placeholder="Dela Cruz" required>
                                </div>
                            </div>
                            <div class="col-sm-5">
                                <label class="form-label fw-bold small text-navy mb-1">First Name</label>
                                <div class="input-group">
                                    <input type="text" name="first_name" class="form-control bg-light py-2" placeholder="Juan" required>
                                </div>
                            </div>
                            <div class="col-sm-2">
                                <label class="form-label fw-bold small text-navy mb-1">M.I.</label>
                                <div class="input-group">
                                    <input type="text" name="middle_initial" maxlength="5" class="form-control bg-light py-2" placeholder="P">
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-navy mb-1">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                                <input type="email" name="email" class="form-control bg-light border-start-0 py-2" placeholder="e.g. author@domain.com" required>
                            </div>
                        </div>

                        <input type="hidden" name="role" value="author">

                        <div class="row g-2 mb-4">
                            <div class="col-sm-6">
                                <label class="form-label fw-bold small text-navy mb-1">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                    <input type="password" name="password" id="regPassword" class="form-control bg-light border-start-0 border-end-0 py-2" placeholder="Password" required>
                                    <span class="input-group-text bg-light border-start-0 text-muted" style="cursor:pointer;" id="toggleRegPassword">
                                        <i class="far fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <label class="form-label fw-bold small text-navy mb-1">Confirm Password</label>
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-shield-alt"></i></span>
                                    <input type="password" name="confirm_password" id="regConfirmPassword" class="form-control bg-light border-start-0 border-end-0 py-2" placeholder="Confirm" required>
                                    <span class="input-group-text bg-light border-start-0 text-muted" style="cursor:pointer;" id="toggleRegConfirmPassword">
                                        <i class="far fa-eye"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold text-navy small mb-2 d-block">Digital Authorization (Required)</label>

                            <ul class="nav nav-pills mb-2" id="sigTab" role="tablist">
                                <li class="nav-item shadow-sm me-2" role="presentation">
                                    <button class="nav-link active py-1 px-3 small rounded-pill" id="draw-tab" data-bs-toggle="tab"
                                        data-bs-target="#draw" type="button" role="tab">Digital Draw</button>
                                </li>
                                <li class="nav-item shadow-sm" role="presentation">
                                    <button class="nav-link py-1 px-3 small rounded-pill" id="upload-tab" data-bs-toggle="tab"
                                        data-bs-target="#upload" type="button" role="tab">Upload PNG</button>
                                </li>
                            </ul>
                            <div class="tab-content pt-2" id="sigTabContent">
                                <div class="tab-pane fade show active" id="draw" role="tabpanel">
                                    <div class="border rounded-4 bg-light overflow-hidden shadow-inner"
                                        style="position: relative; border-color: #e2e8f0 !important;">
                                        <canvas id="signature-pad" class="signature-pad" width="400" height="300"
                                            style="touch-action: none; width: 100%; min-height: 250px; background: #ffffff; cursor: crosshair;"></canvas>
                                        <button type="button" class="btn btn-sm btn-link text-danger position-absolute"
                                            id="clear-sig"
                                            style="bottom: 5px; right: 8px; font-size: 0.7rem; text-decoration: none; font-weight: 700;">
                                            <i class="fas fa-eraser me-1"></i> CLEAR
                                        </button>
                                    </div>
                                    <input type="hidden" name="signature_data" id="signature_data">
                                </div>
                                <div class="tab-pane fade" id="upload" role="tabpanel">
                                    <input type="file" name="signature_file" class="form-control" accept="image/png"
                                        style="padding-left: 15px !important;">
                                    <small class="text-muted mt-1 d-block">Please upload a high-resolution PNG with transparent
                                        background.</small>
                                </div>
                            </div>
                        </div>

                        <button type="submit" name="register_btn" class="btn btn-navy w-100 py-3 rounded-pill fw-bold shadow-sm mb-3 theme-btn-hover">
                            Initialize Committee Account <i class="fas fa-arrow-right ms-2"></i>
                        </button>
                        
                        <div class="text-center">
                            <p class="small text-muted mb-0">Already registered?</p>
                            <a href="<?php echo BASE_URL; ?>author_login" class="text-gold fw-bold text-decoration-none small">Sign In Here</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-4">
            <a href="<?php echo BASE_URL; ?>" class="text-white-50 small text-decoration-none hover-white"><i class="fas fa-arrow-left me-1"></i> Back to Homepage</a>
        </div>
    </div>
</div>

<script>
    function setupPasswordToggle(toggleId, inputId) {
        document.getElementById(toggleId).addEventListener('click', function () {
            const input = document.getElementById(inputId);
            const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
            input.setAttribute('type', type);
            this.querySelector('i').classList.toggle('fa-eye');
            this.querySelector('i').classList.toggle('fa-eye-slash');
        });
    }
    setupPasswordToggle('toggleRegPassword', 'regPassword');
    setupPasswordToggle('toggleRegConfirmPassword', 'regConfirmPassword');
</script>

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(26, 43, 75)'
        });

        const clearButton = document.getElementById('clear-sig');
        clearButton.addEventListener('click', function () {
            signaturePad.clear();
            document.getElementById('signature_data').value = '';
        });

        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function (e) {
            const activeTab = document.querySelector('#sigTab .nav-link.active').id;

            if (activeTab === 'draw-tab') {
                if (signaturePad.isEmpty()) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'Signature Required', text: 'Please draw your signature in the pad.' });
                } else {
                    document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                }
            } else {
                document.getElementById('signature_data').value = '';
                const fileInput = document.querySelector('input[name="signature_file"]');
                if (!fileInput.files.length) {
                    e.preventDefault();
                    Swal.fire({ icon: 'warning', title: 'File Required', text: 'Please select a PNG signature file to upload.' });
                }
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

<style>
    .hover-gold:hover {
        color: var(--gold) !important;
    }

    .shadow-inner {
        box-shadow: inset 0 2px 4px 0 rgba(0, 0, 0, 0.05);
    }

    .nav-pills .nav-link {
        background: #f1f5f9;
        color: #64748b;
    }

    .nav-pills .nav-link.active {
        background: var(--navy);
        color: #fff;
    }
</style>

<?php if ($error): ?>
    <script>Swal.fire({ icon: 'error', title: 'Registration Failed', text: '<?php echo addslashes($error); ?>', confirmButtonColor: '#1a2b4b' });</script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({ icon: 'success', title: 'Account Created', text: '<?php echo addslashes($success); ?>', confirmButtonColor: '#1a2b4b' }).then(() => {
            window.location.href = '<?php echo BASE_URL; ?>author_login';
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
