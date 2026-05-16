<?php
require_once __DIR__ . '/config/database.php';

try {
    $sql = "CREATE TABLE IF NOT EXISTS notifications (
        notification_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        user_type ENUM('author', 'admin') NOT NULL,
        title VARCHAR(255) NOT NULL,
        message TEXT NOT NULL,
        link VARCHAR(255) DEFAULT NULL,
        is_read BOOLEAN DEFAULT FALSE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $pdo->exec($sql);
    echo "Notifications table created successfully.\n";
} catch (Exception $e) {
    echo "Error creating table: " . $e->getMessage() . "\n";
}
?>
