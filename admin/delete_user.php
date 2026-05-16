<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;

// Safety: prevent admin from deleting their own account
if ($id === (int) $_SESSION['user_id']) {
    header("Location: users?error=cannot_delete_self");
    exit();
}

if ($id > 0) {
    try {
        // Fetch the user name for the confirmation message
        $stmt = $pdo->prepare("SELECT name FROM admins WHERE admin_id = ?");
        $stmt->execute([$id]);
        $user = $stmt->fetch();

        if ($user) {
            // Log the action before deleting
            $audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
            $audit->execute([$_SESSION['user_id'], "Deleted account: " . $user['name']]);

            // Delete the user
            $del = $pdo->prepare("DELETE FROM admins WHERE admin_id = ?");
            $del->execute([$id]);

            $deleted_name = htmlspecialchars($user['name']);
        } else {
            header("Location: users?error=not_found");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: users?error=db_error");
        exit();
    }
} else {
    header("Location: users");
    exit();
}

include '../includes/header.php';
?>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        Swal.fire({
            icon: 'success',
            title: 'Account Removed',
            html: `The account for <strong><?php echo $deleted_name; ?></strong> has been permanently deleted from the system.`,
            confirmButtonColor: '#1a2b4b',
            confirmButtonText: 'Back to User Hub',
            customClass: { confirmButton: 'rounded-pill px-4' }
        }).then(() => {
            window.location.href = '<?php echo BASE_URL; ?>admin/users';
        });
    });
</script>

<?php include '../includes/footer.php'; ?>
