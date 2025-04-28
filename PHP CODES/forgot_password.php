<?php
// Start with a clean output buffer
ob_start();

// Include mailer
require_once 'mailer.php';
require_once '../database.php';

// Get the email from POST data
$email = isset($_POST['email']) ? trim($_POST['email']) : '';

// Basic validation
if (empty($email)) {
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'Email Required',
            text: 'Please enter your email address',
            confirmButtonColor: '#144578'
        }).then(function() {
            window.location.href='../HTML CODES/ForgotPassword.php';
        });
    </script>";
    exit;
}

try {
    // First, create the database instance
    $database = new Database();
    $db = $database->getConnection();
    
    // If the primary connection failed, try a simplified approach with explicit database selection
    if (!$db) {
        error_log("Primary database connection failed, trying direct approach...");
        try {
            // Try connecting directly with PDO
            $host = 'localhost';
            $db_name = 'u302876046_pestcozam';
            $username = 'root'; // Use your local MySQL username
            $password = '';     // Use your local MySQL password
            
            $db = new PDO("mysql:host=$host", $username, $password);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            
            // Explicitly select the database
            $db->exec("USE `$db_name`");
            error_log("Connected with direct approach and selected database: $db_name");
        } catch (PDOException $e) {
            error_log("Direct database connection failed: " . $e->getMessage());
            throw new Exception("Cannot connect to database server");
        }
    }
    
    // After connecting, explicitly select the database to ensure it's active
    try {
        $db->exec("USE `u302876046_pestcozam`");
    } catch (Exception $e) {
        error_log("Failed to select database: " . $e->getMessage());
        // Continue anyway, as it might already be selected
    }
    
    $emailExists = false;
    
    // Check if the user exists in the database
    try {
        $stmt = $db->prepare("SELECT id, email FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $emailExists = true;
            error_log("Found user with email: $email, proceeding with password reset");
        } else {
            error_log("No user found with email: $email, password reset aborted");
            // Instead of hiding that the email doesn't exist, now we'll tell the user
            echo "<script>
                Swal.fire({
                    icon: 'warning',
                    title: 'Email Not Found',
                    text: 'We couldn't find an account with that email. Please check your email and try again.',
                    confirmButtonColor: '#144578'
                }).then(function() {
                    window.location.href='../HTML CODES/ForgotPassword.php';
                });
            </script>";
            exit;
        }
    } catch (Exception $e) {
        error_log("Error checking user existence: " . $e->getMessage());
        throw new Exception("Database query failed");
    }
    
    // Only continue if the email exists in our database
    if ($emailExists) {
        // Generate a token
        $token = bin2hex(random_bytes(16));
        
        // Log the token - for development only
        $logMessage = date('Y-m-d H:i:s') . " - Token created for $email: $token\n";
        file_put_contents(__DIR__ . '/reset_tokens.log', $logMessage, FILE_APPEND);
        
        try {
            // First check if the table exists
            $checkTableSql = "SHOW TABLES LIKE 'password_resets'";
            $tableExists = $db->query($checkTableSql)->rowCount() > 0;
            
            if (!$tableExists) {
                // Create the table if it doesn't exist
                $db->exec("CREATE TABLE IF NOT EXISTS `password_resets` (
                    `id` INT AUTO_INCREMENT PRIMARY KEY,
                    `email` VARCHAR(255) NOT NULL,
                    `token` VARCHAR(255) NOT NULL,
                    `expires_at` DATETIME NOT NULL,
                    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    INDEX (`email`),
                    INDEX (`token`)
                )");
                error_log("Created password_resets table");
            }
            
            // Delete any existing tokens for this user
            $stmt = $db->prepare("DELETE FROM `password_resets` WHERE email = ?");
            $stmt->execute([$email]);
            
            // Store the token in the database
            $expires = date('Y-m-d H:i:s', time() + 3600); // 1 hour from now
            $stmt = $db->prepare("INSERT INTO `password_resets` (email, token, expires_at) VALUES (?, ?, ?)");
            $stmt->execute([$email, $token, $expires]);
            
            error_log("Reset token stored in database for email: $email");
        } catch (Exception $e) {
            // If we can't create/access the password_resets table, log it but proceed with sending email
            error_log("Error with password_resets table: " . $e->getMessage());
            // Continue anyway to send the email
        }
        
        // Create the reset link
        $resetLink = "http://" . $_SERVER['HTTP_HOST'] . "/PESTCOZAM/HTML CODES/ResetPassword.php?token=" . $token;
        
        // Use the email template system for password reset email
        require_once '../EMAIL TEMPLATES/email_functions.php';
        
        $emailData = [
            'title' => 'Password Reset Request',
            'resetLink' => $resetLink,
            'expiryTime' => '1 hour'
        ];
        
        $subject = "PESTCOZAM - Password Reset";
        $body = getPasswordResetEmailTemplate($emailData);
        
        // Send the email
        $result = sendEmail($email, $subject, $body);
        
        if ($result['success']) {
            // Redirect with success message
            echo "<script>
                Swal.fire({
                    icon: 'success',
                    title: 'Email Sent',
                    text: 'A password reset link has been sent to your email address.',
                    confirmButtonColor: '#144578'
                }).then(function() {
                    window.location.href='../HTML CODES/Login.php';
                });
            </script>";
        } else {
            error_log("Failed to send email: " . $result['message']);
            echo "<script>
                Swal.fire({
                    icon: 'error',
                    title: 'Email Delivery Failed',
                    text: 'There was a problem sending the email. Please try again later.',
                    confirmButtonColor: '#144578'
                }).then(function() {
                    window.location.href='../HTML CODES/ForgotPassword.php';
                });
            </script>";
        }
    }
} catch (Exception $e) {
    error_log("Password reset error: " . $e->getMessage());
    echo "<script>
        Swal.fire({
            icon: 'error',
            title: 'System Error',
            text: 'An unexpected error occurred. Please try again later.',
            confirmButtonColor: '#144578'
        }).then(function() {
            window.location.href='../HTML CODES/ForgotPassword.php';
        });
    </script>";
}

ob_end_flush();
?>
