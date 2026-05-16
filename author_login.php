<?php
session_start();
require_once 'config/database.php';

// If already logged in, redirect to respective dashboard
if (isset($_SESSION['user_id'])) {
    header("Location: " . $_SESSION['role'] . "/");
    exit();
}

$error = "";
$success = "";
$role = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {
    $email = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if (!password_verify($password, $user['password'])) {
                $error = "Incorrect password. Please try again.";
            } elseif ($user['status'] === 'suspended') {
                $error = "Your account has been suspended. Please contact the REC Administrator.";
            } elseif ($user['status'] === 'pending') {
                $error = "Your account is pending Admin approval. Please check back later.";
            } elseif ($user['status'] !== 'active') {
                $error = "Your account is not active. Please contact Admin.";
            } else {
                // Status is 'active' — grant access
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['name'] = $user['first_name'] . ' ' . $user['last_name'];
                $_SESSION['role'] = 'author';
                $_SESSION['email'] = $user['email'];

                $audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Successful Login')");
                $audit->execute([$user['user_id']]);

                $success = "Welcome back, " . $_SESSION['name'] . "!";
                $role = 'author';
            }
        } else {
            $error = "No account found with that email.";
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
                        <h3 class="fw-bold">Committee Portal</h3>
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
                    <div class="mb-4 text-center">
                        <h4 class="fw-bold text-navy mb-1">Welcome Back, Researcher</h4>
                        <p class="text-muted small">Enter your credentials to access your workspace.</p>
                    </div>

                    <form action="author_login" method="POST" id="loginForm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-navy">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="far fa-envelope"></i></span>
                                <input type="email" name="username" class="form-control bg-light border-start-0 py-2 pt-2 pb-2" placeholder="e.g. author@domain.com" required>
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label small fw-bold text-navy">Password</label>
                            <div class="input-group">
                                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fas fa-lock"></i></span>
                                <input type="password" name="password" id="password" class="form-control bg-light border-start-0 border-end-0 py-2 pt-2 pb-2" placeholder="Enter password" required>
                                <span class="input-group-text bg-light border-start-0 text-muted" style="cursor: pointer;" id="togglePassword">
                                    <i class="far fa-eye"></i>
                                </span>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="remember">
                                <label class="form-check-label small text-muted" for="remember">Remember me</label>
                            </div>
                            <a href="<?php echo BASE_URL; ?>forgot_password" class="text-navy small fw-bold text-decoration-none">Forgot password?</a>
                        </div>

                        <button type="submit" name="login_btn" class="btn btn-navy w-100 py-3 rounded-pill fw-bold shadow-sm mb-4 theme-btn-hover">
                            Login to Dashboard <i class="fas fa-arrow-right ms-2"></i>
                        </button>

                        <div class="text-center">
                            <p class="small text-muted mb-0">Don't have an author account?</p>
                            <a href="<?php echo BASE_URL; ?>author_register" class="text-gold fw-bold text-decoration-none small">Register Now</a>
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
document.getElementById('togglePassword').addEventListener('click', function (e) {
    const password = document.getElementById('password');
    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
    password.setAttribute('type', type);
    this.querySelector('i').classList.toggle('fa-eye');
    this.querySelector('i').classList.toggle('fa-eye-slash');
});
</script>

<style>
.author-login-bg { padding: 40px 15px; }
.form-control:focus { box-shadow: none; border-color: #cbd5e1; }
.input-group-text { border-color: #dee2e6; }
.theme-btn-hover:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0,0,0,0.2) !important; transition: all 0.3s; color: white !important;}
.hover-white:hover { color: white !important; transition: 0.3s; }
</style>

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Login Failed',
            text: '<?php echo $error; ?>',
            confirmButtonColor: '#1a2b4b'
        });
    </script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Success!',
            text: '<?php echo $success; ?>',
            timer: 1500,
            showConfirmButton: false,
            willClose: () => {
                window.location.href = '<?php echo BASE_URL . $role; ?>/';
            }
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>
