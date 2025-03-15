<?php 
session_start();
require_once "../database.php"; 

if (!isset($_SESSION['appointment'])) {
    header("Location: Appointment-service.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$user_id = $_SESSION['appointment']['user_id'];
$service_id = $_SESSION['appointment']['service_id'];

// Handle POST (JSON) to save data
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);

    if (
        isset($data['region'], $data['province'], $data['city'], $data['barangay']) &&
        isset($data['latitude'], $data['longitude'])
    ) {
        $query = "UPDATE appointments SET 
                  region = :region, 
                  province = :province, 
                  city = :city, 
                  barangay = :barangay, 
                  street_address = :street_address,
                  latitude = :latitude,
                  longitude = :longitude
                  WHERE user_id = :user_id AND service_id = :service_id";

        $stmt = $db->prepare($query);
        $result = $stmt->execute([
            ':region' => $data['region'],
            ':province' => $data['province'],
            ':city' => $data['city'],
            ':barangay' => $data['barangay'],
            ':street_address' => $data['street_address'],
            ':latitude' => $data['latitude'],
            ':longitude' => $data['longitude'],
            ':user_id' => $user_id,
            ':service_id' => $service_id
        ]);

        echo json_encode([
            "success" => true,
            "message" => "Location details saved.",
            "is_for_self" => $_SESSION['appointment']['is_for_self'],
            "next_page" => $_SESSION['appointment']['is_for_self'] == 0 ? 
                          "Appointment-info.php" : "Appointment-calendar.php"
        ]);
        exit();
    } else {
        echo json_encode(["success" => false, "message" => "Missing location details."]);
        exit();
    }
}
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

  <main>
    <div class="appointment-container">
      
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
        
        <!-- Hidden fields for lat/lng to save in DB -->
        <input type="hidden" id="latitude">
        <input type="hidden" id="longitude">

        <div class="navigation-buttons">
            <button onclick="window.location.href='Appointment-service.php'">Back</button>
            <button id="nextButton">Next</button>
        </div>
      </div> <!-- .location-details -->

      <!-- RIGHT COLUMN: MAP -->
      <div class="map-container">
        <p>Please click on the map to mark the exact location for your pest treatment.</p>
        <div id="leafletMap"></div>
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
    // 1) Next Button - Save to DB
    document.getElementById("nextButton").addEventListener("click", function() {
      let formData = {
        region: document.getElementById("region").value,
        province: document.getElementById("province").value,
        city: document.getElementById("city").value,
        barangay: document.getElementById("barangay").value,
        street_address: document.getElementById("street_address").value,
        latitude: document.getElementById("latitude").value,
        longitude: document.getElementById("longitude").value
      };

      fetch("Appointment-loc.php", {  
          method: "POST",
          headers: { "Content-Type": "application/json" },
          body: JSON.stringify(formData)
      })
      .then(response => response.json())
      .then(data => {
          if (data.success) {
              // Check session to decide where to go next
              <?php if ($_SESSION['appointment']['is_for_self'] == 0): ?>
                  window.location.href = "Appointment-info.php"; 
              <?php else: ?>
                  window.location.href = "Appointment-calendar.php"; 
              <?php endif; ?>
          } else {
              alert("Error: " + data.message);
          }
      })
      .catch(error => console.error("Error:", error));
    });

    // 2) Populate dropdowns with locked values
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
    });

    // 3) Initialize Leaflet Map (Coordinates for Zamboanga City)
    var mymap = L.map('leafletMap').setView([6.9214, 122.0790], 13);

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
