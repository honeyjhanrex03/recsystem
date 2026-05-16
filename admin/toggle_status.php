<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$user_id = $_GET['id'] ?? null;
if (!$user_id)
    die("Invalid User ID");

// Fetch current status
$stmt = $pdo->prepare("SELECT status FROM admins WHERE admin_id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

if ($user) {
    $new_status = ($user['status'] == 'active') ? 'inactive' : 'active';
    $update = $pdo->prepare("UPDATE admins SET status = ? WHERE admin_id = ?");
    $update->execute([$new_status, $user_id]);

    // Log it
    $log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)");
    $log->execute([$_SESSION['user_id'], "Toggled status for user ID $user_id to $new_status"]);
}

header("Location: users");
exit();
