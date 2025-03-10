<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>User Profile</title>
  <link rel="stylesheet" href="../CSS CODES/Profile.css" />
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
</head>
<body>

  <!-- HEADER -->
  <header class="top-header">
    <div class="container">
      <div class="location">
        <span>
          • <strong>Zamboanga</strong> 
          • <strong>Pagadian</strong> 
          • <strong>Pasay</strong> 
          • <strong>Davao</strong>
        </span>
      </div>
      <div class="contact-info">
        <img src="../Pictures/phone.png" alt="Phone Icon" class="icon">
        <span>0905 - 177 - 5662</span>
        <span class="divider"></span>
        <img src="../Pictures/email.png" alt="Email Icon" class="icon">
        <span>pestcozam@yahoo.com</span>
      </div>
    </div>
  </header>

  <!-- BACK BUTTON -->
  <a href="../HTML CODES/Home_page.php" class="back-btn">
    <i class="fas fa-arrow-left"></i>
    <span>Back to Home</span>
  </a>

  <!-- MAIN CONTAINER -->
  <div class="main-container">
    <!-- LEFT COLUMN -->
    <div class="left-column">
      
      <!-- PROFILE & PERSONAL DETAILS (merged into one card) -->
      <div class="profile-section">
        <!-- Top row: Avatar, Name, Edit Button -->
        <div class="profile-card">
          <img src="../Pictures/boy.png" alt="User Avatar" class="avatar" />
          <div class="user-info">
            <h3>Daniel Patilla</h3>
            <p>Tetuan, Zamboanga City</p>
          </div>
          <button class="edit-btn">
            <i class="fas fa-edit"></i> Edit
          </button>
        </div>

        <!-- Personal Details below the profile row -->
        <div class="personal-details">
          <h3>Personal Details</h3>
          <div class="details-grid">
            <p><strong>First Name:</strong> Daniel</p>
            <p><strong>Last Name:</strong> Patilla</p>
            <p><strong>Date of Birth:</strong> 03-05-2003</p>
            <p><strong>Phone Number:</strong> 0953-654-4541</p>
            <p><strong>Email:</strong> Daniel.Patilla@gmail.com</p>
            <p><strong>Country:</strong> Philippines</p>
            <p><strong>City:</strong> Zamboanga City</p>
            <p><strong>City Address:</strong> Tetuan, Hotdog Drive</p>
          </div>
        </div>
      </div>

      <!-- HISTORY SECTION -->
      <div class="history-section">
        <h3>History</h3>
        <table>
          <thead>
            <tr>
              <th>Appointment #</th>
              <th>Type of Service</th>
              <th>Technician</th>
              <th>Status</th>
              <th>Date</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>415546654</td>
              <td>Thermite</td>
              <td>M. Pogi</td>
              <td><span class="status-badge pending">Pending</span></td>
              <td>03/05/25</td>
            </tr>
            <tr>
              <td>542321312</td>
              <td>General</td>
              <td>M. Buten</td>
              <td><span class="status-badge paid">Paid</span></td>
              <td>03/15/24</td>
            </tr>
            <tr>
              <td>835646655</td>
              <td>Mound</td>
              <td>M. Yoso</td>
              <td><span class="status-badge paid">Paid</span></td>
              <td>03/08/23</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-column">
      <!-- APPOINTMENT CARD -->
      <div class="appointment-card">
        <h3>Appointment</h3>
        <div class="appointment-details">
          <p><strong>Appointment #:</strong> 154564654</p>
          <p><strong>Type of Service:</strong> Mound Demolition</p>
          <p><strong>Date:</strong> March 05, 2025</p>
          <p><strong>Technician:</strong> James Reid, Daniel Patilla</p>
          <p>
            <strong>Status:</strong> 
            <span class="status-badge pending">Pending</span>
          </p>
        </div>
      </div>

      <!-- QUOTATION CARD -->
      <div class="quotation-card">
        <h3>Quotation</h3>
        <div class="quotation-details">
          <p><strong>Appointment #:</strong> 154564654</p>
          <p><strong>Date:</strong> March 05, 2025</p>
          <p><strong>Total Square Meter:</strong> 390Sqm</p>
        </div>
      </div>
    </div>
  </div>
  
</body>
</html>
