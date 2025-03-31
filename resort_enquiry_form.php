<?php
session_start();
if (empty($_GET['resort_id'])) {
    die("Error: No resort specified");
}

// Generate CSRF token
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Include database connection
$pdo = require 'db.php';

// Fetch resort info
$stmt = $pdo->prepare("SELECT r.id, r.resort_name, r.resort_code, d.destination_name, d.id as destination_id 
                      FROM resorts r 
                      JOIN destinations d ON r.destination_id = d.id 
                      WHERE r.id = ?");
$stmt->execute([$_GET['resort_id']]);
$resort = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resort) {
    die("Error: Resort not found");
}

include 'header.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Enquire About <?php echo htmlspecialchars($resort['resort_name']); ?></title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Phone input library -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/css/intlTelInput.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
    <style>
        .enquiry-form-container {
            background-color: #f9fafb;
            border-radius: 8px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .form-header {
            background-color: #3b82f6;
            color: white;
            border-radius: 8px 8px 0 0;
        }
        .iti {
            width: 100%;
        }
    </style>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto py-12 px-4">
        <div class="max-w-3xl mx-auto enquiry-form-container">
            <div class="form-header p-6">
                <h1 class="text-2xl font-bold">Enquire About <?php echo htmlspecialchars($resort['resort_name']); ?></h1>
                <p class="text-gray-200 mt-2">Fill out the form below to enquire about this resort</p>
            </div>
            
            <div class="p-6">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                        <?php 
                        echo $_SESSION['success_message'];
                        unset($_SESSION['success_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                    </div>
                <?php endif; ?>
                
                <form action="process_resort_enquiry.php" method="POST" id="enquiryForm">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                    <input type="hidden" name="resort_id" value="<?php echo $resort['id']; ?>">
                    <input type="hidden" name="resort_name" value="<?php echo htmlspecialchars($resort['resort_name']); ?>">
                    <input type="hidden" name="destination_name" value="<?php echo htmlspecialchars($resort['destination_name']); ?>">
                    <input type="hidden" name="resort_code" value="<?php echo htmlspecialchars($resort['resort_code']); ?>">
                    <input type="hidden" name="destination_id" value="<?php echo $resort['destination_id']; ?>">
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="firstName" class="block text-gray-700 font-medium mb-2">First Name *</label>
                            <input type="text" id="firstName" name="firstName" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label for="lastName" class="block text-gray-700 font-medium mb-2">Last Name *</label>
                            <input type="text" id="lastName" name="lastName" required
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label for="email" class="block text-gray-700 font-medium mb-2">Email Address *</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    
                    <div class="mb-6">
                        <label for="phone" class="block text-gray-700 font-medium mb-2">Phone Number *</label>
                        <input type="tel" id="phone" name="phone" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        <input type="hidden" id="full_phone" name="full_phone">
                    </div>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
                        <div>
                            <label for="dob" class="block text-gray-700 font-medium mb-2">Date of Birth</label>
                            <input type="date" id="dob" name="dob"
                                   class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-2">Do you have a valid passport?</label>
                            <div class="flex items-center space-x-4 mt-2">
                                <label class="inline-flex items-center">
                                    <input type="radio" name="hasPassport" value="Yes" class="form-radio text-blue-500">
                                    <span class="ml-2">Yes</span>
                                </label>
                                <label class="inline-flex items-center">
                                    <input type="radio" name="hasPassport" value="No" class="form-radio text-blue-500">
                                    <span class="ml-2">No</span>
                                </label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mb-6">
                        <label class="block text-gray-700 font-medium mb-2">Resort Details</label>
                        <div class="bg-gray-100 p-4 rounded">
                            <p><strong>Resort:</strong> <?php echo htmlspecialchars($resort['resort_name']); ?></p>
                            <p><strong>Destination:</strong> <?php echo htmlspecialchars($resort['destination_name']); ?></p>
                            <p><strong>Resort Code:</strong> <?php echo htmlspecialchars($resort['resort_code']); ?></p>
                        </div>
                    </div>
                    
                    <div class="flex justify-end">
                        <button type="submit" class="px-6 py-3 bg-blue-500 text-white font-medium rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                            Submit Enquiry
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script>
        // Initialize phone input
        const phoneInput = window.intlTelInput(document.getElementById('phone'), {
            utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
            initialCountry: "auto",
            geoIpLookup: function(success, failure) {
                // Default to 'in' (India) if geolocation fails
                success("in");
            }
        });
        
        // Before form submission, set the full phone number
        document.getElementById('enquiryForm').addEventListener('submit', function(e) {
            const fullNumber = phoneInput.getNumber();
            document.getElementById('full_phone').value = fullNumber;
            
            // Basic form validation
            if (!phoneInput.isValidNumber()) {
                e.preventDefault();
                alert('Please enter a valid phone number.');
                return false;
            }
        });
    </script>
</body>
</html>
<?php include 'footer.php'; ?> 