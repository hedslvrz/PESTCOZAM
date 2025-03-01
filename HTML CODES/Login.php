<?php
session_start();
require '../database.php'; // Include database connection

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    try {
        $stmt = $dbc->prepare("SELECT id, password FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['id']; // Store user session
          
            // Check if the user was redirected from appointment page
            if (isset($_SESSION['redirectTo']) && $_SESSION['redirectTo'] == "appointment-service.php") {
                unset($_SESSION['redirectTo']); // Clear session variable
                echo "<script>window.location.href='../HTML CODES/appointment-service.php';</script>";
            } else {
                echo "<script>window.location.href='../HTML CODES/Home_page.html';</script>";
            }
        } else {
            echo "<script>alert('Invalid email or password!'); window.location.href='../HTML CODES/Login.php';</script>";
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
  <title>Login - PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Login.css" />
</head>
<body>

  <!-- Header-->
  <header class="header-bar">
    <img src="../Pictures/pest_logo.png" alt="PESTCOZAM Logo" class="header-logo" />
    <h1 class="header-text">PESTCOZAM</h1>
  </header>

  <!-- Login Container-->
  <div class="login-container">
    <div class="left-column">
      <img src="../Pictures/create_pic.jpg" alt="Pest Control Worker" class="pest-image" />
      <div class="tagline">
        Your one way <br> ticket to pest <br> eradication
      </div>
    </div>

    <div class="right-column">
      <h1 class="login-title">Login</h1>
      <hr class="title-underline" />

      <form action="../PHP CODES/login.php" method="post" class="login-form">
    <div class="input-group">
        <label for="email">Your Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email address" required />
    </div>

    <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" required />
    </div>

    <div class="bottom-row">
        <p class="signup-text">
            Don't have an account?
            <a href="../HTML CODES/Signup.php">Sign up here</a>
        </p>
        <button type="submit" class="login-btn">Login</button>
    </div>
</form>

    </div>
  </div>

  <script>
document.addEventListener("DOMContentLoaded", function () {
    document.querySelectorAll(".appointment-btn").forEach(button => {
        button.addEventListener("click", function (event) {
            event.preventDefault();

            fetch("../PHP CODES/check_session.php") // Check if user is logged in
                .then(response => response.json())
                .then(data => {
                    if (data.loggedIn) {
                        window.location.href = "appointment-service.php";
                    } else {
                        sessionStorage.setItem("redirectTo", "appointment-service.php"); // Store redirect intent
                        alert("You must log in first to make an appointment.");
                        window.location.href = "login.php";
                    }
                });
        });
    });
});
</script>

</body>
</html>
