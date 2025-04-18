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
$sql_appointments = "SELECT a.*, s.service_name,
            CASE 
                WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                ELSE CONCAT(a.firstname, ' ', a.lastname)
            END as client_name
            FROM appointments a 
            JOIN services s ON a.service_id = s.service_id 
            JOIN users u ON a.user_id = u.id
            WHERE a.user_id = ? 
            ORDER BY a.appointment_date DESC 
            LIMIT 3";
$stmt = $conn->prepare($sql_appointments);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$appointments = $stmt->get_result();

// Get user's most recent appointment
$sql_recent_appointment = "SELECT a.*, s.service_name,
            CASE 
                WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
                ELSE CONCAT(a.firstname, ' ', a.lastname)
            END as client_name,
            CONCAT(t.firstname, ' ', t.lastname) as technician_name,
            a.is_for_self
            FROM appointments a 
            JOIN services s ON a.service_id = s.service_id 
            JOIN users u ON a.user_id = u.id
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
          <li><a href="../Index.php">Home</a></li>
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
        <h3>Appointment Details</h3>
        <div id="appointmentDetails" class="appointment-details">
          <?php if ($recent_appointment): ?>
            <p><strong>Appointment #:</strong> <?php echo $recent_appointment['id']; ?></p>
            <p><strong>Client:</strong> <?php echo htmlspecialchars($recent_appointment['client_name']); ?> 
                <?php echo $recent_appointment['is_for_self'] == 1 ? '(Self)' : '(Other)'; ?></p>
            <p><strong>Type of Service:</strong> <?php echo htmlspecialchars($recent_appointment['service_name']); ?></p>
            <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($recent_appointment['appointment_date'])); ?></p>
            <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($recent_appointment['appointment_time'])); ?></p>
            <p><strong>Location:</strong> <?php echo htmlspecialchars($recent_appointment['street_address']); ?></p>
            <p><strong>Technician:</strong> <?php echo $recent_appointment['technician_name'] ? htmlspecialchars($recent_appointment['technician_name']) : 'Not yet assigned'; ?></p>
            <p>
              <strong>Status:</strong> 
              <span class="appointment-status <?php echo strtolower($recent_appointment['status']); ?>"><?php echo $recent_appointment['status']; ?></span>
            </p>
            
            <?php if ($recent_appointment['status'] === 'Completed'): ?>
              <div class="feedback-section">
                <button class="send-feedback-btn" onclick="openFeedbackModal(<?php echo $recent_appointment['id']; ?>, <?php echo $recent_appointment['service_id']; ?>)">
                  <i class='bx bx-message-square-dots'></i> Send Feedback
                </button>
              </div>
            <?php endif; ?>
          <?php else: ?>
            <p>Select an appointment from the history to view details.</p>
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

  <!-- Feedback Modal -->
  <div id="feedbackModal" class="modal">
    <div class="modal-content feedback-modal-content">
      <span class="close" onclick="closeFeedbackModal()">&times;</span>
      <h2>Share Your Experience</h2>
      <form id="feedbackForm" method="POST" action="submit_review.php">
        <input type="hidden" id="appointment_id" name="appointment_id">
        <input type="hidden" id="service_id" name="service_id">
        
        <div class="feedback-container">
          <!-- Ratings Column (Left) -->
          <div class="ratings-column">
            <!-- Service Rating -->
            <div class="rating-group">
              <p class="rating-label">Service Rating</p>
              <div class="star-rating service-rating">
                <input type="radio" id="service_star5" name="service_rating" value="5" required><label for="service_star5" title="Excellent"></label>
                <input type="radio" id="service_star4" name="service_rating" value="4"><label for="service_star4" title="Very Good"></label>
                <input type="radio" id="service_star3" name="service_rating" value="3"><label for="service_star3" title="Good"></label>
                <input type="radio" id="service_star2" name="service_rating" value="2"><label for="service_star2" title="Fair"></label>
                <input type="radio" id="service_star1" name="service_rating" value="1"><label for="service_star1" title="Poor"></label>
              </div>
            </div>
            
            <!-- Technician Rating -->
            <div class="rating-group">
              <p class="rating-label">Technician Rating</p>
              <div class="star-rating technician-rating">
                <input type="radio" id="tech_star5" name="technician_rating" value="5" required><label for="tech_star5" title="Excellent"></label>
                <input type="radio" id="tech_star4" name="technician_rating" value="4"><label for="tech_star4" title="Very Good"></label>
                <input type="radio" id="tech_star3" name="technician_rating" value="3"><label for="tech_star3" title="Good"></label>
                <input type="radio" id="tech_star2" name="technician_rating" value="2"><label for="tech_star2" title="Fair"></label>
                <input type="radio" id="tech_star1" name="technician_rating" value="1"><label for="tech_star1" title="Poor"></label>
              </div>
            </div>
            
            <!-- Overall Rating -->
            <div class="rating-group">
              <p class="rating-label">Overall Experience</p>
              <div class="star-rating overall-rating">
                <input type="radio" id="star5" name="rating" value="5" required><label for="star5" title="Excellent"></label>
                <input type="radio" id="star4" name="rating" value="4"><label for="star4" title="Very Good"></label>
                <input type="radio" id="star3" name="rating" value="3"><label for="star3" title="Good"></label>
                <input type="radio" id="star2" name="rating" value="2"><label for="star2" title="Fair"></label>
                <input type="radio" id="star1" name="rating" value="1"><label for="star1" title="Poor"></label>
              </div>
            </div>
          </div>
          
          <!-- Inputs Column (Right) -->
          <div class="inputs-column">
            <!-- Service Feedback -->
            <div class="form-group">
              <label for="service_feedback">What did you like about our service?</label>
              <textarea id="service_feedback" name="service_feedback" rows="3" required placeholder="Tell us what you liked about the service..."></textarea>
            </div>
            
            <!-- Issues Reported -->
            <div class="form-group">
              <label for="reported_issues">Any Issues or Concerns?</label>
              <textarea id="reported_issues" name="reported_issues" rows="3" placeholder="Tell us about any issues you encountered..."></textarea>
            </div>
            
            <!-- General Comments -->
            <div class="form-group">
              <label for="review_text">Additional Comments:</label>
              <textarea id="review_text" name="review_text" rows="3" required placeholder="Any other thoughts about your experience..."></textarea>
            </div>
          </div>
        </div>
        
        <div class="form-buttons">
          <button type="submit" class="submit-btn">Submit Feedback</button>
          <button type="button" class="cancel-btn" onclick="closeFeedbackModal()">Cancel</button>
        </div>
      </form>
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

    const modal = document.getElementById('editProfileModal');
    const editBtn = document.querySelector('.edit-btn');
    const closeBtn = document.querySelector('.close');
    const form = document.getElementById('editProfileForm');

    editBtn.onclick = function() {
      modal.style.display = "flex";
      modal.classList.add("show-modal");
    }

    closeBtn.onclick = function() {
      closeModal();
    }

    function closeModal() {
      modal.style.display = "none";
      modal.classList.remove("show-modal");
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
        // Remove active class from all rows
        document.querySelectorAll('.history-row').forEach(r => r.classList.remove('active'));
        // Add active class to clicked row
        this.classList.add('active');
        
        const appointmentId = this.dataset.appointmentId;
        fetch(`get_appointment_details.php?id=${appointmentId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              const details = data.details;
              let detailsHtml = `
                <p><strong>Appointment #:</strong> ${details.id}</p>
                <p><strong>Client:</strong> ${details.client_name} ${details.is_for_self == 1 ? '(Self)' : '(Other)'}</p>
                <p><strong>Type of Service:</strong> ${details.service_name}</p>
                <p><strong>Date:</strong> ${details.appointment_date}</p>
                <p><strong>Time:</strong> ${details.appointment_time}</p>
                <p><strong>Location:</strong> ${details.street_address}</p>
                <p><strong>Technician:</strong> ${details.technician_name || 'Not yet assigned'}</p>
                <p>
                  <strong>Status:</strong> 
                  <span class="appointment-status ${details.status.toLowerCase()}">${details.status}</span>
                </p>`;
                
              // Add Send Feedback button when status is Completed
              if (details.status === 'Completed') {
                detailsHtml += `
                  <div class="feedback-section">
                    <button class="send-feedback-btn" onclick="openFeedbackModal(${details.id}, ${details.service_id})">
                      <i class='bx bx-message-square-dots'></i> Send Feedback
                    </button>
                  </div>`;
              }
              
              document.getElementById('appointmentDetails').innerHTML = detailsHtml;
            } else {
              alert('Error fetching appointment details');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Error fetching appointment details');
          });
      });
    });

    function openFeedbackModal(appointmentId, serviceId) {
      document.getElementById('appointment_id').value = appointmentId;
      document.getElementById('service_id').value = serviceId;
      const feedbackModal = document.getElementById('feedbackModal');
      feedbackModal.style.display = "flex";
      feedbackModal.classList.add("show-modal");
    }
    
    function closeFeedbackModal() {
      const feedbackModal = document.getElementById('feedbackModal');
      feedbackModal.style.display = "none";
      feedbackModal.classList.remove("show-modal");
    }
    
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
      e.preventDefault();
      
      const formData = new FormData(this);
      
      // Show loading state
      const submitBtn = this.querySelector('.submit-btn');
      const originalText = submitBtn.textContent;
      submitBtn.textContent = 'Submitting...';
      submitBtn.disabled = true;
      
      fetch('submit_review.php', {
        method: 'POST',
        body: formData
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok: ' + response.statusText);
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          alert('Thank you for your feedback!');
          closeFeedbackModal();
        } else {
          console.error('Server error:', data);
          alert('Error submitting feedback: ' + (data.message || 'Unknown error'));
        }
      })
      .catch(error => {
        console.error('Submission error:', error);
        alert('An error occurred while submitting your feedback. Please try again later.');
      })
      .finally(() => {
        // Reset button state
        submitBtn.textContent = originalText;
        submitBtn.disabled = false;
      });
    });
  </script>
</body>
</html>
