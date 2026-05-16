<?php
session_start();
require_once 'config/database.php';

$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['request_reset'])) {
    $email = trim($_POST['email']);
    $full_name = trim($_POST['full_name']);

    if (empty($email) || empty($full_name)) {
        $error = "Please provide both your registered email and full name.";
    } else {
        // Simple verification: Check if user exists with this email and name
        $stmt = $pdo->prepare("SELECT user_id, name FROM users WHERE email = ? AND name = ? LIMIT 1");
        $stmt->execute([$email, $full_name]);
        $user = $stmt->fetch();

        if ($user) {
            // Check if there's already a pending request
            $stmt = $pdo->prepare("SELECT COUNT(*) FROM password_resets WHERE user_id = ? AND status = 'pending'");
            $stmt->execute([$user['user_id']]);

            if ($stmt->fetchColumn() > 0) {
                $error = "You already have a pending reset request. Please wait for the Admin.";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO password_resets (user_id, status) VALUES (?, 'pending')");
                    $stmt->execute([$user['user_id']]);

                    $audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Password Reset Requested')");
                    $audit->execute([$user['user_id']]);

                    $success = "Request received! Please contact the System Administrator (admin@dnsc.edu.ph) or visit the REC Office to finalize your password reset.";
                } catch (Exception $e) {
                    $error = "System error: " . $e->getMessage();
                }
            }
        } else {
            $error = "Verification failed. Information does not match our records.";
        }
    }
}

$bodyClass = "login-page";
include 'includes/header.php';
?>

<div class="login-container" style="max-width: 500px;">
    <div class="login-left" style="padding: 40px; width: 100%; flex: 1;">
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
            <h4 class="fw-bold text-navy">Forgot Password?</h4>
            <p class="text-muted small">Enter your details to notify the administrator.</p>
        </div>

        <form action="<?php echo BASE_URL; ?>forgot_password" method="POST">
            <div class="form-group mb-3">
                <i class="far fa-envelope"></i>
                <input type="email" name="email" placeholder="Registered Email Address" required>
            </div>

            <div class="form-group mb-4">
                <i class="far fa-user"></i>
                <input type="text" name="full_name" placeholder="Exact Full Name" required>
            </div>

            <div class="d-grid gap-2">
                <button type="submit" name="request_reset" class="login-btn">
                    <i class="fas fa-paper-plane me-2"></i> Request Reset
                </button>
                <a href="<?php echo BASE_URL; ?>login" class="text-center text-muted small mt-2">Back to Sign In</a>
            </div>
        </form>
    </div>
</div>

<?php if ($error): ?>
    <script>
        Swal.fire({
            icon: 'error',
            title: 'Request Mismatch',
            text: '<?php echo addslashes($error); ?>',
            confirmButtonColor: '#1a2b4b'
        });
    </script>
<?php endif; ?>

<?php if ($success): ?>
    <script>
        Swal.fire({
            icon: 'success',
            title: 'Request Sent',
            text: '<?php echo addslashes($success); ?>',
            confirmButtonColor: '#1a2b4b'
        }).then(() => {
            window.location.href = '<?php echo BASE_URL; ?>login';
        });
    </script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>