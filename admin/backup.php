<?php
require_once '../includes/auth_check.php';
checkAuth(['admin']);
require_once '../config/database.php';

// Very basic DB backup script for demonstration
$filename = "recras_backup_" . date("Y-m-d_H-i-s") . ".sql";
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// This is a simplified educational backup. In production, use mysqldump.
$tables = ['users', 'protocols', 'protocol_files', 'reviewer_assignments', 'form10_answers', 'form12_answers', 'final_decisions', 'audit_logs'];
foreach ($tables as $table) {
    echo "-- Table: $table\n";
    $stmt = $pdo->query("SELECT * FROM $table");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cols = array_keys($row);
        $vals = array_values($row);
        $vals = array_map(function ($v) use ($pdo) {
            return $pdo->quote($v); }, $vals);
        echo "INSERT INTO $table (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
    }
    echo "\n\n";
}

// Log backup activity
$log = $pdo->prepare("INSERT INTO audit_logs (user_id, action) VALUES (?, 'Generated system database backup')");
$log->execute([$_SESSION['user_id']]);
exit();
