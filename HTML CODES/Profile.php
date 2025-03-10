<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Database connection
$conn = new mysqli("localhost", "root", "", "pestcozam");

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Get user data
$user_id = $_SESSION['user_id'];
$sql = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Get user appointments
$sql_appointments = "SELECT a.*, s.service_name 
            FROM appointments a 
            JOIN services s ON a.service_id = s.service_id 
            WHERE a.user_id = ? 
            ORDER BY a.appointment_date DESC 
            LIMIT 3";
$stmt = $conn->prepare($sql_appointments);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get user's most recent appointment
$sql_recent_appointment = "SELECT a.*, s.service_name, 
          CONCAT(t.firstname, ' ', t.lastname) as technician_name
          FROM appointments a 
          JOIN services s ON a.service_id = s.service_id 
          LEFT JOIN users t ON a.technician_id = t.id
          WHERE a.user_id = ? 
          ORDER BY a.appointment_date DESC, a.appointment_time DESC 
          LIMIT 1";
$stmt = $conn->prepare($sql_recent_appointment);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$recent_appointment = $stmt->get_result()->fetch_assoc();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>User Profile</title>
    <link rel="stylesheet" href="../CSS CODES/Profile.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
</head>
<style>
    html {
    scroll-behavior: smooth;
  }
  </style>
