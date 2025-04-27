<?php
// Include the mailer file
require_once "mailer.php";

// Function to test the email sending
function testResetEmail() {
    // Use your own email for testing
    $testEmail = "your-email@example.com"; // Replace with your email
    
    // Create a test email
    $subject = "PESTCOZAM - Password Reset Test";
    $body = "
    <html>
    <body style='font-family: Arial, sans-serif;'>
        <h2>Test Reset Email</h2>
        <p>This is a test email to verify that the password reset email functionality works.</p>
        <p>If you received this email, your mailer is configured correctly!</p>
        <p>Sent at: " . date('Y-m-d H:i:s') . "</p>
    </body>
    </html>
    ";
    
    // Try to send the email
    $result = sendEmail($testEmail, $subject, $body);
    
    // Return the result
    return $result;
}

// Run the test and display the result
$result = testResetEmail();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Reset Email Test</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .container { max-width: 800px; margin: 0 auto; }
        .success { background: #dff0d8; color: #3c763d; padding: 15px; border-radius: 4px; }
        .error { background: #f2dede; color: #a94442; padding: 15px; border-radius: 4px; }
        pre { background: #f5f5f5; padding: 10px; border-radius: 4px; overflow: auto; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Reset Email Test</h1>
        
        <?php if ($result['success']): ?>
            <div class="success">
                <h3>Success!</h3>
                <p>The test email was sent successfully. Check your inbox.</p>
                <p>Message: <?php echo $result['message']; ?></p>
            </div>
        <?php else: ?>
            <div class="error">
                <h3>Error</h3>
                <p>Failed to send the test email.</p>
                <p>Error message: <?php echo $result['message']; ?></p>
                
                <h4>Troubleshooting:</h4>
                <ul>
                    <li>Make sure your email/password in mailer.php are correct</li>
                    <li>For Gmail, use an App Password if you have 2FA enabled</li>
                    <li>Check that your server allows outgoing SMTP connections</li>
                    <li>Verify PHP has the required extensions (openssl, sockets)</li>
                </ul>
            </div>
        <?php endif; ?>
        
        <h3>Next Steps:</h3>
        <p>If the test was successful, try the password reset feature from the main application.</p>
        <p><a href="../HTML CODES/ForgotPassword.php">Go to Forgot Password Page</a></p>
    </div>
</body>
</html>
