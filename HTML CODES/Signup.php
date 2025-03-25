<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Sign Up | PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Signup.css" />
  <link rel="stylesheet" href="../CSS CODES/modal.css" />
  <script src="../JS CODES/modal.js"></script>
  <script src="../JS CODES/register.js"></script>
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

      <form id="registerForm" name="registerForm" class="signup-form">
        <div class="row">
          <div class="input-group">
            <label class="details" for="firstname">Your First Name</label>
            <input type="text" id="firstname" name="firstname" placeholder="Enter your first name" />
          </div>
          <div class="input-group">
            <label class="details" for="middlename">Your Middle Name (Optional)</label>
            <input type="text" id="middlename" name="middlename" placeholder="Enter your middle name" />
          </div>
          <div class="input-group">
            <label class="details" for="lastname">Your Last Name</label>
            <input type="text" id="lastname" name="lastname" placeholder="Enter your last name" />
          </div>
        </div>

        <div class="row">
          <div class="input-group">
            <label class="details" for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email address" />
          </div>
          <div class="input-group">
            <label class="details" for="mobile_number">Mobile Number</label>
            <input type="text" id="mobile_number" name="mobile_number" maxlength="11" placeholder="Enter your mobile number" />
          </div>
        </div>

        <div class="row">
          <div class="input-group">
            <label class="details" for="password">Password</label>
            <input type="password" id="password" name="password" placeholder="Enter your password"/>
          </div>
          <div class="input-group">
            <label class="details" for="confirm_password">Confirm Password</label>
            <input type="password" id="confirm_password" name="confirm_password" placeholder="Confirm your password"/>
          </div>
        </div>

        <div class="bottom-row">
          <p class="login-text">
            Already have an account?
            <a href="../HTML CODES/Login.php">Login here</a>
          </p>
          <button type="submit" value="Register">Sign Up</button>
        </div>
      </form>

    </div>
  </div>
</body>
</html>
