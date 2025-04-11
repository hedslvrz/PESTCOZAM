<?php
// This is a simple script to test if PHPMailer is properly installed

$phpmailerDir = __DIR__ . '/../PHPMailer';

echo "<h1>PHPMailer Installation Test</h1>";

if (is_dir($phpmailerDir)) {
    echo "<p>✓ PHPMailer directory found at: $phpmailerDir</p>";
    
    // Check for src directory
    if (is_dir("$phpmailerDir/src")) {
        echo "<p>✓ src directory found</p>";
        
        // Check for required files
        $requiredFiles = ['Exception.php', 'PHPMailer.php', 'SMTP.php'];
        $missingFiles = [];
        
        foreach ($requiredFiles as $file) {
            if (file_exists("$phpmailerDir/src/$file")) {
                echo "<p>✓ Found $file</p>";
            } else {
                echo "<p>✗ Missing $file</p>";
                $missingFiles[] = $file;
            }
        }
        
        if (empty($missingFiles)) {
            echo "<p style='color:green;font-weight:bold;'>All required files found. Try running the mailer now.</p>";
            echo "<p><a href='test_email.php' style='padding:10px;background:#4CAF50;color:white;text-decoration:none;border-radius:4px;'>Run Email Test</a></p>";
        } else {
            echo "<p style='color:red;font-weight:bold;'>Some files are missing. Please check your PHPMailer installation.</p>";
        }
    } else {
        echo "<p style='color:red;'>✗ src directory not found inside PHPMailer folder</p>";
    }
} else {
    echo "<p style='color:red;'>✗ PHPMailer directory not found at $phpmailerDir</p>";
    echo "<p>Please make sure to extract the PHPMailer library to the correct location.</p>";
}
