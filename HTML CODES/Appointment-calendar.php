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
            <li><a href="../Index.php">Home</a></li>
            <li><a href="../HTML CODES/About_us.html">About Us</a></li>
            <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
            <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
            
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
                <td>07:00 AM - 09:00 AM</td>
                <td class="slot-status">Open (5 more slots)</td>
                <td><button type="button" class="select-time" data-time="07:00 AM - 09:00 AM">Select</button></td>
              </tr>
              <tr class="closed-slot">
                <td>09:00 AM - 11:00 AM</td>
                <td class="slot-status">Closed (0 slots)</td>
                <td><button type="button" class="select-time" data-time="09:00 AM - 11:00 AM" disabled>Select</button></td>
              </tr>
              <tr>
                <td>11:00 AM - 01:00 PM</td>
                <td class="slot-status">Open (3 more slots)</td>
                <td><button type="button" class="select-time" data-time="11:00 AM - 01:00 PM">Select</button></td>
              </tr>
              <tr>
                <td>01:00 PM - 03:00 PM</td>
                <td class="slot-status">Open (4 more slots)</td>
                <td><button type="button" class="select-time" data-time="01:00 PM - 03:00 PM">Select</button></td>
              </tr>
              <tr>
                <td>03:00 PM - 05:00 PM</td>
                <td class="slot-status">Open (2 more slots)</td>
                <td><button type="button" class="select-time" data-time="03:00 PM - 05:00 PM">Select</button></td>
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
            <input type="radio" id="ocular" name="service_id" value="1" required>
            <div class="service-details">
              <span class="service-title">Ocular Inspection</span>
              <p>Initial assessment of pest problems</p>
            </div>
          </label>
          <label class="service-option" for="treatment">
            <input type="radio" id="treatment" name="service_id" value="2" required>
            <div class="service-details">
              <span class="service-title">Treatment Service</span>
              <p>Full pest control implementation</p>
            </div>
          </label>
        </div>
      </div>

      <!-- Navigation Buttons -->
      <div class="calendar-nav">
        <button class="back-btn" onclick="window.location.href='Appointment-info.php'">Back</button>
        <button class="next-btn" disabled id="nextButton" onclick="saveDateTime()">Next</button>
      </div>
    </div>
  </main>

  
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

      for (let dayNum = 1; dayNum <= daysInMonth; dayNum++) {
        const dayDiv = document.createElement('div');
        dayDiv.classList.add('day');
        dayDiv.textContent = dayNum;

        dayDiv.addEventListener('click', () => {
          document.querySelectorAll('.day.selected').forEach(d => {
            d.classList.remove('selected');
          });
          dayDiv.classList.add('selected');

          selectedDate = `${selectedYear}-${(selectedMonth + 1).toString().padStart(2, '0')}-${dayNum.toString().padStart(2, '0')}`;
          selectedDateHeading.textContent = `${monthNames[selectedMonth]} ${dayNum}, ${selectedYear}`;
          
          // Reset time selection when date changes
          document.querySelectorAll('.select-time').forEach(btn => {
            btn.classList.remove('selected');
          });
          selectedTime = '';
          document.getElementById('nextButton').disabled = true;
        });

        calendarDays.appendChild(dayDiv);
      }
    }
    
    renderCalendar();

    let selectedDate = '<?php echo !empty($calendarData['appointment_date']) ? $calendarData['appointment_date'] : ''; ?>';
    let selectedTime = '<?php echo !empty($calendarData['appointment_time']) ? $calendarData['appointment_time'] : ''; ?>';

    // Initialize with pre-selected service
    document.addEventListener('DOMContentLoaded', function() {
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
                if (parseInt(dayElement.textContent) === day) {
                    dayElement.click();
                }
            });
        }, 100);
        <?php endif; ?>
        
        // If time is pre-selected
        <?php if (!empty($calendarData['appointment_time'])): ?>
        const timeButtons = document.querySelectorAll('.select-time');
        timeButtons.forEach(button => {
            if (button.dataset.time === selectedTime) {
                button.click();
            }
        });
        <?php endif; ?>
    });

    // Add time slot selection handlers
    document.querySelectorAll('.select-time').forEach(button => {
      button.addEventListener('click', (e) => {
        if (!selectedDate) {
          alert('Please select a date first');
          return;
        }

        document.querySelectorAll('.select-time').forEach(btn => {
          btn.classList.remove('selected');
        });
        e.target.classList.add('selected');
        selectedTime = e.target.dataset.time;
        document.getElementById('nextButton').disabled = false;
      });
    });

    function saveDateTime() {
      if (!selectedDate || !selectedTime) {
        alert('Please select both date and time');
        return;
      }

      const serviceId = document.querySelector('input[name="service_id"]:checked');
      if (!serviceId) {
        alert('Please select a service type');
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
          window.location.href = 'Appointment-successful.php';
        } else {
          alert('Error: ' + (data.message || 'Failed to save appointment details'));
          document.getElementById('nextButton').disabled = false;
          document.getElementById('nextButton').textContent = 'Next';
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please try again.');
        document.getElementById('nextButton').disabled = false;
        document.getElementById('nextButton').textContent = 'Next';
      });
    }
  </script>
</body>
</html>
