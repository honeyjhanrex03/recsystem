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
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    $sig_data = $_POST['signature_data'] ?? '';
    $sig_file = $_FILES['signature_file'] ?? null;
    $rank = trim($_POST['academic_rank'] ?? '');
    $degree = trim($_POST['academic_degree'] ?? '');
    $prof_file = $_FILES['profile_image'] ?? null;

    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        $error = "Please fill in all required fields.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM admins WHERE email = ?");
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

                $prof_filename = null;
                if ($prof_file && $prof_file['error'] === 0) {
                    $ext = strtolower(pathinfo($prof_file['name'], PATHINFO_EXTENSION));
                    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                        $prof_filename = 'prof_' . time() . '_' . uniqid() . '.' . $ext;
                        if (!is_dir('uploads/profiles')) mkdir('uploads/profiles', 0777, true);
                        move_uploaded_file($prof_file['tmp_name'], 'uploads/profiles/' . $prof_filename);
                    }
                }

                if (!$error) {
                    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $pdo->prepare("INSERT INTO admins (name, email, password, role, academic_rank, academic_degree, profile_image, status, signature) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
                    $stmt->execute([$name, $email, $hashed_password, $role, $rank, $degree, $prof_filename, $sig_filename]);

                    $success = "Registration successful! Please wait for Admin approval.";
                }
            } catch (Exception $e) {
                $error = "System error: " . $e->getMessage();
            }
        }
    }
}

// Custom body class for consistent styling
$bodyClass = "login-page";
include 'includes/header.php';
?>

<div class="login-container" style="max-width: 1100px;">
    <!-- Moved Illustration to the Left Side -->
    <div class="login-right"
        style="flex: 0.8; background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%); border-left: none; border-right: 1px solid #f0f0f0;">
        <div class="decor decor-1"><i class="fas fa-plus"></i></div>
        <div class="decor decor-2"><i class="fas fa-circle"></i></div>
        <div class="decor decor-3"><i class="fas fa-star"></i></div>

        <div class="text-center px-4" style="z-index: 10;">
            <div class="mb-4">
                <i class="fas fa-file-signature fa-5x text-gold opacity-50"></i>
            </div>
            <h3 class="fw-bold text-navy mb-3">REC Registry</h3>
            <p class="text-muted mb-4 small px-3">Join the Davao del Norte State College Research Ethics Committee. Your
                account will undergo verification by the system administrator for security compliance.</p>
            <div class="d-flex justify-content-center gap-2">
                <span class="badge bg-navy-light text-navy px-2 py-1"
                    style="font-size: 0.65rem; background: rgba(26,43,75,0.05);">SECURE</span>
                <span class="badge bg-navy-light text-navy px-2 py-1"
                    style="font-size: 0.65rem; background: rgba(26,43,75,0.05);">COMPLIANT</span>
                <span class="badge bg-navy-light text-navy px-2 py-1"
                    style="font-size: 0.65rem; background: rgba(26,43,75,0.05);">ETHICAL</span>
            </div>
        </div>
    </div>

    <!-- Registration Form on the Right Side -->
    <div class="login-left" style="padding: 40px;">
        <div class="logo-section" style="margin-bottom: 30px;">
            <div class="logo-wrapper">
                <div class="logo-image">
                    <img src="assets/images/logo.png" alt="DNSC REC Logo" style="width: 80px; height: auto;">
                </div>
                <div class="logo-text-group">
                    <h1>DAVAO DEL NORTE STATE COLLEGE</h1>
                    <p>Research Ethics Committee Review System</p>
                </div>
            </div>
        </div>

        <div class="login-header" style="margin-bottom: 20px;">
            <h4 class="fw-bold text-navy">REC Member Registration</h4>
        </div>

        <form action="register" method="POST" id="registerForm" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label fw-bold text-navy small mb-1">Full Name</label>
                <div class="form-group mb-0">
                    <i class="far fa-user"></i>
                    <input type="text" name="name" placeholder="Last Name, First Name, Middle Name or Initial" required>
                </div>
            </div>

            <div class="form-group mb-3">
                <i class="far fa-envelope"></i>
                <input type="email" name="email" placeholder="Email Address" required>
            </div>

            <div class="form-group mb-3">
                <i class="fas fa-user-tag"></i>
                <select name="role" class="form-control" required
                    style="padding-left: 55px !important; background: #f8fafc !important; border-radius: 12px; height: auto; padding-top: 16px; padding-bottom: 16px; -webkit-appearance: none; -moz-appearance: none; appearance: none;">
                    <option value="" disabled selected>Select System Role</option>
                    <option value="rec_member">REC Member</option>
                    <option value="rec_staff">REC Staff</option>
                </select>
                <i class="fas fa-chevron-down"
                    style="left: auto; right: 20px; font-size: 0.8rem; pointer-events: none;"></i>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-6">
                    <div class="form-group mb-0 position-relative">
                        <i class="fas fa-award"></i>
                        <input type="text" name="academic_rank" placeholder="Academic Rank (Optional)">
                    </div>
                    <small class="text-muted" style="font-size: 0.65rem; margin-left: 55px;">e.g. Assistant Professor II</small>
                </div>
                <div class="col-md-6">
                    <div class="form-group mb-0 position-relative">
                        <i class="fas fa-graduation-cap"></i>
                        <input type="text" name="academic_degree" placeholder="Highest Degree (Optional)">
                    </div>
                    <small class="text-muted" style="font-size: 0.65rem; margin-left: 55px;">e.g. MA in Education</small>
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label fw-bold text-navy small mb-1">Profile Photo (Optional)</label>
                <div class="form-group mb-0">
                    <i class="fas fa-camera"></i>
                    <input type="file" name="profile_image" class="form-control" accept="image/*" style="padding-left: 55px !important; padding-top: 15px;">
                </div>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-6">
                    <div class="form-group mb-0 position-relative">
                        <i class="fas fa-lock"></i>
                        <input type="password" name="password" id="regPassword" placeholder="Password" required
                            style="padding-left: 50px !important;">
                        <i class="far fa-eye position-absolute" id="toggleRegPassword"
                            style="left: auto; right: 15px; cursor: pointer; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; font-size: 0.9rem;"></i>
                    </div>
                </div>
                <div class="col-6">
                    <div class="form-group mb-0 position-relative">
                        <i class="fas fa-shield-alt"></i>
                        <input type="password" name="confirm_password" id="regConfirmPassword" placeholder="Confirm"
                            required style="padding-left: 50px !important;">
                        <i class="far fa-eye position-absolute" id="toggleRegConfirmPassword"
                            style="left: auto; right: 15px; cursor: pointer; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10; font-size: 0.9rem;"></i>
                    </div>
                </div>
            </div>

            <script>
                function setupPasswordToggle(toggleId, inputId) {
                    document.getElementById(toggleId).addEventListener('click', function(e) {
                        const input = document.getElementById(inputId);
                        const type = input.getAttribute('type') === 'password' ? 'text' : 'password';
                        input.setAttribute('type', type);
                        this.classList.toggle('fa-eye');
                        this.classList.toggle('fa-eye-slash');
                    });
                }
                setupPasswordToggle('toggleRegPassword', 'regPassword');
                setupPasswordToggle('toggleRegConfirmPassword', 'regConfirmPassword');
            </script>

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
                                style="touch-action: none; width: 100%; min-height: 300px; background: #ffffff; cursor: crosshair;"></canvas>
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

            <div class="d-grid">
                <button type="submit" name="register_btn" class="login-btn">
                    <i class="fas fa-user-plus me-2"></i> Initialise Account
                </button>
                <div class="text-center mt-3">
                    <a href="<?php echo BASE_URL; ?>login" class="text-muted small text-decoration-none hover-gold">
                        Already registered? <span class="text-navy fw-bold">Sign In Here</span>
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="login-page-footer animate__animated animate__fadeInUp">
    <p>INSPIRING CHANGE, CREATING FUTURES</p>
    <div class="social-links">
        <a href="https://www.facebook.com/profile.php?id=61566750576733" target="_blank" title="Official Facebook Page"><i class="fab fa-facebook"></i></a>
        <a href="https://dnsc.edu.ph/" target="_blank" title="DNSC Official Website"><i class="fas fa-globe"></i></a>
    </div>
