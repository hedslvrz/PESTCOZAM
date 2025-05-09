<?php 
session_start();
require_once "../database.php";
require_once "../PHP CODES/AppointmentSession.php";

if (!isset($_SESSION['user_id'])) {
    header("Location: Login.php"); // Redirect if not logged in
    exit();
}

// Modified access check - only check if appointment exists, not step access
if (!isset($_SESSION['appointment'])) {
    header("Location: Appointment-service.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['user_id'];

// Get service data from session
$serviceData = AppointmentSession::getData('service');
if (!$serviceData) {
    header("Location: Appointment-service.php");
    exit();
}

$service_id = $serviceData['service_id'];
$is_for_self = $serviceData['is_for_self'];

// Handle POST (JSON) to save data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        isset($data['region'], $data['province'], $data['city'], $data['barangay']) &&
        isset($data['latitude'], $data['longitude'])
    ) {
        // Save data to session only (no database updates here)
        AppointmentSession::saveStep('location', [
            'region' => $data['region'],
            'province' => $data['province'],
            'city' => $data['city'],
            'barangay' => $data['barangay'],
            'street_address' => $data['street_address'],
            'landmark' => $data['landmark'] ?? null,
            'latitude' => $data['latitude'],
            'longitude' => $data['longitude'],
            'property_type' => $data['property_type'] ?? 'residential',
            'establishment_name' => $data['establishment_name'] ?? null,
            'property_area' => $data['property_area'] ?? null,
            'pest_concern' => $data['pest_concern'] ?? null
        ]);
        
        echo json_encode([
            "success" => true,
            "message" => "Location details saved.",
            "is_for_self" => $is_for_self,
            "next_page" => $is_for_self == 0 ? "Appointment-info.php" : "Appointment-calendar.php"
        ]);
        exit();
    } else {
        echo json_encode(["success" => false, "message" => "Missing location details."]);
        exit();
    }
}

