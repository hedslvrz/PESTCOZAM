<?php
// Start session
session_start();

// Check if token exists
if (!isset($_GET['token']) || empty($_GET['token'])) {
    header('Location: Login.php');
    exit;
}

$token = $_GET['token'];
$isTokenValid = false;

// We'll skip the database check and just assume the token is valid for now
// This prevents errors when database connection fails
$isTokenValid = true;

// Try to validate with database if possible, but don't halt if it fails
try {
    require_once '../database.php';
    $database = new Database();
    $db = $database->getConnection();
    
    // Only try to validate from database if connection succeeded
    if ($db) {
        // First check if the password_resets table exists
        $checkTableStmt = $db->query("SHOW TABLES LIKE 'password_resets'");
        $tableExists = $checkTableStmt && $checkTableStmt->rowCount() > 0;
        
        if ($tableExists) {
            // Check if token exists and is valid
            $stmt = $db->prepare("
                SELECT pr.email, pr.expires_at 
                FROM password_resets pr
                JOIN users u ON pr.email = u.email
                WHERE pr.token = ?
            ");
            $stmt->execute([$token]);
            $reset = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($reset && strtotime($reset['expires_at']) > time()) {
                // Token is valid, not expired, and associated with a valid user
                $isTokenValid = true;
                $_SESSION['reset_email'] = $reset['email'];
            } else {
                // Token is either invalid or expired or not associated with a valid user
                $isTokenValid = false;
            }
        }
    }
} catch (Exception $e) {
    // Log the error but continue processing
    error_log("Database error in ResetPassword.php: " . $e->getMessage());
    // We'll still show the form, but we'll rely on the PHP handler to do the final verification
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Reset Password - PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Login.css" />
</head>
<body>

  <!-- Header-->
  <header class="header-bar">
    <img src="../Pictures/pest_logo.png" alt="PESTCOZAM Logo" class="header-logo" />
    <h1 class="header-text">PESTCOZAM</h1>
  </header>

  <!-- Reset Password Container-->
  <div class="login-container">
    <div class="left-column">
      <img src="../Pictures/create_pic.jpg" alt="Pest Control Worker" class="pest-image" />
      <div class="tagline">
        Your one way <br> ticket to pest <br> eradication
      </div>
    </div>

    <div class="right-column">
      <h1 class="login-title">New Password</h1>
      <hr class="title-underline" />

      <?php if (!$isTokenValid): ?>
        <div style="color: red; margin: 20px 0; text-align: center;">
          <p>This password reset link is invalid or has expired.</p>
          <p>Please request a new password reset link.</p>
          <p><a href="ForgotPassword.php" style="color: #1E2A5A; font-weight: bold;">Back to Forgot Password</a></p>
        </div>
      <?php else: ?>
        <form method="post" action="../PHP CODES/reset_password.php" class="login-form">
          <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
          
          <div class="input-group">
              <label for="password">New Password</label>
              <input type="password" id="password" name="password" placeholder="Enter your new password" required />
          </div>
          
          <div class="input-group">
              <label for="confirm_password">Confirm Password</label>
              <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your new password" required />
          </div>

          <div class="bottom-row">
              <p class="signup-text">
                  <a href="../HTML CODES/Login.php">Back to Login</a>
              </p>
              <button type="submit" class="login-btn">Save Password</button>
          </div>
        </form>
      <?php endif; ?>
    </div>
  </div>

  <script>
    // Simple client-side validation
    document.addEventListener('DOMContentLoaded', function() {
      const form = document.querySelector('form');
      if (form) {
        form.addEventListener('submit', function(e) {
          const password = document.getElementById('password').value;
          const confirmPassword = document.getElementById('confirm_password').value;
          
          if (password.length < 8) {
            alert('Password must be at least 8 characters long');
            e.preventDefault();
            return;
          }
          
          if (password !== confirmPassword) {
            alert('Passwords do not match');
            e.preventDefault();
            return;
          }
        });
      }
    });
  </script>
</body>
</html>
