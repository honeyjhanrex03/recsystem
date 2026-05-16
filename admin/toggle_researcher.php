<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

$id = intval($_GET['id'] ?? 0);
$action = $_GET['action'] ?? '';

if (!$id || !$action) {
    header('Location: researchers');
    exit();
}

// Verify it's a legitimate users (researcher) record
$stmt = $pdo->prepare("SELECT * FROM users WHERE user_id = ?");
$stmt->execute([$id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: researchers?error=not_found');
    exit();
}

if ($action === 'approve') {
    $pdo->prepare("UPDATE users SET status = 'active' WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)")->execute([
        $_SESSION['user_id'],
        "Approved researcher account: {$user['first_name']} {$user['last_name']} (ID #$id)"
    ]);
    header('Location: researchers?success=approved');
    exit();
}

if ($action === 'toggle') {
    $newStatus = ($user['status'] === 'active') ? 'suspended' : 'active';
    $pdo->prepare("UPDATE users SET status = ? WHERE user_id = ?")->execute([$newStatus, $id]);
    $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)")->execute([
        $_SESSION['user_id'],
        "Researcher account {$newStatus}: {$user['first_name']} {$user['last_name']} (ID #$id)"
    ]);
    header('Location: researchers?success=toggled');
    exit();
}

if ($action === 'delete') {
    // Safety: remove protocols and related data first (cascades should handle it, but being explicit)
    $pdo->prepare("DELETE FROM protocols WHERE created_by = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM users WHERE user_id = ?")->execute([$id]);
    $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, ?)")->execute([
        $_SESSION['user_id'],
        "Deleted researcher account: {$user['first_name']} {$user['last_name']} (ID #$id)"
    ]);
    header('Location: researchers?success=deleted');
    exit();
}

header('Location: researchers');
exit();
