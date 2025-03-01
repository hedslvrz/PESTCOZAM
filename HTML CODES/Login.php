
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Login - PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Login.css" />
  <script src="../JS CODES/login.js"></script>
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

      <form id="loginUser" name="loginUser" class="login-form">
    <div class="input-group">
        <label for="email">Your Email</label>
        <input type="email" id="email" name="email" placeholder="Enter your email address" />
    </div>

    <div class="input-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" placeholder="Enter your password" />
    </div>

    <div class="bottom-row">
        <p class="signup-text">
            Don't have an account?
            <a href="../HTML CODES/Signup.php">Sign up here</a>
        </p>
        <button type="submit" value="login" class="login-btn">Login</button>
    </div>
</form>

    </div>
  </div>
</body>
</html>
