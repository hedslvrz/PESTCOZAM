<?php
session_start();
require_once "../database.php";
require_once "../PHP CODES/AppointmentSession.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php"); // Redirect if not logged in
    exit();
}

// Verify access to this step
if (!isset($_SESSION['appointment']) || !AppointmentSession::canAccessStep('calendar')) {
    header("Location: Appointment-service.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];
$serviceData = AppointmentSession::getData('service');
if (!$serviceData) {
    header("Location: Appointment-service.php");
    exit();
}
$service_id = $serviceData['service_id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (isset($data['appointment_date'], $data['appointment_time'], $data['service_id'])) {
        // Store in session
        AppointmentSession::saveStep('calendar', [
            'appointment_date' => $data['appointment_date'],
            'appointment_time' => $data['appointment_time'],
            'service_id' => $data['service_id']
        ]);
        
        // Return success response to redirect to confirmation page
        echo json_encode(["success" => true]);
        exit();
    }
}

// Pre-populate form fields if calendar data exists
$calendarData = AppointmentSession::getData('calendar', []);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Appointment Calendar</title>
  <!-- Link to your external CSS file -->
  <link rel="stylesheet" href="../CSS CODES/Appointment-calendar.css" />
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  <!-- Add SweetAlert2 CSS and JS -->
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.min.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
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
        <span class="brand-name" style="font-size: 2rem;">PESTCOZAM</span>
    </div>
    <nav>
        <ul>
            <li><a href="../index.php">Home</a></li>
            <li><a href="../index.php#offer-section">Services</a></li>
            <li><a href="../index.php#about-us-section">About Us</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Book Appointment</a></li>
            
            <?php if (isset($_SESSION['user_id'])): ?>
                <?php 
                    $profile_pic = isset($_SESSION['profile_pic']) ? $_SESSION['profile_pic'] : '../Pictures/boy.png';
                ?>
                <li class="user-profile">
                    <div class="profile-dropdown">
                        <img src="<?php echo $profile_pic; ?>" alt="Profile" class="profile-pic">
                        <div class="dropdown-content">
                            <a href="../HTML CODES/Profile.php"><i class='bx bx-user'></i> Profile</a>
                            <a href="../HTML CODES/logout.php"><i class='bx bx-log-out'></i> Logout</a>
                        </div>
                    </div>
                </li>
            <?php else: ?>
                <li class="auth-buttons">
                    <a href="../HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in'></i> Login</a>
                    <a href="../HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus'></i> Sign Up</a>
                </li>
            <?php endif; ?>
        </ul>
    </nav>
</header>

<!-- Progress Bar -->
<div class="progress-bar">
    <div class="progress-step completed">
        <div class="circle">1</div>
        <div class="label">Select Service</div>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step completed">
        <div class="circle">2</div>
        <div class="label">Location</div>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step completed">
        <div class="circle">3</div>
        <div class="label">Personal Info</div>
    </div>
    <div class="progress-line completed"></div>
    <div class="progress-step active">
        <div class="circle">4</div>
        <div class="label">Schedule</div>
    </div>
</div>

  <!-- CALENDAR SECTION -->
  <main class="calendar-section">
    <div class="calendar-container">
      <!-- Instruction Text -->
      <p class="instruction">Please select preferred Date and Time.</p>

      <!-- Month & Year Selection -->
      <div class="month-year-container">
        <label for="month-select">Month:</label>
        <select id="month-select"></select>

        <label for="year-select">Year:</label>
        <select id="year-select"></select>
      </div>

      <!-- Calendar Grid -->
      <div class="calendar-grid">
        <!-- Day Labels -->
        <div class="day-label">SUN</div>
        <div class="day-label">MON</div>
        <div class="day-label">TUE</div>
        <div class="day-label">WED</div>
        <div class="day-label">THU</div>
        <div class="day-label">FRI</div>
        <div class="day-label">SAT</div>

        <!-- Calendar Days (Populated by JS) -->
        <div id="calendar-days" class="calendar-days"></div>
      </div>

      <!-- Schedule Table -->
      <div class="schedule-table">
        <h3 id="selected-date-heading">Select a date first</h3>
        <form id="appointmentForm">
          <table>
            <thead>
              <tr>
                <th>Time</th>
                <th>Availability</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody id="timeSlots">
              <tr>
                <td colspan="3" class="loading-slots">Select a date to view available time slots</td>
              </tr>
            </tbody>
          </table>
        </form>
      </div>

      <!-- Service Type Selection -->
      <div class="service-type-form">
        <h3>Select Service Type</h3>
        <div class="service-options">
          <label class="service-option" for="ocular">
            <input type="radio" id="ocular" name="service_id" value="17" required>
            <div class="service-details">
              <span class="service-title">Ocular Inspection</span>
              <p>Initial assessment of pest problems</p>
            </div>
          </label>
          <label class="service-option" for="treatment">
            <input type="radio" id="treatment" name="service_id" value="<?php echo htmlspecialchars($service_id); ?>" required>
            <div class="service-details">
              <span class="service-title">Treatment Service</span>
              <p>Full pest control implementation</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Navigation Buttons -->
      <div class="calendar-nav">
        <?php
        // Check if appointment is for self or someone else
        $is_for_self = $serviceData['is_for_self'] ?? 1;
        $backUrl = $is_for_self == 1 ? 'Appointment-loc.php' : 'Appointment-info.php';
        ?>
        <button class="back-btn" onclick="window.location.href='<?php echo $backUrl; ?>'">Back</button>
        <button class="next-btn" id="nextButton" onclick="saveDateTime()" disabled>Next</button>
      </div>
    </div>
  </main>

  <!-- Treatment Confirmation Modal -->
  <div id="treatmentModal" class="modal">
    <div class="modal-content">
      <div class="modal-header">
        <h2>Confirmation</h2>
        <span class="close-modal">&times;</span>
      </div>
      <div class="modal-body">
        <div class="warning-icon">⚠️</div>
        <p>Are you sure you want to skip Ocular Inspection? A technician may still perform an inspection if additional pest issues are found.</p>
      </div>
      <div class="modal-footer">
        <button id="cancelTreatment" class="modal-btn cancel-btn">Cancel</button>
        <button id="confirmTreatment" class="modal-btn confirm-btn">Proceed with Treatment</button>
      </div>
    </div>
  </div>

 <!-- FOOTER SECTION -->
 <footer class="footer-section">
  <div class="footer-container">
    <div class="footer-left">
      <div class="footer-brand">
        <img src="../Pictures/pest_logo.png" alt="Flower icon" class="flower-icon" />
        <h3 class="brand-name">PESTCOZAM</h3>
      </div>
      <p class="footer-copyright">
        © 2025 Pestcozam. All rights reserved. 
        Designed by FHASK Solutions
      </p>
    </div>
    <div class="footer-right">
      <p class="follow-us-text">Follow us</p>
      <div class="social-icons">
        <a href="#"><img src="../Pictures/facebook.png" alt="Facebook" /></a>
        <a href="#"><img src="../Pictures/telegram.png" alt="Telegram" /></a>
        <a href="#"><img src="../Pictures/instagram.png" alt="Instagram" /></a>
      </div>
    </div>
  </div>
</footer>

  <script>
    const monthSelect = document.getElementById('month-select');
    const yearSelect = document.getElementById('year-select');
    const calendarDays = document.getElementById('calendar-days');
    const selectedDateHeading = document.getElementById('selected-date-heading');
    const timeSlotsContainer = document.getElementById('timeSlots');

    const monthNames = [
      'January', 'February', 'March', 'April', 'May', 'June',
      'July', 'August', 'September', 'October', 'November', 'December'
    ];
    monthNames.forEach((month, index) => {
      const option = document.createElement('option');
      option.value = index;
      option.textContent = month;
      monthSelect.appendChild(option);
    });

    const currentYear = new Date().getFullYear();
    for (let year = currentYear; year <= currentYear + 10; year++) {
      const option = document.createElement('option');
      option.value = year;
      option.textContent = year;
      yearSelect.appendChild(option);
    }

    const today = new Date();
    monthSelect.value = today.getMonth();
    yearSelect.value = today.getFullYear();

    monthSelect.addEventListener('change', renderCalendar);
    yearSelect.addEventListener('change', renderCalendar);

    function renderCalendar() {
      const selectedMonth = parseInt(monthSelect.value);
      const selectedYear = parseInt(yearSelect.value);

      calendarDays.innerHTML = '';

      const firstDayOfMonth = new Date(selectedYear, selectedMonth, 1);
      const lastDayOfMonth = new Date(selectedYear, selectedMonth + 1, 0);
      const daysInMonth = lastDayOfMonth.getDate();

      const startDay = firstDayOfMonth.getDay();

      for (let i = 0; i < startDay; i++) {
        const blankDay = document.createElement('div');
        blankDay.classList.add('day', 'disabled');
        calendarDays.appendChild(blankDay);
      }

      const currentDate = new Date();
      currentDate.setHours(0, 0, 0, 0);

      for (let dayNum = 1; dayNum <= daysInMonth; dayNum++) {
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('day');
        dayDiv.textContent = dayNum;

        // Check if this day is in the past
        const thisDay = new Date(selectedYear, selectedMonth, dayNum);
        if (thisDay < currentDate) {
          dayDiv.classList.add('disabled');
          dayDiv.title = 'Past dates cannot be selected';
        } else {
          dayDiv.addEventListener('click', () => {
            document.querySelectorAll('.day.selected').forEach(d => {
              d.classList.remove('selected');
            });
            dayDiv.classList.add('selected');

            selectedDate = `${selectedYear}-${(selectedMonth + 1).toString().padStart(2, '0')}-${dayNum.toString().padStart(2, '0')}`;
            selectedDateHeading.textContent = `${monthNames[selectedMonth]} ${dayNum}, ${selectedYear}`;
            
            // Show loading state
            timeSlotsContainer.innerHTML = '<tr><td colspan="3" class="loading-slots"><i class="bx bx-loader-alt bx-spin"></i> Loading available time slots...</td></tr>';
            
            // Reset time selection when date changes
            selectedTime = '';
            document.getElementById('nextButton').disabled = true;
            
            // Fetch time slots for the selected date
            fetchTimeSlots(selectedDate);
          });
        }

        calendarDays.appendChild(dayDiv);
      }
    }
    
    renderCalendar();

    let selectedDate = '<?php echo !empty($calendarData['appointment_date']) ? $calendarData['appointment_date'] : ''; ?>';
    let selectedTime = '<?php echo !empty($calendarData['appointment_time']) ? $calendarData['appointment_time'] : ''; ?>';
    
    function fetchTimeSlots(date) {
      fetch(`../PHP CODES/fetch_time_slots.php?date=${date}`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Network response was not ok');
          }
          return response.json();
        })
        .then(data => {
          if (data.success) {
            displayTimeSlots(data.time_slots);
          } else {
            timeSlotsContainer.innerHTML = `<tr><td colspan="3" class="error-message">Error: ${data.message || 'Failed to load time slots'}</td></tr>`;
          }
        })
        .catch(error => {
          console.error('Error fetching time slots:', error);
          timeSlotsContainer.innerHTML = '<tr><td colspan="3" class="error-message">Error loading time slots. Please try again.</td></tr>';
        });
    }
    
    function displayTimeSlots(timeSlots) {
      timeSlotsContainer.innerHTML = '';
      
      if (!timeSlots || timeSlots.length === 0) {
        timeSlotsContainer.innerHTML = '<tr><td colspan="3">No time slots available for this date.</td></tr>';
        return;
      }
      
      // No need to sort as they come pre-sorted from the server
      timeSlots.forEach(slot => {
        const row = document.createElement('tr');
        if (!slot.is_available) {
          row.classList.add('closed-slot');
        }
        
        // Time column
        const timeCell = document.createElement('td');
        timeCell.textContent = slot.time_range;
        row.appendChild(timeCell);
        
        // Availability column
        const availabilityCell = document.createElement('td');
        availabilityCell.classList.add('slot-status');
        
        if (slot.is_available) {
          availabilityCell.textContent = `Open (${slot.available_slots} ${slot.available_slots === 1 ? 'slot' : 'slots'} left)`;
        } else {
          availabilityCell.textContent = 'Closed (0 slots)';
        }
        row.appendChild(availabilityCell);
        
        // Action column
        const actionCell = document.createElement('td');
        const selectButton = document.createElement('button');
        selectButton.type = 'button';
        selectButton.classList.add('select-time');
        selectButton.dataset.time = slot.time_range;
        selectButton.textContent = 'Select';
        
        if (!slot.is_available) {
          selectButton.disabled = true;
        } else {
          selectButton.addEventListener('click', (e) => {
            document.querySelectorAll('.select-time').forEach(btn => {
              btn.classList.remove('selected');
            });
            e.target.classList.add('selected');
            
            // Extract the start time (e.g., "07:00 AM" from "07:00 AM - 09:00 AM")
            const timeRange = e.target.dataset.time;
            const startTime = timeRange.split(' - ')[0];
            
            // Convert to 24-hour format for database
            const [time, period] = startTime.split(' ');
            const [hour, minute] = time.split(':');
            let hour24 = parseInt(hour);
            
            if (period === 'PM' && hour24 < 12) hour24 += 12;
            if (period === 'AM' && hour24 === 12) hour24 = 0;
            
            // Format as HH:MM:00
            selectedTime = `${hour24.toString().padStart(2, '0')}:${minute}:00`;
            
            document.getElementById('nextButton').disabled = false;
          });
        }
        
        actionCell.appendChild(selectButton);
        row.appendChild(actionCell);
        
        timeSlotsContainer.appendChild(row);
      });
    }

    // Initialize service type radios with event listeners
    document.addEventListener('DOMContentLoaded', function() {
      // Treatment selection confirmation
      const treatmentRadio = document.getElementById('treatment');
      const ocularRadio = document.getElementById('ocular');
      const modal = document.getElementById('treatmentModal');
      const closeBtn = document.querySelector('.close-modal');
      const cancelBtn = document.getElementById('cancelTreatment');
      const confirmBtn = document.getElementById('confirmTreatment');
      let serviceWasChanged = false;

      // Show modal when treatment is selected
      treatmentRadio.addEventListener('change', function(e) {
        if (this.checked) {
          modal.style.display = 'block';
          serviceWasChanged = true;
        }
      });

      // Close modal handlers
      closeBtn.onclick = function() {
        modal.style.display = 'none';
        if (serviceWasChanged) {
          ocularRadio.checked = true;
          serviceWasChanged = false;
        }
      }

      // Cancel treatment selection
      cancelBtn.onclick = function() {
        modal.style.display = 'none';
        ocularRadio.checked = true;
        serviceWasChanged = false;
      }

      // Confirm treatment selection
      confirmBtn.onclick = function() {
        modal.style.display = 'none';
        serviceWasChanged = false;
        // Treatment radio stays selected
      }

      // Close modal if clicked outside
      window.onclick = function(event) {
        if (event.target == modal) {
          modal.style.display = 'none';
          if (serviceWasChanged) {
            ocularRadio.checked = true;
            serviceWasChanged = false;
          }
        }
      }

      <?php if (!empty($calendarData['service_id'])): ?>
      const serviceId = <?php echo $calendarData['service_id']; ?>;
      const serviceRadio = document.querySelector(`input[name="service_id"][value="${serviceId}"]`);
      if (serviceRadio) {
          serviceRadio.checked = true;
      }
      <?php endif; ?>
      
      // If date is pre-selected
      <?php if (!empty($calendarData['appointment_date'])): ?>
      const dateParts = selectedDate.split('-');
      const year = parseInt(dateParts[0]);
      const month = parseInt(dateParts[1]) - 1;
      const day = parseInt(dateParts[2]);
      
      monthSelect.value = month;
      yearSelect.value = year;
      renderCalendar();
      
      // Select the date in calendar
      setTimeout(() => {
          const dayElements = document.querySelectorAll('.day');
          dayElements.forEach(dayElement => {
              if (parseInt(dayElement.textContent) === day && !dayElement.classList.contains('disabled')) {
                  dayElement.click();
              }
          });
      }, 100);
      <?php endif; ?>
    });

    function saveDateTime() {
      if (!selectedDate || !selectedTime) {
        Swal.fire({
          icon: 'warning',
          title: 'Incomplete Selection',
          text: 'Please select both date and time',
        });
        return;
      }

      const serviceId = document.querySelector('input[name="service_id"]:checked');
      if (!serviceId) {
        Swal.fire({
          icon: 'warning',
          title: 'Service Type Missing',
          text: 'Please select a service type',
        });
        return;
      }

      // Disable the button to prevent multiple submissions
      document.getElementById('nextButton').disabled = true;
      document.getElementById('nextButton').textContent = 'Processing...';

      fetch('Appointment-calendar.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          appointment_date: selectedDate,
          appointment_time: selectedTime,
          service_id: serviceId.value
        })
      })
      .then(response => {
        if (!response.ok) {
          throw new Error('Network response was not ok');
        }
        return response.json();
      })
      .then(data => {
        if (data.success) {
          Swal.fire({
            icon: 'success',
            title: 'Appointment Saved',
            text: 'Your appointment details have been saved successfully!',
          }).then(() => {
            window.location.href = 'Appointment-successful.php';
          });
        } else {
          Swal.fire({
            icon: 'error',
            title: 'Error',
            text: data.message || 'Failed to save appointment details',
          });
          document.getElementById('nextButton').disabled = false;
          document.getElementById('nextButton').textContent = 'Next';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        Swal.fire({
          icon: 'error',
          title: 'Error',
          text: 'An error occurred. Please try again.',
        });
        document.getElementById('nextButton').disabled = false;
        document.getElementById('nextButton').textContent = 'Next';
      });
    }
  </script>
</body>
</html>
