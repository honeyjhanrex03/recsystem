<?php
// Include PHPMailer autoload (assuming standard Composer installation)
require_once dirname(__DIR__) . '/vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Send an email using SMTP (PHPMailer)
 * 
 * @param string $toEmail The recipient's email address
 * @param string $toName  The recipient's name
 * @param string $subject The email subject
 * @param string $htmlContent The HTML content of the email
 * @return bool True if successful, false otherwise
 */
function sendEmailAPI($toEmail, $toName, $subject, $htmlContent) {
    
    // Who the email appears to be FROM (Change this to your verified sender email)
    $senderEmail = "delapena.jhanrexphilip@dnsc.edu.ph"; 
    $senderName  = "DNSC REC";

    // Where users should REPLY to (Your actual working email)
    $replyToEmail = "delapena.jhanrexphilip@dnsc.edu.ph";

    $mail = new PHPMailer(true);

    try {
        //Server settings
        $mail->isSMTP();                                            // Send using SMTP
        $mail->Host       = 'smtp-relay.brevo.com';                 // Set the SMTP server to send through
        $mail->SMTPAuth   = true;                                   // Enable SMTP authentication
        
        // Use the SMTP Login and Key from your Brevo Dashboard
        if (file_exists(dirname(__DIR__) . '/config/secrets.php')) {
            require_once dirname(__DIR__) . '/config/secrets.php';
        }
        
        $mail->Username   = 'a34cd5001@smtp-brevo.com';             
        $mail->Password   = defined('BREVO_SMTP_KEY') ? BREVO_SMTP_KEY : 'YOUR_BREVO_SMTP_KEY_HERE'; 


        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;         // Enable TLS encryption
        $mail->Port       = 587;                                    // TCP port to connect to

        //Recipients
        $mail->setFrom($senderEmail, $senderName);
        $mail->addAddress($toEmail, $toName);                       // Add a recipient
        $mail->addReplyTo($replyToEmail, 'DNSC REC Support Team');

        // Content
        $mail->isHTML(true);                                        // Set email format to HTML
        $mail->Subject = $subject;
        $mail->Body    = $htmlContent;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        echo "Mailer Error: {$mail->ErrorInfo}";
        return false;
    }
}
?>
