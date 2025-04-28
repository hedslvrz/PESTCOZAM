<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Forgot Password - PESTCOZAM</title>
  <link rel="stylesheet" href="../CSS CODES/Login.css" />
  <!-- Add SweetAlert2 CSS and JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
</head>
<body>

  <!-- Header-->
  <header class="header-bar">
    <img src="../Pictures/pest_logo.png" alt="PESTCOZAM Logo" class="header-logo" />
    <h1 class="header-text">PESTCOZAM</h1>
  </header>

  <!-- Forgot Password Container-->
  <div class="login-container">
    <div class="left-column">
      <img src="../Pictures/create_pic.jpg" alt="Pest Control Worker" class="pest-image" />
      <div class="tagline">
        Your one way <br> ticket to pest <br> eradication
      </div>
    </div>

    <div class="right-column">
      <h1 class="login-title">Reset Password</h1>
      <hr class="title-underline" />

      <!-- Direct form submission -->
      <form method="post" action="../PHP CODES/forgot_password.php" class="login-form">
        <div class="input-group">
            <label for="email">Your Email</label>
            <input type="email" id="email" name="email" placeholder="Enter your email address" required />
        </div>

        <div class="bottom-row">
            <p class="signup-text">
                <a href="../HTML CODES/Login.php">Back to Login</a>
            </p>
            <button type="submit" class="login-btn">Reset Password</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Basic validation script -->
  <script>
    document.querySelector('form').addEventListener('submit', function(event) {
      const email = document.getElementById('email').value.trim();
      if (!email) {
        event.preventDefault();
        Swal.fire({
          icon: 'error',
          title: 'Oops...',
          text: 'Please enter your email address',
          confirmButtonColor: '#3085d6',
          confirmButtonText: 'OK'
        });
      }
    });
  </script>
</body>
</html>
