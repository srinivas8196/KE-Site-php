<?php
require 'db.php';

// Function to get resort information based on the current page
function get_resort_info($page_name) {
    global $conn;
    $sql = "SELECT d.name AS destination_name, r.name AS resort_name
            FROM resorts r
            JOIN destinations d ON r.destination_id = d.id
            WHERE r.page_name = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $page_name);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows > 0) {
        return $result->fetch_assoc();
    }
    return null;
}

// Check if the form is included in a resort page
$current_page = basename($_SERVER['PHP_SELF']);
$resort_info = get_resort_info($current_page);

// Fetch destinations from the database
$destinations = [];
$sql = "SELECT id, name FROM destinations";
$result = $conn->query($sql);
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $destinations[$row['id']] = $row['name'];
    }
}

// Fetch active resorts for each destination
$active_resorts = [];
foreach ($destinations as $destination_id => $destination_name) {
    $sql = "SELECT id, name FROM resorts WHERE destination_id = $destination_id AND is_active = 1";
    $result = $conn->query($sql);
    if ($result->num_rows > 0) {
        while($row = $result->fetch_assoc()) {
            $active_resorts[$destination_id][] = $row['name'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Destination Form</title>
</head>
<body>
    <form action="process_form.php" method="post">
        <?php if ($resort_info): ?>
            <input type="hidden" name="destination" value="<?php echo $resort_info['destination_name']; ?>">
            <input type="hidden" name="resort" value="<?php echo $resort_info['resort_name']; ?>">
        <?php else: ?>
            <label for="destination">Select Destination:</label>
            <select name="destination" id="destination" onchange="updateResorts()">
                <option value="">Select a destination</option>
                <?php foreach ($destinations as $destination_id => $destination_name): ?>
                    <option value="<?php echo $destination_id; ?>"><?php echo $destination_name; ?></option>
                <?php endforeach; ?>
            </select>

            <label for="resort">Select Resort:</label>
            <select name="resort" id="resort">
                <option value="">Select a resort</option>
                <!-- Resorts will be populated dynamically based on the selected destination -->
            </select>
        <?php endif; ?>

        <!-- Other form fields -->
        <label for="firstName">First Name:</label>
        <input type="text" name="firstName" required>

        <label for="lastName">Last Name:</label>
        <input type="text" name="lastName" required>

        <label for="email">Email:</label>
        <input type="email" name="email" required>

        <label for="phoneNumber">Phone Number:</label>
        <input type="text" name="phoneNumber" required>

        <label for="dob">Date of Birth:</label>
        <input type="date" name="dob" required>

        <label for="country">Country:</label>
        <input type="text" name="country" required>

        <label for="passport">Passport Number:</label>
        <input type="text" name="passport">

        <button type="submit">Submit</button>
    </form>

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
</body>
</html>
