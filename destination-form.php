<?php
session_start();
require 'db.php';

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get resort information based on the current page
$current_page = basename($_SERVER['PHP_SELF']);
$resort_info = null;

// Convert page name to resort slug by removing .php extension
$resort_slug = str_replace('.php', '', $current_page);
$stmt = $pdo->prepare("SELECT d.destination_name, r.resort_name
                       FROM resorts r
                       JOIN destinations d ON r.destination_id = d.id
                       WHERE r.resort_slug = ?");
$stmt->execute([$resort_slug]);
$resort_info = $stmt->fetch();

// Fetch destinations from the database
$destinations = [];
$sql = "SELECT id, destination_name FROM destinations";
$result = $pdo->query($sql);
while($row = $result->fetch()) {
    $destinations[$row['id']] = $row['destination_name'];
}

// Fetch active resorts for each destination
$active_resorts = [];
foreach ($destinations as $destination_id => $destination_name) {
    $sql = "SELECT id, resort_name FROM resorts WHERE destination_id = ? AND is_active = 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$destination_id]);
    while($row = $stmt->fetch()) {
        $active_resorts[$destination_id][] = $row['resort_name'];
    }
}

// Get current resort and destination names from the included page
$current_resort_name = $current_resort_name ?? '';
$current_destination_name = $current_destination_name ?? '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/destination-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <title>Enquiry Form</title>
    <style>
        /* Basic CSS for the form */
        .destination-form-container {
            width: 80%;
            margin: 20px auto;
            padding: 20px;
            border: 1px solid #ddd;
            border-radius: 5px;
            background-color: #f9f9f9;
        }

        .destination-form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 15px;
        }

        .destination-form-field {
            margin-bottom: 15px;
        }

        .destination-form-field label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }

        .destination-form-field input[type="text"],
        .destination-form-field input[type="email"],
        .destination-form-field input[type="tel"],
        .destination-form-field input[type="date"],
        .destination-form-field select {
            width: 100%;
            padding: 8px;
            border: 1px solid #ccc;
            border-radius: 4px;
            box-sizing: border-box; /* Ensures padding doesn't affect width */
        }

        .destination-form-submit {
            text-align: center;
            margin-top: 20px;
        }

        .btn {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        .btn:hover {
            background-color: #0056b3;
        }

        /* Styles for destination and resort selection */
        .destination-form-space {
            margin-bottom: 20px;
            border: 1px solid #ccc;
            padding: 10px;
            border-radius: 5px;
        }

        .iti {
            width: 100%; /* Make the input fill the container */
        }
    </style>
</head>
<body>
    <div class="destination-form-container">
        <form id="enquiryForm" method="POST" action="submit_enquiry.php" class="destination-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <?php if ($resort_info): ?>
                <input type="hidden" name="destination" value="<?php echo htmlspecialchars($resort_info['destination_name']); ?>">
                <input type="hidden" name="resort" value="<?php echo htmlspecialchars($resort_info['resort_name']); ?>">
            <?php else: ?>
                <input type="hidden" name="destination" value="<?php echo htmlspecialchars($current_destination_name); ?>">
                <input type="hidden" name="resort" value="<?php echo htmlspecialchars($current_resort_name); ?>">
                <div class="destination-form-space">
                    <div class="destination-form-field">
                        <label for="destination">Select Destination</label>
                        <select name="destination" id="destination" onchange="updateResorts()">
                            <option value="">Select a destination</option>
                            <?php foreach ($destinations as $destination_id => $destination_name): ?>
                                <option value="<?php echo $destination_id; ?>"><?php echo $destination_name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="destination-form-field">
                        <label for="resort">Select Resort</label>
                        <select name="resort" id="resort">
                            <option value="">Select a resort</option>
                        </select>
                    </div>
                </div>
            <?php endif; ?>

            <div class="destination-form-grid">
                <div class="destination-form-field">
                    <label for="firstName">First Name *</label>
                    <input type="text" id="firstName" name="firstName" required>
                </div>

                <div class="destination-form-field">
                    <label for="lastName">Last Name *</label>
                    <input type="text" id="lastName" name="lastName" required>
                </div>

                <div class="destination-form-field">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required>
                </div>

                <div class="destination-form-field">
                    <label for="phone">Phone Number *</label>
                    <input type="tel" id="phone" name="phone" required>
                </div>

                <div class="destination-form-field">
                    <label for="dob">Date of Birth</label>
                    <input type="date" id="dob" name="dob">
                </div>

                <div class="destination-form-field">
                    <label for="hasPassport">Do you have a passport? *</label>
                    <select id="hasPassport" name="hasPassport" required>
                        <option value="">Select</option>
                        <option value="yes">Yes</option>
                        <option value="no">No</option>
                    </select>
                </div>
            </div>

            <div class="destination-form-submit">
                <button type="submit" class="btn btn-primary">Submit Enquiry</button>
            </div>
        </form>
    </div>

    <?php if (!$resort_info): ?>
        <script>
            // JavaScript to update the resorts based on the selected destination
            function updateResorts() {
                const destinationSelect = document.getElementById('destination');
                const resortSelect = document.getElementById('resort');
                const selectedDestinationId = destinationSelect.value;

                // Clear existing options
                resortSelect.innerHTML = '<option value="">Select a resort</option>';

                // Get the active resorts for the selected destination
                const activeResorts = <?php echo json_encode($active_resorts); ?>;
                if (activeResorts[selectedDestinationId]) {
                    activeResorts[selectedDestinationId].forEach(resortName => {
                        const option = document.createElement('option');
                        option.value = resortName;
                        option.textContent = resortName;
                        resortSelect.appendChild(option);
                    });
                }
            }
        </script>
    <?php endif; ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <script>
        const phoneInputField = document.querySelector("#phone");
        const phoneInput = window.intlTelInput(phoneInputField, {
            initialCountry: "auto",
            geoIpLookup: function(callback) {
                fetch('https://ipapi.co/json')
                    .then(function(res) { return res.json(); })
                    .then(function(data) { callback(data.country_code); })
                    .catch(function() { callback('us'); });
            },
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js", // just for formatting/placeholders etc
            onlyCountries: ["us", "gb", "ca"] // Restrict to these countries
        });
    </script>
    <script src="js/form-handler.js"></script>
</body>
</html>
