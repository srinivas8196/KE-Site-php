<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Destination Form</title>
  <!-- Intl-Tel-Input CSS -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css" />
  <style>
    /* Container styles */
    .form-container {
      font-family: Arial, sans-serif;
      background-color: #f4f4f4;
      padding: 10px;
      position: sticky;
      top: 0;
      z-index: 1000;
      /* Ensure full viewport height if needed */
      /* height: 100vh; */
    }
    /* Use CSS Grid for two columns */
    .form-container form {
      background: #fff;
      padding: 15px;
      border-radius: 8px;
      box-shadow: 0 0 10px rgba(0,0,0,0.1);
      display: grid;
      grid-template-columns: repeat(2, 1fr);
      gap: 10px;
      width: 100%;
      max-width: 600px;
      box-sizing: border-box;
    }
    /* Full-width groups span both columns */
    .form-container .form-group.full-width {
      grid-column: 1 / -1;
    }
    /* Form group styling */
    .form-container .form-group {
      display: flex;
      flex-direction: column;
    }
    .form-container .form-group label {
      margin-bottom: 3px;
      font-size: 14px;
      font-weight: normal;
    }
    .form-container .form-group input,
    .form-container .form-group select,
    .form-container .form-group textarea {
      width: 100%;
      padding: 6px;
      border: 1px solid #ccc;
      border-radius: 4px;
      font-size: 14px;
      box-sizing: border-box;
    }
    /* Ensure phone input has extra left padding so text doesn't overlap flag */
    #phone-number {
      padding-left: 50px; /* Adjust as needed based on flag size */
    }
    /* Adjust intl-tel-input flag spacing */
    .iti__flag-container {
      margin-right: 8px !important;
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
      font-size: 14px;
      margin-top: 10px;
    }
    .form-container button:hover {
      background-color: #0056b3;
    }
  </style>
  <script>
    // Update resorts dropdown based on Preferred Destination selection
    function updateResorts() {
      var destination = document.getElementById("holiday-destination").value;
      var resorts = document.getElementById("preferred-resort");
      var resortSelectDiv = document.getElementById("resortSelectDiv");
      var options = {
        "India": ["Karma Royal Haathi Mahal, Goa", "Karma Royal Palms, Goa", "Karma Royal MonteRio, Goa"],
        "Indonesia": ["Mentari Residences, Karma Kandara, Bali", "Jimbaran Bay Beach Resort, Bali"],
        "Thailand": ["Karma Royal Boat Lagoon, Phuket", "Karma Panalee, Koh Samui"]
      };
      resorts.innerHTML = "";
      if (options[destination]) {
        options[destination].forEach(function(resort) {
          var option = document.createElement("option");
          option.value = resort;
          option.text = resort;
          resorts.appendChild(option);
        });
        resortSelectDiv.classList.remove("hidden");
      } else {
        resortSelectDiv.classList.add("hidden");
      }
    }
    // Validate phone number length (7 to 11 digits)
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
      return true;
    }
  </script>
</head>
<body>
  <div class="form-container">
    <form id="destinationForm" onsubmit="return validateForm()" novalidate>
      <div class="form-group">
        <label for="first-name">First Name</label>
        <input type="text" id="first-name" name="first-name" required>
      </div>
      <div class="form-group">
        <label for="last-name">Last Name</label>
        <input type="text" id="last-name" name="last-name" required>
      </div>
      <div class="form-group">
        <label for="email">E-Mail</label>
        <input type="email" id="email" name="email" required>
      </div>
      <div class="form-group">
        <label for="phone-number">Phone Number</label>
        <input type="tel" id="phone-number" name="phone-number" required>
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
        <label for="nationality">Nationality</label>
        <select id="nationality" name="nationality" required>
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
      <div class="form-group">
        <label for="holiday-destination">Preferred Destinations</label>
        <select id="holiday-destination" name="holiday-destination" onchange="updateResorts()" required>
          <option value="" selected disabled>Select</option>
          <option value="Cambodia">Cambodia</option>
          <option value="Egypt">Egypt</option>
          <option value="Germany">Germany</option>
          <option value="Greece">Greece</option>
          <option value="Italy">Italy</option>
          <option value="Indonesia">Indonesia</option>
          <option value="India">India</option>
          <option value="Maldives">Maldives</option>
          <option value="Spain">Spain</option>
          <option value="Thailand">Thailand</option>
          <option value="United Kingdom">United Kingdom</option>
          <option value="Vietnam">Vietnam</option>
        </select>
      </div>
      <div class="form-group full-width hidden" id="resortSelectDiv">
        <label for="preferred-resort">Select Preferred Resort</label>
        <select id="preferred-resort" name="preferred-resort" required>
          <!-- Options populated based on selected destination -->
        </select>
      </div>
      <div class="form-group full-width">
        <input type="checkbox" id="terms1" name="terms1" required>
        <label for="terms1">Allow Karma Experience/Karma Group related brands to communicate with me via SMS/email/call during and after your submission on this promotional offer.</label>
      </div>
      <div class="form-group full-width">
        <input type="checkbox" id="terms2" name="terms2" required>
        <label for="terms2">If you are a registered DND subscriber, you agree that you have requested to be contacted about this contest/promotional offer.</label>
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
        fetch("https://ipinfo.io/json?token=YOUR_TOKEN_HERE")
          .then(resp => resp.json())
          .then(resp => success(resp.country))
          .catch(() => success("US"));
      }
    });

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
      return true;
    }
    function updateResorts() {
      var destination = document.getElementById("holiday-destination").value;
      var resorts = document.getElementById("preferred-resort");
      var resortSelectDiv = document.getElementById("resortSelectDiv");
      var options = {
        "India": ["Karma Royal Haathi Mahal, Goa", "Karma Royal Palms, Goa", "Karma Royal MonteRio, Goa"],
        "Indonesia": ["Mentari Residences, Karma Kandara, Bali", "Jimbaran Bay Beach Resort, Bali"],
        "Thailand": ["Karma Royal Boat Lagoon, Phuket", "Karma Panalee, Koh Samui"]
      };
      resorts.innerHTML = "";
      if (options[destination]) {
        options[destination].forEach(function(resort) {
          var option = document.createElement("option");
          option.value = resort;
          option.text = resort;
          resorts.appendChild(option);
        });
        resortSelectDiv.classList.remove("hidden");
      } else {
        resortSelectDiv.classList.add("hidden");
      }
    }
  </script>
</body>
</html>
