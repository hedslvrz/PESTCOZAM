<?php
// Simple approach - direct file inclusion
// First, check if our Autoload.php exists and use it
if (file_exists(__DIR__ . '/../PHPMailer/vendor/autoload.php')) {
    require_once __DIR__ . '/../PHPMailer/vendor/autoload.php';
} 
// Otherwise, try manual class loading
else {
    if (file_exists(__DIR__ . '/../PHPMailer/src/Exception.php')) {
        require_once __DIR__ . '/../PHPMailer/src/Exception.php';
        require_once __DIR__ . '/../PHPMailer/src/PHPMailer.php';
        require_once __DIR__ . '/../PHPMailer/src/SMTP.php';
    } else {
        die('PHPMailer files not found. Please check the installation instructions.');
    }
}

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

/**
 * Function to send email
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param array $attachments Optional array of attachments
 * @return array ['success' => bool, 'message' => string]
 */
function sendEmail($to, $subject, $body, $attachments = []) {
    // Create new PHPMailer instance
    $mail = new PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'aldwinsuarez56@gmail.com';
        $mail->Password = 'wnobpnaneikdqzvz'; // Use App Password, not your Gmail password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = 587;
        
        // Recipients
        $mail->setFrom('aldwinsuarez56@gmail.com', 'PESTCOZAM');
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        // Add attachments if any
        if (!empty($attachments)) {
            foreach ($attachments as $attachment) {
                $mail->addAttachment($attachment);
            }
        }
        
        $mail->send();
        return ['success' => true, 'message' => 'Email sent successfully.'];
    } catch (Exception $e) {
        error_log("Email error: {$mail->ErrorInfo}");
        return ['success' => false, 'message' => "Email could not be sent. Mailer Error: {$mail->ErrorInfo}"];
    }
}
