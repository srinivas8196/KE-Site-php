<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require 'db.php';

// Add CSRF protection
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Get resort information based on the current page
$current_page = basename($_SERVER['PHP_SELF']);
$resort_info = null;

// Extract resort slug from the current page (e.g., "abc-resort.php" -> "abc-resort")
$resort_slug = str_replace('.php', '', $current_page);

// Fetch resort details using the slug
$stmt = $pdo->prepare("SELECT r.*, d.destination_name 
                       FROM resorts r
                       JOIN destinations d ON r.destination_id = d.id
                       WHERE r.resort_slug = ?");
$stmt->execute([$resort_slug]);
$resort_info = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$resort_info) {
    return; // Don't show the form if resort not found
}
?>

<div class="max-w-4xl mx-auto bg-white rounded-xl shadow-lg overflow-hidden my-8">
    <!-- Form Header -->
    <div class="bg-gradient-to-r from-blue-600 to-blue-800 px-8 py-6">
        <h2 class="text-2xl font-bold text-white mb-2">Enquire About <?php echo htmlspecialchars($resort_info['resort_name']); ?></h2>
        <p class="text-blue-100">Located in <?php echo htmlspecialchars($resort_info['destination_name']); ?></p>
    </div>

    <!-- Form Content -->
    <div class="p-8">
        <form id="resortEnquiryForm" action="process_resort_enquiry.php" method="POST" class="space-y-6">
            <!-- CSRF Token -->
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <input type="hidden" name="resort_id" value="<?php echo $resort_info['id']; ?>">
            <input type="hidden" name="destination_id" value="<?php echo $resort_info['destination_id']; ?>">
            <input type="hidden" name="resort_name" value="<?php echo htmlspecialchars($resort_info['resort_name']); ?>">
            <input type="hidden" name="destination_name" value="<?php echo htmlspecialchars($resort_info['destination_name']); ?>">
            <input type="hidden" name="resort_code" value="<?php echo htmlspecialchars($resort_info['resort_code']); ?>">

            <!-- Personal Information Section -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Personal Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="first_name" class="block text-sm font-medium text-gray-700 mb-1">First Name</label>
                        <input type="text" id="first_name" name="first_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Enter your first name">
                    </div>
                    <div>
                        <label for="last_name" class="block text-sm font-medium text-gray-700 mb-1">Last Name</label>
                        <input type="text" id="last_name" name="last_name" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Enter your last name">
                    </div>
                </div>
            </div>

            <!-- Contact Information Section -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Contact Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 mb-1">Email Address</label>
                        <input type="email" id="email" name="email" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Enter your email address">
                    </div>
                    <div>
                        <label for="phone" class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                        <input type="tel" id="phone" name="phone" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors"
                               placeholder="Enter your phone number">
                    </div>
                </div>
            </div>

            <!-- Additional Information Section -->
            <div class="bg-gray-50 p-6 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-800 mb-4">Additional Information</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <label for="date_of_birth" class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" id="date_of_birth" name="date_of_birth" required
                               class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                        <p class="mt-1 text-sm text-gray-500">Must be 27 years or older</p>
                    </div>
                    <div>
                        <label for="has_passport" class="block text-sm font-medium text-gray-700 mb-1">Passport Status</label>
                        <select id="has_passport" name="has_passport" required
                                class="w-full px-4 py-2 border border-gray-300 rounded-md focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-colors">
                            <option value="">Select an option</option>
                            <option value="yes">Yes, I have a valid passport</option>
                            <option value="no">No, I need to apply for one</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="flex justify-center">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-semibold py-3 px-8 rounded-md transition-colors duration-300 transform hover:scale-105 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    Submit Enquiry
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Phone Input Styles -->
<style>
    .iti {
        width: 100%;
        display: block;
    }
    .iti__flag {
        background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags.png");
    }
    @media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) {
        .iti__flag {
            background-image: url("https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/img/flags@2x.png");
        }
    }
</style>

<!-- Scripts -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/intlTelInput.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Initialize phone input
    const phoneInput = document.getElementById('phone');
    const iti = window.intlTelInput(phoneInput, {
        utilsScript: "https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js",
        preferredCountries: ["IN", "GB", "US", "AU"],
        separateDialCode: true,
        formatOnDisplay: true
    });

    // Create hidden input for full phone number
    const hiddenInput = document.createElement('input');
    hiddenInput.type = 'hidden';
    hiddenInput.name = 'full_phone';
    phoneInput.parentNode.insertBefore(hiddenInput, phoneInput.nextSibling);

    // Form validation
    const form = document.getElementById('resortEnquiryForm');
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        // Validate phone number
        if (!iti.isValidNumber()) {
            alert('Please enter a valid phone number');
            return;
        }

        // Set the full phone number with country code
        hiddenInput.value = iti.getNumber();

        // Validate age (27+ years)
        const dob = new Date(document.getElementById('date_of_birth').value);
        const today = new Date();
        const age = today.getFullYear() - dob.getFullYear();
        const monthDiff = today.getMonth() - dob.getMonth();
        if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
            age--;
        }
        if (age < 27) {
            alert('You must be at least 27 years old to submit an enquiry');
            return;
        }

        // If all validations pass, submit the form
        form.submit();
    });

    // Add input validation styles
    const inputs = form.querySelectorAll('input, select');
    inputs.forEach(input => {
        input.addEventListener('invalid', function(e) {
            e.preventDefault();
            this.classList.add('border-red-500');
        });
        input.addEventListener('input', function() {
            this.classList.remove('border-red-500');
        });
    });
});
</script> 