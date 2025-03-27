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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="css/destination-form.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
</head>
<body>
    <div class="destination-form-container">
        <form id="enquiryForm" method="POST" action="process_form.php" class="destination-form">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <?php if ($resort_info): ?>
                <input type="hidden" name="destination" value="<?php echo htmlspecialchars($resort_info['destination_name']); ?>">
                <input type="hidden" name="resort" value="<?php echo htmlspecialchars($resort_info['resort_name']); ?>">
            <?php else: ?>
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
                    <label for="country">Country</label>
                    <input type="text" id="country" name="country">
                </div>

                <div class="destination-form-field full-width">
                    <label>Do you have a passport? *</label>
                    <div class="custom-control"></div>
                        <input type="radio" id="passportYes" name="hasPassport" value="yes" required>
                        <label for="passportYes">Yes</label>
                    </div>
                    <div class="custom-control">
                        <input type="radio" id="passportNo" name="hasPassport" value="no">
                        <label for="passportNo">No</label>
                    </div>
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
    <script src="js/form-handler.js"></script>
</body>
</html>
