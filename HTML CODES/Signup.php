<?php
session_start();
require '../database.php'; // Ensure database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $mobile_number = trim($_POST['mobile_number']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);

    // ✅ **Password Validation (Before Hashing & Inserting into DB)**
    if ($password !== $confirm_password) {
        echo "<script>alert('Passwords do not match!'); window.location.href='../HTML CODES/Signup.php';</script>";
        exit(); // Stop further execution
    }

    // ✅ **Hash the password after validation**
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    try {
        // Check if email already exists
        $stmt = $dbc->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);

        if ($stmt->rowCount() > 0) {
            echo "<script>alert('Email already registered!'); window.location.href='../HTML CODES/Signup.php';</script>";
            exit();
        }

        // Insert new user
        $sql = "INSERT INTO users (firstname, lastname, email, mobile_number, password, verified) 
                VALUES (?, ?, ?, ?, ?, 0)";
        $stmt = $dbc->prepare($sql);
        $stmt->execute([$firstname, $lastname, $email, $mobile_number, $hashed_password]);

        // Auto-login after signup
        $_SESSION['user_id'] = $dbc->lastInsertId();
        $_SESSION['email'] = $email;
        $_SESSION['firstname'] = $firstname;

        // Redirect user based on intent
        if (isset($_SESSION['redirectTo']) && $_SESSION['redirectTo'] == "appointment-service.php") {
            unset($_SESSION['redirectTo']); // Clear session variable
            echo "<script>window.location.href='../HTML CODES/appointment-service.php';</script>";
        } else {
            echo "<script>alert('Signup successful! Welcome, $firstname.'); window.location.href='../HTML CODES/Home_page.html';</script>";
        }
    } catch (PDOException $e) {
        echo "Error: " . $e->getMessage();
    }
}
?>






<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up | PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Signup.css" />
  <script defer src="../JS CODES/register.js"></script>
</head>
<body>
  <!-- Header -->
  <header class="header-bar">
    <img src="../Pictures/pest_logo.png" alt="Logo" class="header-logo" />
    <h1 class="header-text">PESTCOZAM</h1>
  </header>

  <!-- Signup Container -->
  <div class="signup-container">
    <div class="left-column">
      <img src="../Pictures/create_pic.jpg" alt="Pest Control Worker" class="pest-image" />
      <div class="tagline">Your one way <br> ticket to pest <br> eradication</div>
    </div>

    <div class="right-column">
      <h1 class="signup-title">Signup</h1>
      <hr class="title-underline" />

      <form id="registerForm" class="signup-form">
        <div class="row">
          <div class="input-group">
            <label for="firstname">Your First Name</label>
            <input type="text" id="firstname" name="firstname" placeholder="Enter your first name" required />
          </div>
          <div class="input-group">
            <label for="lastname">Your Last Name</label>
            <input type="text" id="lastname" name="lastname" placeholder="Enter your last name" required />
          </div>
        </div>

        <div class="row">
          <div class="input-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email address" required />
          </div>
          <div class="input-group">
            <label for="mobile_number">Mobile Number</label>
            <input type="tel" id="mobile_number" name="mobile_number" placeholder="Enter your mobile number" required />
          </div>
        </div>

        <div class="row">
          <div class="input-group">
            <label for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password" required />
          </div>
          <div class="input-group">
            <label for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password" required />
          </div>
        </div>

        <div class="bottom-row">
          <p class="login-text">
            Already have an account?
            <a href="../HTML CODES/Login.php">Login here</a>
          </p>
          <button type="submit">Sign Up</button>
        </div>
      </form>

    </div>
  </div>
</body>
</html>
