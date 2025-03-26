<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Destination Form</title>
  <!-- Intl-Tel-Input CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
  <style>
    .form-container {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      padding: 10px;
      position: sticky;
      top: 0;
      z-index: 1000;
    }
    .form-container form {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 10px;
      max-width: 600px;
      margin: 0 auto;
      box-sizing: border-box;
    }
    .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-group label {
      margin-bottom: 3px;
      font-size: 13px;
      font-weight: normal;
    }
    .form-group input,
    .form-group select,
    .form-group textarea {
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 13px;
      width: 100%;
      box-sizing: border-box;
    }
    /* Full width: span both columns */
    .full-width {
      grid-column: 1 / -1;
    }
    /* Hide element */
    .hidden {
      display: none;
    }
    /* Ensure phone number input has extra left padding so text does not overlap flag */
    #phone-number {
      padding-left: 50px;
    }
    /* Adjust intl-tel-input flag spacing */
    .iti__flag-container {
      margin-right: 8px !important;
    }
    /* Accent color for checkboxes */
    input[type="checkbox"] {
      accent-color: #007BFF;
    }
    /* Reduce checkbox label font size */
    .form-container .form-group label[for="terms1"],
    .form-container .form-group label[for="terms2"] {
      font-size: 12px;
    }
    /* Button styling */
    .form-container button {
      grid-column: 1 / -1;
      background-color: #007BFF;
      color: #fff;
      padding: 8px 12px;
      border: none;
      border-radius: 4px;
      cursor: pointer;
      font-size: 13px;
      margin-top: 10px;
    }
    .form-container button:hover {
      background-color: #0056b3;
    }
  </style>
  <script>
    function updateResorts() {  
      var destination = document.getElementById("holiday-destination").value;
      var resorts = document.getElementById("preferred-resort");
      var resortSelectDiv = document.getElementById("resortSelectDiv");
      var options = {
                "India": ["Karma Royal Haathi Mahal, Goa", "Karma Royal Palms, Goa", "Karma Royal MonteRio, Goa", "Goa Villagio, South Goa (Partner Hotel)", "Karma Sitabani, Corbett National Park", "Karma Seven Lakes, Udaipur", "Karma Golden Camp, Jaisalmer", "Karma Haveli, Jaipur", "Karma Sunshine Village, Bengaluru", "Karma Martam Retreat, Gangtok", "Karma Utopia, Manali", "Karma Munnar, Munnar", "Whispering Palms, Kumarakom(Partner Hotel)", "Copper Castle, Munnar (Partner Hotel)"],
                "Indonesia": ["Mentari Residences, Karma Kandara, Bali", "Jimbaran Bay Beach Resort, Bali (Partner Hotel)", "Swiss-Belhotel Tuban, Bali (Partner Hotel)"],
                "Thailand": ["Karma Royal Boat Lagoon, Phuket", "Karma Panalee, Koh Samui", "Supicha Pool Access Hotel, Phuket (Partner Hotel)", "The Oceanic Sportel, Phuket (Partner Hotel)", "The Pago Design Hotel, Phuket (Partner Hotel)", "March Samui Resort, Koh Samui (Partner Hotel)"],
                "Vietnam": ["Amina Lantana, Hoi An (Partner Hotel)"],
                "Cambodia": ["Karma Bayon, Siem Reap"],
                "Egypt": ["Karma Karnak, Luxor"],
                "Spain": ["Karma La Herriza, Gaucín"],
                "Germany": ["Karma Bavaria, Schliersee"],
                "Italy": ["Karma Borgo di Colleoli, Tuscany"],
                "United Kingdom": ["Karma Lake of Menteith, Stirling", "Karma Salford Hall, The Vale of Evesham"],
                "Maldives": ["Karma Fushi, Maldives"],
                "Greece": ["Karma Minoan, Crete"]
            };
      resorts.innerHTML = "";
      if (options[destination]) {
        options[destination].forEach(function(resort) {
          var option = document.createElement("option");
          option.value = resort;
          option.text = resort;
          resorts.appendChild(option);
        });
        resortSelectDiv.style.display = "block";
      } else {
        resortSelectDiv.style.display = "none";
      }
    }
    function validatePhoneNumber() {
      var phone = iti.getNumber();
      var digits = phone.replace(/\D/g, "");
      return (digits.length >= 7 && digits.length <= 11);
    }
    function validateForm() {
      if (!validatePhoneNumber()) {
        alert("Phone number must be between 7 and 11 digits.");
        return false;
      }
      
      // Format phone number for LeadSquared
      var phoneInput = document.getElementById("phone-number");
      var phoneFormatLsq = document.getElementById("PhoneFormatLsq");
      phoneFormatLsq.value = iti.getNumber();
      
      return true;
    }
  </script>