// Pre-populate form fields if location data exists
$locationData = AppointmentSession::getData('location', []);
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Book an Appointment</title>
  <link rel="stylesheet" href="../CSS CODES/Appointment-loc.css">
  <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
  
  <!-- Leaflet CSS -->
  <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" />
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
            <li class="user-profile">
                <div class="profile-dropdown">
                    <i class='bx bx-menu hamburger-icon'></i>
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
      <div class="progress-step active">
          <div class="circle">2</div>
          <div class="label">Location</div>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
          <div class="circle">3</div>
          <div class="label">Personal Info</div>
      </div>
      <div class="progress-line"></div>
      <div class="progress-step">
          <div class="circle">4</div>
          <div class="label">Schedule</div>
      </div>
  </div>

  <main>
    <div class="appointment-container">
      
      <!-- Top section with location form and map side by side -->
      <div class="top-section">
        <!-- LEFT COLUMN: LOCATION DETAILS -->
        <div class="location-details">
          <label>Location Details:</label>
          <select id="region" name="region">
              <option value="">Select Region</option>
          </select>
          <select id="province" name="province">
              <option value="">Select Province</option>
          </select>
          <select id="city" name="city">
              <option value="">Select City</option>
          </select>
          <select id="barangay" name="barangay">
              <option value="">Select Barangay</option>
              <!-- FULL LIST of barangays from citypopulation.de -->
              <option value="Arena Blanco">Arena Blanco</option>
              <option value="Ayala">Ayala</option>
              <option value="Baliwasan">Baliwasan</option>
              <option value="Baluno">Baluno</option>
              <option value="Boalan">Boalan</option>
              <option value="Bolong">Bolong</option>
              <option value="Buenavista">Buenavista</option>
              <option value="Bunguiao">Bunguiao</option>
              <option value="Busay">Busay</option>
              <option value="Cabaluay">Cabaluay</option>
              <option value="Cabatangan">Cabatangan</option>
              <option value="Calarian">Calarian</option>
              <option value="Camino Nuevo">Camino Nuevo</option>
              <option value="Campo Islam">Campo Islam</option>
              <option value="Canelar">Canelar</option>
              <option value="Capisan">Capisan</option>
              <option value="Cawit">Cawit</option>
              <option value="Culianan">Culianan</option>
              <option value="Curuan">Curuan</option>
              <option value="Dita">Dita</option>
              <option value="Divisoria">Divisoria</option>
              <option value="Dulian (Upper Pasonanca)">Dulian (Upper Pasonanca)</option>
              <option value="Guisao">Guisao</option>
              <option value="Guiwan">Guiwan</option>
              <option value="Kabasalan">Kabasalan</option>
              <option value="La Paz">La Paz</option>
              <option value="Labuan">Labuan</option>
              <option value="Lamisahan">Lamisahan</option>
              <option value="Landang Gua">Landang Gua</option>
              <option value="Landang Laum">Landang Laum</option>
              <option value="Lapakan">Lapakan</option>
              <option value="Latuan">Latuan</option>
              <option value="Licomo">Licomo</option>
              <option value="Limpapa">Limpapa</option>
              <option value="Lubigan">Lubigan</option>
              <option value="Lumbangan">Lumbangan</option>
              <option value="Lunzuran">Lunzuran</option>
              <option value="Maasin">Maasin</option>
              <option value="Malagutay">Malagutay</option>
              <option value="Mampang">Mampang</option>
              <option value="Manalipa">Manalipa</option>
              <option value="Mangusu">Mangusu</option>
              <option value="Mariki">Mariki</option>
              <option value="Mercedes">Mercedes</option>
              <option value="Muti">Muti</option>
              <option value="Pasonanca">Pasonanca</option>
              <option value="Pasobolong">Pasobolong</option>
              <option value="Pasilmanta">Pasilmanta</option>
              <option value="Patalon">Patalon</option>
              <option value="Pilar">Pilar</option>
              <option value="Quiniput">Quiniput</option>
              <option value="Recodo">Recodo</option>
              <option value="Rio Hondo">Rio Hondo</option>
              <option value="Sangali">Sangali</option>
              <option value="San Jose Cawa-Cawa">San Jose Cawa-Cawa</option>
              <option value="San Jose Gusu">San Jose Gusu</option>
              <option value="San Roque">San Roque</option>
              <option value="Santa Barbara">Santa Barbara</option>
              <option value="Santa Catalina">Santa Catalina</option>
              <option value="Santa Maria">Santa Maria</option>
              <option value="Sibulao">Sibulao</option>
              <option value="Sinubong">Sinubong</option>
              <option value="Tagasilay">Tagasilay</option>
              <option value="Talabaan">Talabaan</option>
              <option value="Talisayan">Talisayan</option>
              <option value="Talon-Talon">Talon-Talon</option>
              <option value="Taluksangay">Taluksangay</option>
              <option value="Taytay Manubo">Taytay Manubo</option>
              <option value="Tictapul">Tictapul</option>
              <option value="Tigbalabag">Tigbalabag</option>
              <option value="Tolosa">Tolosa</option>
              <option value="Tugbungan">Tugbungan</option>
              <option value="Tulungatung">Tulungatung</option>
              <option value="Tumaga">Tumaga</option>
              <option value="Victoria">Victoria</option>
              <option value="Zambowood">Zambowood</option>
              <option value="Zamboanga Port Area">Zamboanga Port Area</option>
              <option value="Zone I (Pob.)">Zone I (Pob.)</option>
              <option value="Zone II (Pob.)">Zone II (Pob.)</option>
              <option value="Zone III (Pob.)">Zone III (Pob.)</option>
              <option value="Zone IV (Pob.)">Zone IV (Pob.)</option>
          </select>
          
          <input type="text" id="street_address" class="specify-addr" 
                placeholder="Street Name & House/Building No.">
          
          <!-- Add landmark field -->
          <input type="text" id="landmark" class="specify-addr" 
                placeholder="Nearest Landmark (optional)">
          
          <!-- Hidden fields for lat/lng to save in DB -->
          <input type="hidden" id="latitude">
          <input type="hidden" id="longitude">
        </div> <!-- .location-details -->

        <!-- RIGHT COLUMN: MAP -->
        <div class="map-container">
          <p>Please click on the map to mark the exact location for your pest treatment.</p>
          <div id="leafletMap"></div>
        </div>
      </div> <!-- .top-section -->
      
      <!-- Property details section (below map and form) -->
      <div class="property-details-section">
        <!-- LEFT SIDE: Property Type and Area -->
        <div class="property-left-container">
          <!-- Property Type -->
          <div class="form-group property-type-section">
            <label class="field-label">Property Type:</label>
            <div class="property-type-options">
              <label class="radio-label">
                <input type="radio" name="property_type" value="residential" checked> Residential
              </label>
              <label class="radio-label">
                <input type="radio" name="property_type" value="establishment"> Establishment
              </label>
            </div>
            
            <!-- Conditional field for establishment name -->
            <div id="establishment-name-container" style="display: none;">
              <input type="text" id="establishment_name" class="form-input specify-addr" 
                    placeholder="Establishment Name">
            </div>
          </div>
          
          <!-- Property area -->
          <div class="form-group property-area-section">
            <label class="field-label">Property Area (optional):</label>
            <div class="area-input-container">
              <input type="text" id="property_area" class="form-input specify-addr" 
                    placeholder="Enter the total square meters of your property">
              <span class="area-unit">sq.m</span>
            </div>
            <small class="help-text">Please provide the approximate total area of your property in square meters (length × width)</small>
          </div>
        </div>
        
        <!-- RIGHT SIDE: Pest Concern -->
        <div class="pest-concern-container">
          <div class="form-group pest-concern-section">
            <label class="field-label">Pest Concern:</label>
            <textarea id="pest_concern" class="form-input pest-concern-textarea" 
                    placeholder="Please describe your pest concern related to the service you selected"></textarea>
          </div>
        </div>
      </div> <!-- .property-details-section -->

      <div class="navigation-buttons">
          <button onclick="window.location.href='Appointment-service.php'">Back</button>
          <button id="nextButton">Next</button>
      </div>
    </div> <!-- .appointment-container -->
  </main>

  <!-- FOOTER SECTION -->
  <footer class="footer-section">
    <div class="footer-container">
      <div class="footer-left">
        <div class="footer-brand">
          <img src="../Pictures/pest_logo.png" alt="Flower icon" class="flower-icon" />
          <h3 class="brand-name">PESTCOZAM</h3>
        </div>
        <p>
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

  <!-- Leaflet JS + Our Map Logic -->
  <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
  <script>
    // Navigation warning
    window.onbeforeunload = function() {
      // Check if user has entered any data
      if (document.getElementById('street_address').value || 
          document.getElementById('landmark').value || 
          document.getElementById('barangay').value !== "") {
        return "You haven't finished booking your appointment. Are you sure you want to leave?";
      }
    };

    // 1) Next Button - Save to Session
    document.getElementById("nextButton").addEventListener("click", function() {
      // Disable the navigation warning
      window.onbeforeunload = null;
      
      let formData = {
        region: document.getElementById("region").value,
        province: document.getElementById("province").value,
        city: document.getElementById("city").value,
        barangay: document.getElementById("barangay").value,
        street_address: document.getElementById("street_address").value,
        landmark: document.getElementById("landmark").value,
        latitude: document.getElementById("latitude").value,
        longitude: document.getElementById("longitude").value,
        property_type: document.querySelector('input[name="property_type"]:checked').value,
        establishment_name: document.getElementById("establishment_name").value,
        property_area: document.getElementById("property_area").value,
        pest_concern: document.getElementById("pest_concern").value
      };

      fetch("Appointment-loc.php", {  
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData)
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              window.location.href = data.is_for_self == 0 ? "Appointment-info.php" : "Appointment-calendar.php";
          } else {
              alert("Error: " + data.message);
          }
      })
      .catch(error => console.error("Error:", error));
    });

    // Prepopulate form fields if data exists in session
    window.addEventListener('DOMContentLoaded', function() {
      // Region - locked with Region IX
      const region = document.getElementById('region');
      region.innerHTML = `<option value="Region IX">Region IX</option>`;
      region.disabled = true;

      // Province - locked with Zamboanga Del Sur
      const province = document.getElementById('province');
      province.innerHTML = `<option value="Zamboanga Del Sur">Zamboanga Del Sur</option>`;
      province.disabled = true;

      // City - locked with Zamboanga City
      const city = document.getElementById('city');
      city.innerHTML = `<option value="Zamboanga City">Zamboanga City</option>`;
      city.disabled = true;
      
      <?php if (!empty($locationData)): ?>
      // Pre-populate from session
      if (document.getElementById('barangay')) {
        document.getElementById('barangay').value = "<?php echo addslashes($locationData['barangay'] ?? ''); ?>";
      }
      if (document.getElementById('street_address')) {
        document.getElementById('street_address').value = "<?php echo addslashes($locationData['street_address'] ?? ''); ?>";
      }
      if (document.getElementById('landmark')) { 
        document.getElementById('landmark').value = "<?php echo addslashes($locationData['landmark'] ?? ''); ?>";
      }
      if (document.getElementById('latitude')) {
        document.getElementById('latitude').value = "<?php echo addslashes($locationData['latitude'] ?? ''); ?>";
      }
      if (document.getElementById('longitude')) {
        document.getElementById('longitude').value = "<?php echo addslashes($locationData['longitude'] ?? ''); ?>";
      }

      // Prepopulate property details fields if they exist
      if ("<?php echo $locationData['property_type'] ?? ''; ?>" === "establishment") {
        document.querySelector('input[name="property_type"][value="establishment"]').checked = true;
        document.getElementById('establishment-name-container').style.display = 'block';
        document.getElementById('establishment_name').value = "<?php echo addslashes($locationData['establishment_name'] ?? ''); ?>";
      }
      document.getElementById('property_area').value = "<?php echo $locationData['property_area'] ?? ''; ?>";
      document.getElementById('pest_concern').value = "<?php echo addslashes($locationData['pest_concern'] ?? ''); ?>";

      // If we have coordinates, set the marker on the map after it's loaded
      const latitude = <?php echo !empty($locationData['latitude']) ? $locationData['latitude'] : 'null'; ?>;
      const longitude = <?php echo !empty($locationData['longitude']) ? $locationData['longitude'] : 'null'; ?>;
      
      if (latitude && longitude) {
        setTimeout(() => {
          if (mymap) {
            if (userMarker) {
              userMarker.setLatLng([latitude, longitude]);
            } else {
              userMarker = L.marker([latitude, longitude], { draggable: true }).addTo(mymap);
            }
            mymap.setView([latitude, longitude], 14);
          }
        }, 500); // Short delay to ensure map is loaded
      }
      <?php endif; ?>

      // Show/hide establishment name field based on property type selection
      const propertyTypeRadios = document.querySelectorAll('input[name="property_type"]');
      const establishmentContainer = document.getElementById('establishment-name-container');
      
      propertyTypeRadios.forEach(radio => {
        radio.addEventListener('change', function() {
          if (this.value === 'establishment') {
            establishmentContainer.style.display = 'block';
          } else {
            establishmentContainer.style.display = 'none';
          }
        });
      });

      // Clear appointment session when clicking on navigation links
      document.querySelectorAll('nav a:not(.btn-appointment)').forEach(link => {
        link.addEventListener('click', function(event) {
            // Don't show the native browser confirmation
            window.onbeforeunload = null;
            
            // Show our custom confirmation
            event.preventDefault();
            if (confirm("You haven't finished booking your appointment. Are you sure you want to leave?")) {
                // Clear the session via AJAX and then navigate
                fetch('../PHP CODES/clear_appointment_session.php', {
                    method: 'POST'
                })
                .then(() => {
                    window.location.href = this.href;
                });
            }
        });
      });
    });

    // 3) Initialize Leaflet Map (Coordinates for Zamboanga City)
    var mymap = L.map('leafletMap', {
      dragging: true,       // Enable dragging
      touchZoom: true,      // Enable touch zoom 
      scrollWheelZoom: true,  // Enable scroll wheel zoom
      doubleClickZoom: true,  // Enable double click zoom
      boxZoom: true,        // Enable box zoom
      keyboard: true,       // Enable keyboard navigation
      zoomControl: true     // Add zoom control buttons
    }).setView([6.9214, 122.0790], 13);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
      attribution: '© OpenStreetMap',
      maxZoom: 19
    }).addTo(mymap);

    var userMarker = null;

    // 4) Click on the map to place a marker + Reverse Geocode
    mymap.on('click', function(e) {
      var lat = e.latlng.lat;
      var lng = e.latlng.lng;

      // If marker exists, just move it
      if (userMarker) {
        userMarker.setLatLng(e.latlng);
      } else {
        userMarker = L.marker(e.latlng, { draggable: true }).addTo(mymap);
      }

      // Save coords in hidden fields
      document.getElementById('latitude').value = lat;
      document.getElementById('longitude').value = lng;

      // Reverse Geocode to get the address
      fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}`)
      .then(response => response.json())
      .then(data => {
        if (data && data.address) {
          // Combine possible address parts
          let addressParts = [];
          if (data.address.house_number) {
            addressParts.push(data.address.house_number);
          }
          if (data.address.road) {
            addressParts.push(data.address.road);
          }
          // You can also include suburb, city, etc. if you want
          // Fill street_address with a nice, joined address
          document.getElementById('street_address').value = addressParts.join(', ');
        }
      })
      .catch(err => console.log('Reverse geocode error:', err));
    });

    // 5) When barangay changes, auto-zoom to that barangay (Nominatim)
    document.getElementById('barangay').addEventListener('change', function() {
      let selectedBrgy = this.value;
      if (!selectedBrgy) return;

      let query = encodeURIComponent(selectedBrgy + ", Zamboanga City, Philippines");
      fetch(`https://nominatim.openstreetmap.org/search?format=json&q=${query}`)
      .then(resp => resp.json())
      .then(data => {
        if (data && data.length > 0) {
          let lat = parseFloat(data[0].lat);
          let lon = parseFloat(data[0].lon);
          // Zoom the map to this barangay
          mymap.setView([lat, lon], 14);
        }
      })
      .catch(err => console.log('Barangay geocode error:', err));
    });
  </script>
</body>
</html>
