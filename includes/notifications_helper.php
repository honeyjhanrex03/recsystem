<?php
require_once __DIR__ . '/send_email.php';

function notifyUser(PDO $pdo, int|string $user_id, string $user_type, string $title, string $message, ?string $link = null, ?string $email_address = null, ?string $author_name = null): void {
    // 1. Insert into database
    $stmt = $pdo->prepare("INSERT INTO notifications (user_id, user_type, title, message, link) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$user_id, $user_type, $title, $message, $link]);

    // 2. Send Email if email address is provided
    if ($email_address) {
        $emailBody = "
            <div style='font-family: sans-serif; color: #1e293b; max-width: 600px;'>
                <h2 style='color: #0f172a;'>" . htmlspecialchars($title) . "</h2>
                <p>Dear " . htmlspecialchars($author_name ?: 'User') . ",</p>
                <div style='background: #f8fafc; padding: 20px; border-radius: 8px; border-left: 5px solid #c5a059; margin: 20px 0;'>
                    <p style='margin: 0; font-size: 1rem; color: #334155;'>" . nl2br(htmlspecialchars($message)) . "</p>
                </div>
                " . ($link ? "<p style='margin-top: 20px;'><a href='https://dnsc-rec.edu.ph/" . ltrim($link, '/') . "' style='display: inline-block; background: #1a2b4b; color: white; padding: 10px 20px; text-decoration: none; border-radius: 50px; font-weight: bold;'>View Details</a></p>" : "") . "
                <hr style='border: 0; border-top: 1px solid #e2e8f0; margin: 30px 0;'>
                <p style='font-size: 0.8rem; color: #64748b;'>This is an automated notification from the DNSC REC System.</p>
            </div>
        ";
        sendEmailAPI($email_address, $author_name, $title, $emailBody);
    }
}
?>
