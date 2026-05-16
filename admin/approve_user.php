<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$id = $_GET['id'] ?? null;

if ($id) {
    try {
        $stmt = $pdo->prepare("UPDATE admins SET status = 'active' WHERE admin_id = ? AND status = 'pending'");
        $stmt->execute([$id]);

        // Audit log
        $audit = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
        $audit->execute([$_SESSION['user_id'], "Approved personnel asset account ID: $id"]);

        header("Location: users?success=Account authorized successfully");
    } catch (Exception $e) {
        header("Location: users?error=Error approving account: " . $e->getMessage());
    }
} else {
    header("Location: users");
}
exit();
