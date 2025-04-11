<?php
// Include the mailer file
require_once "mailer.php";

// Set a test recipient - using your own email for testing
$testRecipient = "aldwinsuarez56@gmail.com"; // Replace with your actual email address

// Test email content
$subject = "PESTCOZAM - Email Test";
$body = "
<html>
<head>
    <style>
        body { font-family: Arial, sans-serif; }
        .container { padding: 20px; }
    </style>
</head>
<body>
    <div class='container'>
        <h2>PHPMailer Test</h2>
        <p>This is a test email to verify that the PHPMailer configuration is working correctly.</p>
        <p>If you received this email, it means your setup is successful!</p>
        <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
    </div>
</body>
</html>
";

// Send the test email
$result = sendEmail($testRecipient, $subject, $body);

// Output the result
if ($result['success']) {
    echo "<h1>Email Test Successful</h1>";
    echo "<p>The test email was sent successfully to {$testRecipient}.</p>";
} else {
    echo "<h1>Email Test Failed</h1>";
    echo "<p>Error: {$result['message']}</p>";
    
    echo "<h2>Troubleshooting Tips:</h2>";
    echo "<ul>";
    echo "<li>Verify that the PHP Mailer files are in the correct location</li>";
    echo "<li>Make sure you're using an App Password from Google (not your regular password)</li>";
    echo "<li>Check that your Google account has allowed less secure apps</li>";
    echo "<li>Verify that your server allows outgoing SMTP connections</li>";
    echo "</ul>";
}
?>