<body>

  <!-- HEADER -->
  <div class="header-wrapper">
    <header class="top-header">
      <div class="container">
        <div class="location">
          <i class='bx bx-map'></i>
          <span> <strong>Estrada St, Zamboanga City, Zamboanga Del Sur, 7000<strong></span>
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

    <!-- NAVBAR -->
    <header class="navbar">
      <div class="logo-container">
        <img src="../Pictures/pest_logo.png" alt="Flower Logo" class="flower-logo">
        <span class="brand-name">PESTCOZAM</span>
      </div>
      <nav>
        <ul>
          <li><a href="Home_page.php">Home</a></li>
          <li><a href="Home_page.php#offer-section">Services</a></li>
          <li><a href="Home_page.php#about-us-section">About Us</a></li>
          <li><a href="Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="user-profile">
              <div class="profile-dropdown">
                <img src="../Pictures/boy.png" alt="Profile" class="profile-pic">
                <div class="dropdown-content">
                  <a href="Profile.php"><i class='bx bx-user'></i> Profile</a>
                  <a href="logout.php"><i class='bx bx-log-out'></i> Logout</a>
                </div>
              </div>
            </li>
          <?php endif; ?>
        </ul>      
      </nav>
    </header>
  </div>

  <!-- MAIN CONTAINER -->
  <div class="main-container">
    <!-- LEFT COLUMN -->
    <div class="left-column">
      
      <!-- PROFILE & PERSONAL DETAILS -->
      <div class="profile-section">
        <div class="profile-card">
          <img src="../Pictures/boy.png" alt="User Avatar" class="avatar" />
          <div class="user-info">
            <h3><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
            <p><?php echo htmlspecialchars($user['email']); ?></p>
          </div>
          <button class="edit-btn">
            <i class="bx bx-edit"></i> Edit
          </button>
        </div>

        <!-- Personal Details -->
        <div class="personal-details">
          <h3>Personal Details</h3>
          <div class="details-grid">
            <p><strong>First Name:</strong> <?php echo htmlspecialchars($user['firstname']); ?></p>
            <p><strong>Last Name:</strong> <?php echo htmlspecialchars($user['lastname']); ?></p>
            <p><strong>Date of Birth:</strong> <?php echo $user['dob'] ? date('m-d-Y', strtotime($user['dob'])) : 'Not set'; ?></p>
            <p><strong>Phone Number:</strong> <?php echo htmlspecialchars($user['mobile_number']); ?></p>
            <p><strong>Email:</strong> <?php echo htmlspecialchars($user['email']); ?></p>
            <p><strong>Role:</strong> <?php echo ucfirst(htmlspecialchars($user['role'])); ?></p>
            <p><strong>Status:</strong> <?php echo ucfirst(htmlspecialchars($user['status'])); ?></p>
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
              <th>Date</th>
              <th>Status</th>
            </tr>
          </thead>
          <tbody>
            <?php while($appointment = $appointments->fetch_assoc()): ?>
            <tr class="history-row" data-appointment-id="<?php echo $appointment['id']; ?>">
              <td><?php echo $appointment['id']; ?></td>
              <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
              <td><?php echo date('m/d/y', strtotime($appointment['appointment_date'])); ?></td>
              <td><span class="history-badge <?php echo strtolower($appointment['status']); ?>"><?php echo $appointment['status']; ?></span></td>
            </tr>
            <?php endwhile; ?>
          </tbody>
        </table>
      </div>
    </div>

    <!-- RIGHT COLUMN -->
    <div class="right-column">
      <!-- APPOINTMENT CARD -->
      <div class="appointment-card">
        <h3>Latest Appointment</h3>
        <div class="appointment-details">
          <?php if ($recent_appointment): ?>
            <p><strong>Appointment #:</strong> <?php echo $recent_appointment['id']; ?></p>
            <p><strong>Type of Service:</strong> <?php echo htmlspecialchars($recent_appointment['service_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($recent_appointment['appointment_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($recent_appointment['appointment_time'])); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($recent_appointment['street_address']); ?></p>
            <p><strong>Technician:</strong> <?php echo $recent_appointment['technician_name'] ? htmlspecialchars($recent_appointment['technician_name']) : 'Not yet assigned'; ?></p>
            <p>
              <strong>Status:</strong> 
              <span class="appointment-status <?php echo strtolower($recent_appointment['status']); ?>"><?php echo $recent_appointment['status']; ?></span>
            </p>
          <?php else: ?>
            <p>No appointments found.</p>
          <?php endif; ?>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Profile Modal -->
  <div id="editProfileModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Edit Profile</h2>
      <form id="editProfileForm" method="POST" action="update_profile.php">
        <div class="form-group">
          <label for="firstname">First Name</label>
          <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
        </div>
        <div class="form-group">
          <label for="lastname">Last Name</label>
          <input type="text" id="lastname" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" required>
        </div>
        <div class="form-group">
          <label for="email">Email</label>
          <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required>
        </div>
        <div class="form-group">
          <label for="mobile_number">Mobile Number</label>
          <input type="tel" id="mobile_number" name="mobile_number" value="<?php echo htmlspecialchars($user['mobile_number']); ?>" required>
        </div>
        <div class="form-group">
          <label for="dob">Date of Birth</label>
          <input type="date" id="dob" name="dob" value="<?php echo $user['dob']; ?>">
        </div>
        <div class="form-buttons">
          <button type="submit" class="save-btn">Save Changes</button>
          <button type="button" class="cancel-btn" onclick="closeModal()">Cancel</button>
        </div>
      </form>
    </div>
  </div>

  <!-- Appointment Details Modal -->
  <div id="appointmentDetailsModal" class="modal">
    <div class="modal-content">
      <span class="close">&times;</span>
      <h2>Appointment Details</h2>
      <div id="appointmentDetails"></div>
    </div>
  </div>

  <script>
    let lastScrollTop = 0;
    const headerWrapper = document.querySelector('.header-wrapper');
    const navbarHeight = headerWrapper.offsetHeight;
    
    window.addEventListener('scroll', function() {
        const scrollTop = window.pageYOffset || document.documentElement.scrollTop;
        
        if (scrollTop > lastScrollTop && scrollTop > navbarHeight) {
            // Scrolling down & past navbar
            headerWrapper.classList.add('hide-nav-group');
        } else {
            // Scrolling up or at top
            headerWrapper.classList.remove('hide-nav-group');
        }
        
        lastScrollTop = scrollTop <= 0 ? 0 : scrollTop; // For Mobile or negative scrolling
    }, false);
  </script>

  <script>
    const modal = document.getElementById('editProfileModal');
    const editBtn = document.querySelector('.edit-btn');
    const closeBtn = document.querySelector('.close');
    const form = document.getElementById('editProfileForm');

    editBtn.onclick = function() {
      modal.style.display = "block";
    }

    closeBtn.onclick = function() {
      closeModal();
    }

    function closeModal() {
      modal.style.display = "none";
    }

    window.onclick = function(event) {
      if (event.target == modal) {
        closeModal();
      }
    }

    form.onsubmit = function(e) {
      e.preventDefault();
      const formData = new FormData(form);
      
      fetch('update_profile.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Profile updated successfully!');
          location.reload();
        } else {
          alert('Error updating profile: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error updating profile');
        console.error(error);
      });
    }

    document.querySelectorAll('.history-row').forEach(row => {
      row.addEventListener('click', function() {
        const appointmentId = this.dataset.appointmentId;
        fetch(`get_appointment_details.php?id=${appointmentId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const details = data.details;
              const detailsHtml = `
                <p><strong>Appointment #:</strong> ${details.id}</p>
                <p><strong>Type of Service:</strong> ${details.service_name}</p>
                <p><strong>Date:</strong> ${details.appointment_date}</p>
                <p><strong>Time:</strong> ${details.appointment_time}</p>
                <p><strong>Location:</strong> ${details.street_address}</p>
                <p><strong>Technician:</strong> ${details.technician_name || 'Not yet assigned'}</p>
                <p><strong>Status:</strong> ${details.status}</p>
              `;
              document.getElementById('appointmentDetails').innerHTML = detailsHtml;
              document.getElementById('appointmentDetailsModal').style.display = 'block';
            } else {
              alert('Error fetching appointment details');
            }
          })
          .catch(error => {
            alert('Error fetching appointment details');
            console.error(error);
          });
      });
    });

    const appointmentModal = document.getElementById('appointmentDetailsModal');
    const closeAppointmentModal = document.querySelector('#appointmentDetailsModal .close');

    closeAppointmentModal.onclick = function() {
      appointmentModal.style.display = 'none';
    }

    window.onclick = function(event) {
      if (event.target == appointmentModal) {
        appointmentModal.style.display = 'none';
      }
    }
  </script>
</body>
</html>
