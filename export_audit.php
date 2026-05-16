<?php
require_once 'config/database.php';
$stmt = $pdo->query("SHOW CREATE TABLE audit_logs");
$res = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents('audit_create.txt', $res['Create Table']);
?>
