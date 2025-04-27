<?php
require_once "mailer.php";
require_once "../EMAIL TEMPLATES/email_functions.php";

// Set a test recipient - change this to your actual email address
$testRecipient = "aldwinsuarez56@gmail.com"; 

// Create email data
$emailData = [
    'title' => 'Email Image Test',
    'resetLink' => '#',
    'expiryTime' => '24 hours'
];

// Get the email template
$subject = "PESTCOZAM - Image Test";
$body = getPasswordResetEmailTemplate($emailData);

// Send the test email
$result = sendEmail($testRecipient, $subject, $body);

// Output result
if ($result['success']) {
    echo "<h1>Test Email Sent Successfully</h1>";
    echo "<p>Check your inbox to see if the image is displaying correctly.</p>";
    
    // Also display the email content on the page for testing
    echo "<h2>Email Content Preview:</h2>";
    echo "<div style='border: 1px solid #ccc; padding: 20px; margin: 20px 0;'>";
    echo $body;
    echo "</div>";
} else {
    echo "<h1>Failed to Send Test Email</h1>";
    echo "<p>Error: " . $result['message'] . "</p>";
}
?>
