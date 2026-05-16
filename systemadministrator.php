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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login_btn'])) {
    $email = trim($_POST['username']);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM admins WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user) {
            if ($user['status'] === 'inactive') {
                $error = "Your account is deactivated. Please contact Admin.";
            } elseif ($user['status'] === 'pending') {
                $error = "Your account is pending Admin approval. Please check back later.";
            } elseif (password_verify($password, $user['password'])) {
                $_SESSION['user_id'] = $user['admin_id'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['email'] = $user['email'];

                $audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Successful Login')");
                $audit->execute([$user['admin_id']]);

                // We'll use JS to redirect after showing success alert
                $success = "Welcome back, " . $user['name'] . "!";
                $role = $user['role'];
            } else {
                $error = "Incorrect password. Please try again.";
            }
        } else {
            $error = "No account found with that email.";
        }
    }
}

include 'includes/header.php';
?>

<div class="login-container">
    <div class="login-left">
        <div class="logo-section">
            <div class="logo-wrapper">
                <div class="logo-image">
                    <img src="assets/images/logo.png" alt="DNSC Logo" style="width: 80px; height: auto;">
                </div>
                <div class="logo-text-group">
                    <h1>DAVAO DEL NORTE STATE COLLEGE</h1>
                    <p>Research Ethics Committee Review System</p>
                </div>
            </div>
        </div>

        <div class="login-header">
            <h2>Official System Portal</h2>
            <p>Access strictly for REC System Administrators, Reviewers, and REC Staff only. Committees must use the Committee Portal.</p>
        </div>

        <form action="systemadministrator" method="POST" id="loginForm">
            <div class="form-group mb-4">
                <i class="far fa-envelope"></i>
                <input type="email" name="username" placeholder="Email Address" required>
            </div>

            <div class="form-group mb-4">
                <i class="fas fa-lock"></i>
                <input type="password" name="password" id="password" placeholder="Password" required>
                <i class="far fa-eye position-absolute" id="togglePassword"
                    style="left: auto; right: 20px; cursor: pointer; top: 50%; transform: translateY(-50%); color: var(--text-muted); z-index: 10;"></i>
            </div>

            <script>
                document.getElementById('togglePassword').addEventListener('click', function (e) {
                    const password = document.getElementById('password');
                    const type = password.getAttribute('type') === 'password' ? 'text' : 'password';
                    password.setAttribute('type', type);
                    this.classList.toggle('fa-eye');
                    this.classList.toggle('fa-eye-slash');
                });
            </script>

            <div class="d-flex justify-content-between align-items-center mb-4">
                <a href="<?php echo BASE_URL; ?>forgot_password" class="forgot-link">Forget password?</a>
            </div>

            <div>
                <button type="submit" name="login_btn" class="login-btn w-100 py-3">Login to Dashboard</button>
                <div class="text-center mt-3">
                    <p class="small text-muted mb-0">System or Reviewer Access required?</p>
                    <a href="<?php echo BASE_URL; ?>register"
                        class="text-navy fw-bold text-decoration-none small">Register System Role</a>
                </div>
            </div>
        </form>
    </div>

    <div class="login-right">
        <div class="decor decor-1"><i class="fas fa-plus"></i></div>
        <div class="decor decor-2"><i class="fas fa-circle"></i></div>
        <div class="decor decor-3"><i class="fas fa-star"></i></div>
        <div class="decor" style="top: 15%; left: 20%; color: #10b981; font-size: 18px;"><i
                class="fas fa-square-full"></i></div>
        <div class="decor" style="bottom: 20%; right: 10%; color: #8b5cf6; font-size: 20px;"><i
                class="fas fa-shapes"></i></div>

        <img src="assets/images/researchers.jpg" alt="DNSC REC Review Illustration" class="illustration-img" id="main-illustration">

        <script>
            document.getElementById('main-illustration').onerror = function () {
                this.src = 'https://img.freepik.com/free-vector/scientists-working-science-lab_23-2148483832.jpg';
            };
        </script>
    </div>
</div>

<div class="login-page-footer animate__animated animate__fadeInUp">
    <p>INSPIRING CHANGE, CREATING FUTURES</p>
    <div class="social-links">
        <a href="https://www.facebook.com/profile?id=61566750576733" target="_blank" title="Official Facebook Page"><i class="fab fa-facebook"></i></a>
        <a href="https://dnsc.edu.ph/" target="_blank" title="DNSC Official Website"><i class="fas fa-globe"></i></a>
    </div>
</div>

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
