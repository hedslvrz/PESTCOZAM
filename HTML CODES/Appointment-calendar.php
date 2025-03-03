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
  <header class="top-header">
    <div class="container">
        <div class="location">
            <span>• <strong>Zamboanga</strong> • <strong>Pagadian</strong> • <strong>Pasay</strong> • <strong>Davao</strong></span>
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
        <li><a href="../HTML CODES/Home_page.html">Home</a></li>
        <li><a href="../HTML CODES/About_us.html">About Us</a></li>
        <li><a href="../HTML CODES/Services.html" class="services">Services</a></li>
        <li><a href="../HTML CODES/Appointment-service.php" class="btn-appointment">Appointment</a></li>
        <li><a href="../HTML CODES/Login.php" class="btn-login"><i class='bx bx-log-in' ></i> Login</a></li>
        <li><a href="../HTML CODES/Signup.php" class="btn-signup"><i class='bx bx-user-plus' ></i> Sign Up</a></li>
      </ul>
    </nav>
  </header>


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
        <!-- This heading updates automatically in JS when a user clicks a day -->
        <h3 id="selected-date-heading">February 20, 2025</h3>
        <table>
          <thead>
            <tr>
              <th>Time</th>
              <th>Availability</th>
            </tr>
          </thead>
          <tbody>
            <tr>
              <td>07:00 AM - 09:00 AM</td>
              <td>Reserved</td>
            </tr>
            <tr>
              <td>09:00 AM - 11:00 AM</td>
              <td>Reserved</td>
            </tr>
            <tr>
              <td>11:00 AM - 01:00 PM</td>
              <td>Open</td>
            </tr>
            <tr>
              <td>01:00 PM - 03:00 PM</td>
              <td>Open</td>
            </tr>
            <tr>
              <td>03:00 PM - 05:00 PM</td>
              <td>Reserved</td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Navigation Buttons -->
      <div class="calendar-nav">
        <button class="back-btn" onclick="window.location.href='Appointment-otp.html'">Back</button>
        <button class="next-btn" onclick="window.location.href='Appointment-successful.html'">Next</button>
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

          selectedDateHeading.textContent = 
            `${monthNames[selectedMonth]} ${dayNum}, ${selectedYear}`;
        });

        calendarDays.appendChild(dayDiv);
      }
    }
    
    renderCalendar();
  </script>
</body>
</html>