</div>

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

<script src="https://cdn.jsdelivr.net/npm/signature_pad@4.0.0/dist/signature_pad.umd.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const canvas = document.getElementById('signature-pad');
        const signaturePad = new SignaturePad(canvas, {
            backgroundColor: 'rgba(255, 255, 255, 0)',
            penColor: 'rgb(26, 43, 75)'
        });

        const clearButton = document.getElementById('clear-sig');
        clearButton.addEventListener('click', function() {
            signaturePad.clear();
            document.getElementById('signature_data').value = '';
        });

        const form = document.getElementById('registerForm');
        form.addEventListener('submit', function(e) {
            const activeTab = document.querySelector('#sigTab .nav-link.active').id;

            if (activeTab === 'draw-tab') {
                if (signaturePad.isEmpty()) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'Signature Required',
                        text: 'Please draw your signature in the pad.'
                    });
                } else {
                    document.getElementById('signature_data').value = signaturePad.toDataURL('image/png');
                }
            } else {
                // If on upload tab, ensure we clear any drawing data to avoid PHP confusion
                document.getElementById('signature_data').value = '';
                const fileInput = document.querySelector('input[name="signature_file"]');
                if (!fileInput.files.length) {
                    e.preventDefault();
                    Swal.fire({
                        icon: 'warning',
                        title: 'File Required',
                        text: 'Please select a PNG signature file to upload.'
                    });
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

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Registration Failed',
            text: '<?php echo addslashes($error); ?>',
            confirmButtonColor: '#1a2b4b'
        });
    </script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Account Created',
            text: '<?php echo addslashes($success); ?>',
            confirmButtonColor: '#1a2b4b'
        }).then(() => {
            window.location.href = '<?php echo BASE_URL; ?>login';
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>