</head>
<body>
  <div class="form-container">
    <form id="destinationForm" action="process_form.php" method="POST" onsubmit="return validateForm()" novalidate>
      <input type="hidden" id="resort" name="resort" value="<?php echo isset($resort['resort_name']) ? htmlspecialchars($resort['resort_name']) : (isset($_GET['resort']) ? htmlspecialchars($_GET['resort']) : ''); ?>">
      <input type="hidden" id="PhoneFormatLsq" name="PhoneFormatLsq" value="">
      <div class="form-group">
        <label for="firstName">First Name</label>
        <input type="text" id="firstName" name="firstName" required>
      </div>
      <div class="form-group">
        <label for="lastName">Last Name</label>
        <input type="text" id="lastName" name="lastName" required>
      </div>
      <div class="form-group">
        <label for="email">E-Mail</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="phone-number">Phone Number</label>
        <input type="tel" id="phone-number" name="phoneNumber" required>
      </div>
      <div class="form-group">
        <label for="dob">Date of Birth</label>
        <input type="date" id="dob" name="dob" required>
      </div>
      <div class="form-group">
        <label for="passport">Do you have a passport?</label>
        <select id="passport" name="passport" required>
          <option value="">Select</option>
          <option value="yes">Yes</option>
          <option value="no">No</option>
        </select>
      </div>
      <div class="form-group">
        <label for="nationality">Country</label>
        <select id="nationality" name="country" required>
          <option value="" selected disabled>Select</option>
          <option value="India">India</option>
          <option value="Australia">Australia</option>
          <option value="Indonesia">Indonesia</option>
          <option value="United Kingdom">United Kingdom</option>
          <option value="Spain">Spain</option>
          <option value="Egypt">Egypt</option>
          <option value="Germany">Germany</option>
          <option value="Others">Others</option>
        </select>
      </div>
      <?php
      // Check if we're on a resort page
      $is_resort_page = isset($resort) && !empty($resort);
      
      // If we're not on a resort page, show destination and resort selection
      if (!$is_resort_page):
      ?>
      <div class="form-group">
        <label for="holiday-destination">Preferred Destinations</label>
        <select id="holiday-destination" name="holiday-destination" onchange="updateResorts()" required>
          <option value="" selected disabled>Select</option>
          <?php
          // If we have access to the database, fetch active destinations
          if (isset($pdo)) {
            try {
              $stmt = $pdo->query("SELECT DISTINCT d.id, d.destination_name
                                  FROM destinations d
                                  JOIN resorts r ON d.id = r.destination_id
                                  WHERE r.is_active = 1
                                  ORDER BY d.destination_name");
              $destinations = $stmt->fetchAll();
              foreach ($destinations as $dest) {
                echo '<option value="' . htmlspecialchars($dest['destination_name']) . '">' . htmlspecialchars($dest['destination_name']) . '</option>';
              }
            } catch (PDOException $e) {
              // If there's an error, fall back to the static list
              $static_destinations = ["Cambodia", "Egypt", "Germany", "Greece", "Italy", "Indonesia", "India", "Maldives", "Spain", "Thailand", "United Kingdom", "Vietnam"];
              foreach ($static_destinations as $dest) {
                echo '<option value="' . htmlspecialchars($dest) . '">' . htmlspecialchars($dest) . '</option>';
              }
            }
          } else {
            // If $pdo is not available, use static list
            $static_destinations = ["Cambodia", "Egypt", "Germany", "Greece", "Italy", "Indonesia", "India", "Maldives", "Spain", "Thailand", "United Kingdom", "Vietnam"];
            foreach ($static_destinations as $dest) {
              echo '<option value="' . htmlspecialchars($dest) . '">' . htmlspecialchars($dest) . '</option>';
            }
          }
          ?>
        </select>
      </div>
      <div class="form-group full-width" id="resortSelectDiv" style="display: none;">
        <label for="preferred-resort">Select Preferred Resort</label>
        <select id="preferred-resort" name="preferred-resort" required>
          <!-- Options populated based on selected destination -->
        </select>
      </div>
      <?php endif; ?>
      <div class="form-group full-width">
        <input type="checkbox" id="terms1" name="terms1" required>
        <label for="terms1" style="font-size: 12px;">Allow Karma Experience/Karma Group related brands to communicate with me via SMS/email/call during and after your submission on this promotional offer.</label>
      </div>
      <div class="form-group full-width">
        <input type="checkbox" id="terms2" name="terms2" required>
        <label for="terms2" style="font-size: 12px;">If you are a registered DND subscriber, you agree that you have requested to be contacted about this contest/promotional offer.</label>
      </div>
      <button type="submit">Submit</button>
    </form>
  </div>

  <!-- Intl-Tel-Input JS -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
  <script>
    var phoneInput = document.querySelector("#phone-number");
    var iti = window.intlTelInput(phoneInput, {
      utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
      initialCountry: "auto",
      geoIpLookup: function(success, failure) {
          fetch("https://ipinfo.io/json?token=551c02d4d78d44")
            .then(resp => resp.json())
            .then(resp => success(resp.country))
            .catch(() => success("US"));
      }
    });
  </script>
</body>
</html>
