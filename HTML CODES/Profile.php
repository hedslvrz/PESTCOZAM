<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php");
    exit();
}

// Database connection
require_once '../database.php';
$database = new Database();
$db = $database->getConnection();

// Get user data
$user_id = $_SESSION['user_id'];
$stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bindParam(1, $user_id);
$stmt->execute();
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Set profile picture path - default if not set
$profile_pic = isset($user['profile_pic']) && !empty($user['profile_pic']) 
    ? "../uploads/profile_pictures/" . $user['profile_pic'] 
    : "../Pictures/boy.png";

// Store profile pic in session for use across site
$_SESSION['profile_pic'] = $profile_pic;

// Get user appointments
$stmt = $db->prepare("SELECT a.*, s.service_name,
        CASE 
            WHEN a.is_for_self = 1 THEN CONCAT(u.firstname, ' ', u.lastname)
            ELSE CONCAT(a.firstname, ' ', a.lastname)
        END as client_name
        FROM appointments a 
        JOIN services s ON a.service_id = s.service_id 
        JOIN users u ON a.user_id = u.id
        WHERE a.user_id = ? 
        ORDER BY a.appointment_date DESC 
        LIMIT 10");
$stmt->bindParam(1, $user_id);
$stmt->execute();
$appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if there's a current appointment in the session
$current_appointment = null;
if (isset($_SESSION['current_appointment'])) {
    $current_appointment = $_SESSION['current_appointment'];
} else {
    // Get user's most recent appointment
    $stmt = $db->prepare("SELECT a.*, s.service_name,
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
            LIMIT 1");
    $stmt->bindParam(1, $user_id);
    $stmt->execute();
    $current_appointment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($current_appointment) {
        // Format dates and times for display
        $current_appointment['appointment_date'] = date('F d, Y', strtotime($current_appointment['appointment_date']));
        $current_appointment['appointment_time'] = date('h:i A', strtotime($current_appointment['appointment_time']));
    }
}

// Store the current appointment in a JS variable for receipt download
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>User Profile</title>
    <link rel="stylesheet" href="../CSS CODES/Profile.css" />
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.4.0/jspdf.umd.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
          <li><a href="../index.php">Home</a></li>
          <li><a href="../index.php#offer-section">Services</a></li>
          <li><a href="../index.php#about-us-section">About Us</a></li>
          <li><a href="Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="user-profile">
              <div class="profile-dropdown">
                <img src="<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic">
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
    <!-- LEFT COLUMN (Now full width) -->
    <div class="left-column">
      
      <!-- PROFILE & PERSONAL DETAILS -->
      <div class="profile-section">
        <div class="profile-card">
          <div class="profile-pic-container">
            <img src="<?php echo $profile_pic; ?>" alt="User Avatar" class="avatar" />
            <div class="change-photo-overlay">
              <i class='bx bx-camera'></i>
              <span>Change Photo</span>
            </div>
          </div>
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
            <p><strong>Middle Name:</strong> <?php echo $user['middlename'] ? htmlspecialchars($user['middlename']) : 'Not set'; ?></p>
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
        <h3>Appointment History</h3>
        
        <!-- Status filter buttons -->
        <div class="status-filter-container">
          <button class="status-filter-btn active" data-status="all">All</button>
          <button class="status-filter-btn" data-status="pending">Pending</button>
          <button class="status-filter-btn" data-status="confirmed">Confirmed</button>
          <button class="status-filter-btn" data-status="completed">Completed</button>
        </div>
        
        <!-- Simplified date filter control -->
        <div class="date-filter-container">
          <div class="filter-group">
            <label for="date-filter">Filter by Date:</label>
            <input type="date" id="date-filter" class="date-input" style="cursor: pointer;">
          </div>
        </div>

        <div class="table-container">
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
              <?php foreach($appointments as $appointment): ?>
              <tr class="history-row" 
                  data-appointment-id="<?php echo $appointment['id']; ?>" 
                  data-appointment-date="<?php echo $appointment['appointment_date']; ?>"
                  data-status="<?php echo strtolower($appointment['status']); ?>">
                <td><?php echo $appointment['id']; ?></td>
                <td><?php echo htmlspecialchars($appointment['service_name']); ?></td>
                <td><?php echo date('m/d/y', strtotime($appointment['appointment_date'])); ?></td>
                <td><span class="history-badge <?php echo strtolower($appointment['status']); ?>"><?php echo $appointment['status']; ?></span></td>
              </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Appointment Details Modal -->
  <div id="appointmentModal" class="modal">
    <div class="appointment-modal-content">
      <span class="close-modal" onclick="closeAppointmentModal()">&times;</span>
      <h2>Appointment Details</h2>
      
      <div id="modalAppointmentDetails" class="appointment-details">
        <?php if ($current_appointment): ?>
          <p><strong>Appointment #:</strong> <span id="modal-app-id"><?php echo $current_appointment['id']; ?></span></p>
          <p><strong>Client:</strong> <span id="modal-client-name"><?php echo htmlspecialchars($current_appointment['client_name']); ?> 
              <?php echo $current_appointment['is_for_self'] == 1 ? '(Self)' : '(Other)'; ?></span></p>
          <p><strong>Type of Service:</strong> <span id="modal-service-name"><?php echo htmlspecialchars($current_appointment['service_name']); ?></span></p>
          <p><strong>Date:</strong> <span id="modal-app-date"><?php echo $current_appointment['appointment_date']; ?></span></p>
          <p><strong>Time:</strong> <span id="modal-app-time"><?php echo $current_appointment['appointment_time']; ?></span></p>
          <p><strong>Location:</strong> <span id="modal-location"><?php echo htmlspecialchars($current_appointment['street_address']); ?></span></p>
          
          <p><strong>Property Type:</strong> <span id="modal-property-type"><?php echo ucfirst(htmlspecialchars($current_appointment['property_type'] ?? 'residential')); ?>
            <?php if(isset($current_appointment['establishment_name']) && !empty($current_appointment['establishment_name'])): ?>
              (<?php echo htmlspecialchars($current_appointment['establishment_name']); ?>)
            <?php endif; ?></span>
          </p>
          
          <p><strong>Property Area:</strong> 
            <span id="modal-property-area">
            <?php 
            if(isset($current_appointment['property_area']) && !empty($current_appointment['property_area'])): 
              echo htmlspecialchars($current_appointment['property_area']) . " sq.m"; 
            else: 
              echo "Not specified";
            endif; 
            ?>
            </span>
          </p>
          
          <?php if(isset($current_appointment['pest_concern']) && !empty($current_appointment['pest_concern'])): ?>
          <p><strong>Pest Concern:</strong> <span id="modal-pest-concern"><?php echo nl2br(htmlspecialchars($current_appointment['pest_concern'])); ?></span></p>
          <?php endif; ?>
          
          <p><strong>Email:</strong> <span id="modal-email"><?php echo htmlspecialchars($current_appointment['is_for_self'] == 1 ? $user['email'] : ($current_appointment['email'] ?? 'N/A')); ?></span></p>
          <p><strong>Phone:</strong> <span id="modal-phone"><?php echo htmlspecialchars($current_appointment['is_for_self'] == 1 ? $user['mobile_number'] : ($current_appointment['mobile_number'] ?? 'N/A')); ?></span></p>
          
          <p><strong>Technician:</strong> <span id="modal-technician"><?php echo $current_appointment['technician_name'] ? htmlspecialchars($current_appointment['technician_name']) : 'Not yet assigned'; ?></span></p>
          <p>
            <strong>Status:</strong> 
            <span id="modal-status" class="appointment-status <?php echo strtolower($current_appointment['status']); ?>"><?php echo $current_appointment['status']; ?></span>
          </p>
        <?php endif; ?>
      </div>
      
      <div class="modal-actions">
        <button class="download-receipt-btn" onclick="downloadCurrentReceipt()">
          <i class='bx bx-download'></i> Download Receipt
        </button>
        
        <div id="feedbackButtonContainer" style="display: none;">
          <button class="send-feedback-btn" onclick="openFeedbackModal(currentAppointment.id, currentAppointment.service_id)">
            <i class='bx bx-message-square-dots'></i> Send Feedback
          </button>
        </div>
      </div>
    </div>
  </div>
  
  <!-- Profile Modal -->
  <div id="editProfileModal" class="modal">
    <div class="profile-modal-content">
      <div class="modal-header">
        <h2>Edit Profile</h2>
        <span class="close">&times;</span>
      </div>
      <form id="editProfileForm" method="POST" action="update_profile.php" enctype="multipart/form-data">
        <div class="profile-pic-edit">
          <img src="<?php echo $profile_pic; ?>" alt="Profile Picture" id="preview-profile-pic">
          <div class="pic-edit-overlay">
            <label for="profile_picture"><i class='bx bx-camera'></i> Change</label>
            <input type="file" id="profile_picture" name="profile_picture" accept="image/*" style="display:none">
          </div>
        </div>
        <div class="form-group">
          <label for="firstname">First Name</label>
          <input type="text" id="firstname" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" required>
        </div>
        <div class="form-group">
          <label for="middlename">Middle Name</label>
          <input type="text" id="middlename" name="middlename" value="<?php echo htmlspecialchars($user['middlename'] ?? ''); ?>">
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
          <input type="date" id="dob" name="dob" value="<?php echo $user['dob'] ? date('Y-m-d', strtotime($user['dob'])) : ''; ?>">
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

    editBtn.onclick = function() {
      modal.classList.add("show");
    }

    closeBtn.onclick = function() {
      closeModal();
    }

    function closeModal() {
      modal.classList.remove("show");
    }

    let currentAppointment = <?php echo json_encode($current_appointment); ?>;

    document.querySelectorAll('.history-row').forEach(row => {
      row.addEventListener('click', function() {
        // Remove active class from all rows
        document.querySelectorAll('.history-row').forEach(r => r.classList.remove('active'));
        // Add active class to clicked row
        this.classList.add('active');
        
        const appointmentId = this.dataset.appointmentId;
        
        // Show loading indicator or spinner if needed
        
        // Fetch appointment data
        fetch(`get_appointment_data.php?id=${appointmentId}`)
          .then(response => response.json())
          .then(data => {
            if (data.success) {
              currentAppointment = data.appointment;
              updateAppointmentModal(currentAppointment);
              openAppointmentModal();
            } else {
              Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'Could not load appointment details: ' + data.message,
                confirmButtonColor: '#144578'
              });
            }
          })
          .catch(error => {
            console.error('Error:', error);
            Swal.fire({
              icon: 'error',
              title: 'Error',
              text: 'An error occurred while loading appointment details',
              confirmButtonColor: '#144578'
            });
          });
      });
    });

    function updateAppointmentModal(appointment) {
      // Update all the fields in the modal with appointment data
      document.getElementById('modal-app-id').textContent = appointment.id;
      document.getElementById('modal-client-name').textContent = appointment.client_name + 
        (appointment.is_for_self == 1 ? ' (Self)' : ' (Other)');
      document.getElementById('modal-service-name').textContent = appointment.service_name;
      document.getElementById('modal-app-date').textContent = appointment.appointment_date;
      document.getElementById('modal-app-time').textContent = appointment.appointment_time;
      document.getElementById('modal-location').textContent = appointment.street_address;
      
      // Property type with establishment name if available
      let propertyTypeText = appointment.property_type ? 
        (appointment.property_type.charAt(0).toUpperCase() + appointment.property_type.slice(1)) : 
        'Residential';
      
      if (appointment.establishment_name) {
        propertyTypeText += ` (${appointment.establishment_name})`;
      }
      document.getElementById('modal-property-type').textContent = propertyTypeText;
      
      // Property area
      document.getElementById('modal-property-area').textContent = 
        appointment.property_area ? `${appointment.property_area} sq.m` : 'Not specified';
      
      // Optional fields
      const pestConcernElement = document.getElementById('modal-pest-concern');
      if (pestConcernElement) {
        pestConcernElement.innerHTML = appointment.pest_concern ? 
          appointment.pest_concern.replace(/\n/g, '<br>') : '';
      }
      
      // Set email and phone based on whether appointment is for self or not
      if (appointment.is_for_self == 1) {
        document.getElementById('modal-email').textContent = appointment.user_email || 'N/A';
        document.getElementById('modal-phone').textContent = appointment.user_mobile || 'N/A';
      } else {
        document.getElementById('modal-email').textContent = appointment.email || 'N/A';
        document.getElementById('modal-phone').textContent = appointment.mobile_number || 'N/A';
      }
      
      document.getElementById('modal-technician').textContent = appointment.technician_name || 'Not yet assigned';
      
      // Status with proper class
      const statusElement = document.getElementById('modal-status');
      statusElement.textContent = appointment.status;
      statusElement.className = 'appointment-status ' + appointment.status.toLowerCase();
      
      // Show/hide feedback button based on status
      const feedbackContainer = document.getElementById('feedbackButtonContainer');
      if (appointment.status === 'Completed') {
        feedbackContainer.style.display = 'block';
      } else {
        feedbackContainer.style.display = 'none';
      }
    }

    function openAppointmentModal() {
      const modal = document.getElementById('appointmentModal');
      modal.classList.add('show');
    }
    
    function closeAppointmentModal() {
      const modal = document.getElementById('appointmentModal');
      modal.classList.remove('show');
    }

    function openFeedbackModal(appointmentId, serviceId) {
      document.getElementById('appointment_id').value = appointmentId;
      document.getElementById('service_id').value = serviceId;
      const feedbackModal = document.getElementById('feedbackModal');
      feedbackModal.style.display = "flex";
      feedbackModal.classList.add("show-modal");
      
      // Close the appointment modal when opening feedback
      closeAppointmentModal();
    }
    
    async function downloadCurrentReceipt() {
      if (!currentAppointment) {
        Swal.fire({
          icon: 'warning',
          title: 'No Selection',
          text: 'No appointment selected to download.',
          confirmButtonColor: '#144578'
        });
        return;
      }

      const { jsPDF } = window.jspdf;
      const doc = new jsPDF();

      doc.setFont("helvetica", "bold");
      doc.setFontSize(16);
      doc.text("PESTCOZAM Appointment Receipt", 105, 20, { align: "center" });

      doc.setFont("helvetica", "normal");
      doc.setFontSize(12);
      doc.text("Estrada St, Zamboanga City, Zamboanga Del Sur, 7000", 105, 30, { align: "center" });
      doc.text("Contact: 0905-177-5662 | Email: pestcozam@yahoo.com", 105, 36, { align: "center" });

      doc.line(10, 40, 200, 40);

      doc.setFontSize(12);
      doc.text(`Appointment #: ${currentAppointment.id}`, 10, 50);
      doc.text(`Client: ${currentAppointment.client_name} ${currentAppointment.is_for_self == 1 ? '(Self)' : '(Other)'}`, 10, 60);
      doc.text(`Type of Service: ${currentAppointment.service_name}`, 10, 70);
      doc.text(`Date: ${currentAppointment.appointment_date}`, 10, 80);
      doc.text(`Time: ${currentAppointment.appointment_time}`, 10, 90);
      doc.text(`Location: ${currentAppointment.street_address}`, 10, 100);
      
      let yPos = 110;
      
      const propertyTypeText = `Property Type: ${currentAppointment.property_type ? currentAppointment.property_type.charAt(0).toUpperCase() + currentAppointment.property_type.slice(1) : 'Residential'}`;
      const establishmentText = currentAppointment.establishment_name ? ` (${currentAppointment.establishment_name})` : '';
      doc.text(propertyTypeText + establishmentText, 10, yPos);
      yPos += 10;
      
      if (currentAppointment.property_area) {
        doc.text(`Property Area: ${currentAppointment.property_area} sq.m`, 10, yPos);
        yPos += 10;
      } else {
        doc.text(`Property Area: Not specified`, 10, yPos);
        yPos += 10;
      }
      
      if (currentAppointment.pest_concern) {
        const concernText = `Pest Concern: ${currentAppointment.pest_concern}`;
        if (concernText.length > 80) {
          doc.text(`Pest Concern:`, 10, yPos);
          yPos += 6;
          const wrappedText = doc.splitTextToSize(currentAppointment.pest_concern, 180);
          doc.text(wrappedText, 15, yPos);
          yPos += (wrappedText.length * 6);
        } else {
          doc.text(concernText, 10, yPos);
          yPos += 10;
        }
      }
      
      doc.text(`Email: ${currentAppointment.email || 'N/A'}`, 10, yPos);
      yPos += 10;
      doc.text(`Phone: ${currentAppointment.mobile_number || 'N/A'}`, 10, yPos);
      yPos += 10;
      
      doc.text(`Technician: ${currentAppointment.technician_name || 'Not yet assigned'}`, 10, yPos);
      yPos += 10;
      doc.text(`Status: ${currentAppointment.status}`, 10, yPos);

      doc.setFontSize(10);
      doc.text("Thank you for choosing PESTCOZAM!", 105, 280, { align: "center" });

      doc.save(`Appointment_${currentAppointment.id}_Receipt.pdf`);
    }

    // Enhanced filtering functionality for history table
    document.addEventListener('DOMContentLoaded', function() {
      const dateFilter = document.getElementById('date-filter');
      const statusButtons = document.querySelectorAll('.status-filter-btn');
      const historyRows = document.querySelectorAll('.history-row');
      let currentStatus = 'all';
      
      // Apply both date and status filters
      function applyFilters() {
        const selectedDate = dateFilter.value ? new Date(dateFilter.value) : null;
        
        // Set selected date to midnight for proper comparison if not null
        if (selectedDate) {
          selectedDate.setHours(0, 0, 0, 0);
        }
        
        historyRows.forEach(row => {
          const rowStatus = row.getAttribute('data-status');
          const rowDate = new Date(row.getAttribute('data-appointment-date'));
          rowDate.setHours(0, 0, 0, 0); // Set to midnight for date-only comparison
          
          // Status filter
          const statusMatch = currentStatus === 'all' || rowStatus === currentStatus;
          
          // Date filter
          const dateMatch = !selectedDate || rowDate.getTime() === selectedDate.getTime();
          
          // Show row only if both filters match
          row.style.display = (statusMatch && dateMatch) ? '' : 'none';
        });
      }
      
      // Status filter button click handler
      statusButtons.forEach(button => {
        button.addEventListener('click', function() {
          // Remove active class from all buttons
          statusButtons.forEach(btn => btn.classList.remove('active'));
          
          // Add active class to clicked button
          this.classList.add('active');
          
          // Update current status filter
          currentStatus = this.getAttribute('data-status');
          
          // Apply both filters
          applyFilters();
        });
      });
      
      // Date filter change handler
      dateFilter.addEventListener('change', function() {
        applyFilters();
      });
    });

    // Close modal when clicking outside
    window.onclick = function(event) {
      const appointmentModal = document.getElementById('appointmentModal');
      const feedbackModal = document.getElementById('feedbackModal');
      const profileModal = document.getElementById('editProfileModal');
      
      if (event.target == appointmentModal) {
        closeAppointmentModal();
      } else if (event.target == feedbackModal) {
        closeFeedbackModal();
      } else if (event.target == profileModal) {
        closeModal();
      }
    }

    // Add this JavaScript to the end of your Profile.php file or update the existing script
    document.getElementById('feedbackForm').addEventListener('submit', function(e) {
        e.preventDefault(); // Prevent regular form submission
        
        // Create FormData object to collect form data
        const formData = new FormData(this);
        
        // Submit form via AJAX
        fetch('submit_review.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            // Close the feedback modal
            closeFeedbackModal();
            
            // Show success message
            if (data.success) {
                Swal.fire({
                    icon: 'success',
                    title: 'Success!',
                    text: 'Feedback submitted successfully!',
                    confirmButtonColor: '#144578',
                    timer: 3000,
                    timerProgressBar: true
                });
                // Optionally reload the page to refresh any data
                // window.location.reload();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: 'Error: ' + data.message,
                    confirmButtonColor: '#144578'
                });
            }
        })
        .catch(error => {
            console.error('Error:', error);
            Swal.fire({
                icon: 'error',
                title: 'Error',
                text: 'An error occurred while submitting your feedback.',
                confirmButtonColor: '#144578'
            });
        });
    });

    function closeFeedbackModal() {
        const feedbackModal = document.getElementById('feedbackModal');
        feedbackModal.style.display = "none";
        feedbackModal.classList.remove("show-modal");
        
        // Clear form fields
        document.getElementById('feedbackForm').reset();
    }

    // Profile picture change functionality
    document.addEventListener('DOMContentLoaded', function() {
      // Handle profile picture overlay click
      const profilePicContainer = document.querySelector('.profile-pic-container');
      if (profilePicContainer) {
        profilePicContainer.addEventListener('click', function() {
          document.getElementById('editProfileModal').classList.add("show");
          // Focus on the file input
          setTimeout(() => {
            document.getElementById('profile_picture').click();
          }, 300);
        });
      }

      // Preview the selected image
      const profilePictureInput = document.getElementById('profile_picture');
      if (profilePictureInput) {
        profilePictureInput.addEventListener('change', function() {
          const file = this.files[0];
          if (file) {
            const reader = new FileReader();
            reader.onload = function(e) {
              document.getElementById('preview-profile-pic').src = e.target.result;
            }
            reader.readAsDataURL(file);
          }
        });
      }
    });
  </script>

  <style>
    /* Enhanced Profile Modal Styling to match dashboard-pct */
    #editProfileModal {
      display: none;
      position: fixed;
      z-index: 2000;
      left: 0;
      top: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      overflow: hidden;
      padding: 0;
      box-sizing: border-box;
      align-items: center;
      justify-content: center;
      opacity: 0;
      visibility: hidden;
      transition: opacity 0.3s ease, visibility 0.3s ease;
    }
    
    #editProfileModal.show {
      opacity: 1;
      visibility: visible;
      display: flex;
    }
    
    .profile-modal-content {
      background-color: #fff;
      margin: auto;
      padding: 35px 30px;
      border-radius: 15px;
      width: 90%;
      max-width: 550px;
      max-height: 80vh;
      overflow-y: auto;
      position: relative;
      box-shadow: 0 10px 30px rgba(0,0,0,0.25);
      animation: modalFadeIn 0.3s ease forwards;
    }
    
    /* Modal header style similar to profile section */
    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      padding: 0 0 15px 0;
      border-bottom: 1px solid #e0e0e0;
      margin-bottom: 20px;
    }
    
    /* Increase header font size */
    .modal-header h2 {
      font-size: 1.75rem;
      margin: 0;
      color: #144578;
    }
    
    /* Close button styling */
    .modal-header .close {
      font-size: 24px;
      font-weight: bold;
      color: #666;
      cursor: pointer;
      transition: color 0.2s ease;
    }
    
    .modal-header .close:hover {
      color: #144578;
    }
    
    /* Add modal animation */
    @keyframes modalFadeIn {
      from {
        opacity: 0;
        transform: translateY(-20px);
      }
      to {
        opacity: 1;
        transform: translateY(0);
      }
    }
    
    /* Enhanced form inputs */
    #editProfileForm .form-group {
      margin-bottom: 20px;
    }
    
    #editProfileForm label {
      display: block;
      margin-bottom: 8px;
      color: #144578;
      font-weight: 600;
      font-size: 1rem;
    }
    
    #editProfileForm input {
      width: 100%;
      padding: 12px 15px;
      border: 2px solid #d0e1fd;
      border-radius: 10px;
      font-size: 1rem;
      background-color: #f8faff;
      transition: all 0.3s ease;
    }
    
    #editProfileForm input:focus {
      border-color: #144578;
      box-shadow: 0 0 0 3px rgba(20, 69, 120, 0.1);
      outline: none;
    }
    
    /* Form buttons in modal */
    .form-buttons {
      display: flex;
      justify-content: flex-end;
      gap: 15px;
      margin-top: 25px;
      padding-top: 20px;
      border-top: 1px solid #e0e0e0;
    }
    
    .save-btn, .cancel-btn {
      padding: 10px 20px;
      border-radius: 8px;
      font-weight: 600;
      cursor: pointer;
      transition: all 0.3s ease;
    }
    
    .save-btn {
      background: #144578;
      color: white;
      border: none;
    }
    
    .cancel-btn {
      background: white;
      color: #666;
      border: 1px solid #ddd;
    }
    
    .save-btn:hover, .cancel-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
    }
    
    /* Responsive adjustments for the modal */
    @media (max-width: 768px) {
      .profile-modal-content {
        padding: 25px 20px;
        width: 95%;
      }
      
      .form-buttons {
        flex-direction: column-reverse;
      }
      
      .save-btn, .cancel-btn {
        width: 100%;
        text-align: center;
      }
    }
    
    /* Profile picture upload styling */
    .profile-pic-container {
      position: relative;
      width: 70px;
      height: 70px;
      border-radius: 50%;
      overflow: hidden;
      cursor: pointer;
    }
    
    .avatar {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .change-photo-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      color: white;
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .profile-pic-container:hover .change-photo-overlay {
      opacity: 1;
    }
    
    .change-photo-overlay i {
      font-size: 20px;
      margin-bottom: 4px;
    }
    
    .change-photo-overlay span {
      font-size: 10px;
      text-align: center;
    }
    
    .profile-pic-edit {
      position: relative;
      width: 100px;
      height: 100px;
      margin: 0 auto 20px;
      border-radius: 50%;
      overflow: hidden;
    }
    
    .profile-pic-edit img {
      width: 100%;
      height: 100%;
      object-fit: cover;
    }
    
    .pic-edit-overlay {
      position: absolute;
      bottom: 0;
      left: 0;
      width: 100%;
      background-color: rgba(0, 0, 0, 0.5);
      color: white;
      text-align: center;
      padding: 5px 0;
      cursor: pointer;
    }
    
    .pic-edit-overlay label {
      cursor: pointer;
      font-size: 12px;
      display: block;
      width: 100%;
    }
  </style>
</body>
</html>
