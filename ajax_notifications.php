<?php
require_once __DIR__ . '/includes/auth_check.php';
require_once __DIR__ . '/config/database.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'] ?? 'author';
// In recras2.0, authors have string roles like "author" or they might not have a specific role variable if it's the default.
// The DB distinguishes users (authors) and admins (internal staff).
$user_type = in_array($role, ['author']) ? 'author' : 'admin';

$action = $_GET['action'] ?? 'fetch';

if ($action === 'fetch') {
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? AND user_type = ? ORDER BY created_at DESC LIMIT 10");
    $stmt->execute([$user_id, $user_type]);
    $notifications = $stmt->fetchAll();
    
    // DEBUG: Log the parameters
    file_put_contents(__DIR__ . '/scratch/notif_debug.log', "UID: $user_id, TYPE: $user_type, COUNT: " . count($notifications) . "\n", FILE_APPEND);
    
    
    $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND user_type = ? AND is_read = 0");
    $unreadStmt->execute([$user_id, $user_type]);
    $unreadCount = $unreadStmt->fetchColumn();

    echo json_encode([
        'success' => true,
        'notifications' => $notifications,
        'unread_count' => $unreadCount
    ]);
} elseif ($action === 'mark_read') {
    $stmt = $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ? AND user_type = ?");
    $stmt->execute([$user_id, $user_type]);
    echo json_encode(['success' => true]);
}
?>
