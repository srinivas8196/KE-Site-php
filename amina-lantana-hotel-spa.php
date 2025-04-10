<?php
// Set session parameters BEFORE session_start
ini_set('session.cookie_httponly', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_secure', 0);

// Create sessions directory if it doesn't exist
if (!file_exists(dirname(__FILE__) . '/sessions')) {
    mkdir(dirname(__FILE__) . '/sessions', 0777, true);
}

// Set session save path BEFORE session_start
$sessionPath = dirname(__FILE__) . '/sessions';
session_save_path($sessionPath);

// Start session
session_start();

// Generate CSRF token if needed
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

require 'db.php';
$stmt = $pdo->prepare("SELECT * FROM resorts WHERE resort_slug = ?");
$stmt->execute(['amina-lantana-hotel-spa']);
$resort = $stmt->fetch();
if (!$resort) { echo 'Resort not found.'; exit(); }
if ($resort['is_active'] != 1) { header('Location: 404.php'); exit(); }
$destStmt = $pdo->prepare("SELECT * FROM destinations WHERE id = ?");
$destStmt->execute([$resort['destination_id']]);
$destination = $destStmt->fetch();
$amenities = json_decode($resort['amenities'] ?? '', true);
$room_details = json_decode($resort['room_details'] ?? '', true);
$gallery = json_decode($resort['gallery'] ?? '', true);
$testimonials = json_decode($resort['testimonials'] ?? '', true);
$resortFolder = 'assets/resorts/' . ($resort['resort_slug'] ?? '');
?>
<?php include 'kresort_header.php'; ?>
<link rel="stylesheet" href="css/resort-details.css" />
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
<link rel="stylesheet" href="https://unpkg.com/swiper/swiper-bundle.min.css" />
<link rel="stylesheet" href="assets/int-tel-input/css/intlTelInput.min.css">
<div class="resort-banner modern-banner">
<?php if (!empty($resort['banner_image'])): ?>
  <img src="<?php echo $resortFolder . '/' . htmlspecialchars($resort['banner_image']); ?>" alt="<?php echo htmlspecialchars($resort['resort_name']); ?> Banner" class="banner-image">
  <div class="banner-content animated-banner-content">
    <div class="container">
      <h1 class="banner-title animate-title"><?php echo htmlspecialchars($resort['banner_title'] ?? ''); ?></h1>
    </div>
  </div>
<?php endif; ?>
</div>
<div class="container resort-details-container section-padding">
  <div class="row">
    <div class="col-lg-8 resort-content-left">
      <h2 class="resort-name"><?php echo htmlspecialchars($resort['resort_name'] ?? ''); ?></h2>
      <p class="resort-description"><?php echo nl2br(htmlspecialchars($resort['resort_description'] ?? '')); ?></p>
<style>
.modern-banner {
  position: relative;
  margin-bottom: 0;
  overflow: hidden;
}
.modern-banner img {
  width: 100%;
  height: auto;
  display: block;
}
.banner-content {
  position: absolute;
  bottom: 30px;
  left: 0;
  width: 100%;
  padding: 25px 0;
  z-index: 10;
}
.animated-banner-content {
  animation: fadeInUp 1s ease-out;
}
@keyframes fadeInUp {
  from {
    opacity: 0;
    transform: translateY(30px);
  }
  to {
    opacity: 1;
    transform: translateY(0);
  }
}
.banner-title {
  font-size: 3.2rem;
  font-weight: 700;
  margin: 0;
  color: #fff;
  text-shadow: 2px 2px 8px rgba(0, 0, 0, 0.8);
  animation: slidein 1.5s ease-out;
}
@keyframes slidein {
  from {
    transform: translateX(-50px);
    opacity: 0;
  }
  to {
    transform: translateX(0);
    opacity: 1;
  }
}
@media (max-width: 768px) {
  .banner-title {
    font-size: 2.2rem;
  }
  .banner-content {
    bottom: 15px;
    padding: 15px 0;
  }
}
.resort-name {
  margin-top: 1rem;
  margin-bottom: 1rem;
}
</style>
<div class="resort-section amenities-section">
        <h3>Amenities</h3>
<div class="amenities-grid">
<?php if(is_array($amenities)): foreach($amenities as $a): ?>
<div class="amenity-item animate-icon">
<img src="<?php echo $resortFolder . '/' . htmlspecialchars($a['icon'] ?? ''); ?>" alt="<?php echo htmlspecialchars($a['name'] ?? ''); ?>">
<p><?php echo htmlspecialchars($a['name'] ?? ''); ?></p>
</div>
<?php endforeach; else: ?>
<p>No amenities listed.</p>
<?php endif; ?>
</div>
</div>
<div class="resort-section rooms-section mt-8 mb-12">
    <h3 class="text-2xl font-semibold mb-6">Room Details</h3>
    <div class="room-details-grid">
    <?php if(is_array($room_details)): foreach($room_details as $r): ?>
        <div class="room-item">
            <?php if(!empty($r['image'])): ?>
            <div class="room-image">
                <img src="<?php echo $resortFolder . '/' . htmlspecialchars($r['image']); ?>" 
                     alt="<?php echo htmlspecialchars($r['name']); ?>">
            </div>
            <?php endif; ?>
            <div class="room-info">
                <h4><?php echo htmlspecialchars($r['name']); ?></h4>
            </div>
        </div>
    <?php endforeach; else: ?>
        <p class="text-gray-500">No room details available.</p>
    <?php endif; ?>
    </div>
</div>
<style>
.room-details-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 25px; }
.room-item { background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1); transition: transform 0.3s; }
.room-item:hover { transform: translateY(-5px); box-shadow: 0 6px 12px rgba(0,0,0,0.15); }
.room-image { width: 100%; height: 300px; overflow: hidden; }
.room-image img { width: 100%; height: 100%; object-fit: cover; transition: transform 0.3s; }
.room-item:hover .room-image img { transform: scale(1.05); }
.room-info { padding: 16px; text-align: center; }
.room-info h4 { margin: 0; font-size: 18px; color: #333; font-weight: 600; }
@media (max-width: 991px) {
    .room-details-grid { grid-template-columns: repeat(2, 1fr); }
    .room-image { height: 250px; }
}
@media (max-width: 768px) {
    .room-details-grid { grid-template-columns: repeat(2, 1fr); gap: 15px; }
    .room-image { height: 200px; }
}
@media (max-width: 480px) {
    .room-details-grid { grid-template-columns: 1fr; }
    .room-image { height: 250px; }
}
</style>
<div class="resort-section gallery-section">
        <h3>Gallery</h3>
<?php
$gallery = json_decode($resort['gallery'] ?? '[]', true);
if(is_array($gallery) && count($gallery) > 0): ?>
<div class="gallery-grid">
<?php foreach($gallery as $img): ?>
<?php if(!empty($img)): ?>
<div class="gallery-item">
<a href="<?php echo $resortFolder . '/' . htmlspecialchars($img); ?>" data-fancybox="gallery" class="gallery-link" data-caption="<?php echo htmlspecialchars($resort['resort_name']); ?> Gallery Image">
<img src="<?php echo $resortFolder . '/' . htmlspecialchars($img); ?>" alt="Gallery Image" class="gallery-image">
<div class="gallery-overlay"><i class="fas fa-search-plus"></i></div>
</a>
</div>
<?php endif; ?>
<?php endforeach; ?>
</div>
<?php else: ?>
<p>No gallery images available.</p>
<?php endif; ?>
</div>
<style>
.gallery-section { margin-bottom: 30px; }
.gallery-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 8px; }
.gallery-item { position: relative; overflow: hidden; border-radius: 6px; height: 0; padding-bottom: 70%; transition: transform 0.3s; }
.gallery-item:hover { transform: scale(1.02); }
.gallery-link { display: block; height: 100%; width: 100%; position: absolute; top: 0; left: 0; }
.gallery-image { object-fit: cover; height: 100%; width: 100%; position: absolute; top: 0; left: 0; transition: all 0.3s; }
.gallery-overlay { position: absolute; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.4); opacity: 0; transition: opacity 0.3s; display: flex; align-items: center; justify-content: center; }
.gallery-overlay i { color: white; font-size: 24px; }
.gallery-item:hover .gallery-overlay { opacity: 1; }
.gallery-item:hover .gallery-image { filter: brightness(1.1); }
@media (max-width: 991px) { .gallery-grid { grid-template-columns: repeat(2, 1fr); } }
@media (max-width: 576px) { .gallery-grid { grid-template-columns: 1fr; } }
</style>
<div class="resort-section testimonials-section modern-testimonials">
        <h3>What Our Guests Say</h3>
<div class="swiper testimonial-carousel">
<div class="swiper-wrapper">
<?php if(is_array($testimonials) && count($testimonials) > 0): foreach($testimonials as $t): ?>
<div class="swiper-slide testimonial-item">
<blockquote class="testimonial-content">
<p class="testimonial-text">"<?php echo htmlspecialchars($t['content']); ?>"</p>
<footer class="testimonial-author">
<strong><?php echo htmlspecialchars($t['name']); ?></strong>
<?php if(!empty($t['from'])): ?>
<span>, <?php echo htmlspecialchars($t['from']); ?></span>
<?php endif; ?>
</footer>
</blockquote>
</div>
<?php endforeach; else: ?>
<p>No testimonials available at the moment.</p>
<?php endif; ?>
</div>
<div class="swiper-pagination testimonial-pagination"></div>
</div>
</div>
</div>
<div class="col-lg-4">
<div class="sticky-form-container">
<div class="resort-form-container">
<h3>Enquire Now</h3>
<?php if(isset($_SESSION['success_message'])): ?>
<div class="alert alert-success">
    <?php 
    echo htmlspecialchars($_SESSION['success_message']);
    unset($_SESSION['success_message']);
    ?>
</div>
<?php endif; ?>

<?php if(isset($_SESSION['error_message'])): ?>
<div class="alert alert-danger">
    <?php 
    echo htmlspecialchars($_SESSION['error_message']);
    unset($_SESSION['error_message']);
    ?>
</div>
<?php endif; ?>

<form id="resortEnquiryForm" method="POST" action="process_resort_enquiry.php">
<input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
<input type="hidden" name="resort_id" value="<?php echo htmlspecialchars($resort['id']); ?>">
<input type="hidden" name="resort_name" value="<?php echo htmlspecialchars($resort['resort_name']); ?>">
<input type="hidden" name="destination_name" value="<?php echo htmlspecialchars($destination['destination_name']); ?>">
<input type="hidden" name="resort_code" value="<?php echo htmlspecialchars($resort['resort_code']); ?>">
<input type="hidden" name="destination_id" value="<?php echo htmlspecialchars($destination['id']); ?>">
<input type="hidden" name="full_phone" id="full_phone">
<div class="form-grid">
<div class="form-group">
<label for="first_name">First Name *</label>
<input type="text" id="first_name" name="first_name" class="form-control" required>
</div>
<div class="form-group">
<label for="last_name">Last Name *</label>
<input type="text" id="last_name" name="last_name" class="form-control" required>
</div>
<div class="form-group email-field">
<label for="email">Email *</label>
<input type="email" id="email" name="email" class="form-control" required>
</div>
<div class="form-group phone-field">
<label for="phone">Phone Number *</label>
<input type="tel" id="phone" name="phone" class="form-control" required>
<div id="phone-error" class="error-message">Please enter a valid phone number</div>
</div>
<div class="form-group dob-field">
<label for="dob">Date of Birth * (Must be born in 1997 or earlier)</label>
<input type="date" id="dob" name="dob" class="form-control" required max="1997-12-31">
<div id="dob-error" class="error-message">You must be born in 1997 or earlier</div>
</div>
<div class="form-group passport-field">
<label for="has_passport">Do you have a passport? *</label>
<select id="has_passport" name="has_passport" class="form-control" required>
<option value="">Select an option</option>
<option value="yes">Yes</option>
<option value="no">No</option>
</select>
</div>
<div class="form-group consent-field">
<div class="checkbox-container">
<input type="checkbox" id="communication_consent" name="communication_consent" required>
<label for="communication_consent" class="checkbox-label">Allow Karma Experience/Karma Group related brands to communicate with me via SMS/Email/Call during and after my submission on this promotional offer. *</label>
</div>
</div>
<div class="form-group consent-field">
<div class="checkbox-container">
<input type="checkbox" id="dnd_consent" name="dnd_consent" required>
<label for="dnd_consent" class="checkbox-label">Should I be a registered DND subscriber, I agree that I have requested to be contacted about this contest/promotional offer. *</label>
</div>
</div>
<button type="submit" class="btn-submit">Submit Enquiry</button>
</form>
</div>
</div>
</div>
</div>
</div>
<script>
document.addEventListener('DOMContentLoaded', function() {
  new Swiper('.testimonial-carousel', {
    slidesPerView: 1,
    spaceBetween: 30,
    loop: true,
    autoplay: {
      delay: 5000,
      disableOnInteraction: false,
    },
    pagination: {
      el: '.testimonial-pagination',
      clickable: true,
    },
  });
});
</script>
<style>
.testimonial-carousel { padding: 20px 0; }
.testimonial-item { text-align: center; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.testimonial-content { font-style: italic; margin-bottom: 15px; }
.testimonial-text { font-size: 1.1em; line-height: 1.6; margin-bottom: 15px; }
.testimonial-author { font-size: 0.9em; color: #666; }
.testimonial-author strong { color: #333; }
.swiper-pagination { position: relative; margin-top: 20px; }
.swiper-pagination-bullet { width: 10px; height: 10px; background: #007bff; opacity: 0.5; }
.swiper-pagination-bullet-active { opacity: 1; }
</style>
<style>
.resort-details-container { padding: 30px 0; position: relative; }
.sticky-form-container { position: sticky; top: 80px; margin-bottom: 20px; z-index: 100; }
.resort-form-container { background: #fff; padding: 18px; border-radius: 8px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); }
.resort-form-container h3 { margin-top: 0; margin-bottom: 15px; font-size: 22px; font-weight: 600; }
.resort-content-left { padding-right: 35px; }
/* Improve form appearance */
.form-grid { display: grid; grid-template-columns: 1fr; gap: 12px; margin-bottom: 15px; }
.form-group { margin-bottom: 8px; position: relative; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; font-size: 13px; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; transition: border-color 0.2s; }
.form-control:focus { border-color: #007bff; outline: none; box-shadow: 0 0 0 2px rgba(0,123,255,0.15); }
.checkbox-container { display: flex; align-items: flex-start; margin-bottom: 5px; }
.checkbox-container input[type='checkbox'] { flex-shrink: 0; margin-top: 3px; margin-right: 8px; }
.checkbox-label { font-size: 12px; font-weight: normal; line-height: 1.3; color: #555; margin: 0; display: inline-block; }
.consent-field { margin-bottom: 10px; width: 100%; display: block; }
.btn-submit { background: #007bff; color: white; padding: 10px 15px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; font-weight: 500; width: 100%; margin-top: 8px; transition: background-color 0.2s; }
.btn-submit:hover { background: #0056b3; }
.error-message { color: #dc3545; font-size: 11px; margin-top: 2px; display: none; }
.error-message.show { display: block; }
/* Alert styling */
.alert { padding: 12px 15px; margin-bottom: 20px; border-radius: 4px; font-size: 14px; }
.alert-success { background-color: #d4edda; border: 1px solid #c3e6cb; color: #155724; }
.alert-danger { background-color: #f8d7da; border: 1px solid #f5c6cb; color: #721c24; }
/* Phone input styling */
.iti { width: 100%; }
.iti__flag-container { position: absolute; top: 0; bottom: 0; right: 0; padding: 1px; }
.iti__selected-flag { padding: 0 6px 0 8px; }
.iti__country-list { z-index: 999999; background-color: white; border: 1px solid #CCC; max-height: 200px; overflow-y: auto; }
.iti__flag { background-image: url('assets/int-tel-input/img/flags.png'); }
@media (-webkit-min-device-pixel-ratio: 2), (min-resolution: 192dpi) { .iti__flag { background-image: url('assets/int-tel-input/img/flags@2x.png'); } }
.phone-field { position: relative; }
/* Responsive adjustments */
@media (max-width: 991px) { 
    .resort-details-container { display: flex; flex-direction: column; }
    .resort-content-left { order: 1; width: 100%; margin-bottom: 30px; }
    .sticky-form-container { order: 0; position: relative; top: 0; margin-bottom: 30px; width: 100%; }
}
@media (min-width: 992px) { 
    .resort-details-container .row { display: flex; }
    .resort-content-left { flex: 0 0 66.666667%; max-width: 66.666667%; }
    .resort-details-container .col-lg-4 { flex: 0 0 33.333333%; max-width: 33.333333%; }
}
/* Form grid responsiveness for larger screens */
@media (min-width: 768px) {
    .form-grid { grid-template-columns: 1fr 1fr; }
    .form-group.email-field,
    .form-group.phone-field,
    .form-group.dob-field,
    .form-group.passport-field,
    .form-group.consent-field {
        grid-column: 1 / -1; /* Make these fields full width */
    }
}
</style>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</div>
</div>
<?php include 'kfooter.php'; ?>
<!-- Phone Input Initialization -->
<link rel="stylesheet" href="assets/int-tel-input/css/intlTelInput.css">
<script src="assets/int-tel-input/js/intlTelInput.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js"></script>
<script>
window.addEventListener('load', function() {
    var phoneInput = document.querySelector('#phone');
    var fullPhoneInput = document.querySelector('#full_phone');
    var form = document.querySelector('#resortEnquiryForm');
    
    if (phoneInput) {
        var iti = window.intlTelInput(phoneInput, {
            utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js',
            initialCountry: 'in',
            preferredCountries: ['in', 'ae', 'gb', 'us'],
            separateDialCode: true,
            dropdownContainer: document.body,
            formatOnDisplay: true,
            autoPlaceholder: 'aggressive',
            allowDropdown: true,
            nationalMode: true
        });
        
        // Update hidden full_phone field with international format before submit
        if (form) {
            form.addEventListener('submit', function(e) {
                if (fullPhoneInput) {
                    fullPhoneInput.value = iti.getNumber();
                }
                
                // Validate phone number
                if (!iti.isValidNumber()) {
                    var errorCode = iti.getValidationError();
                    var errorMsg = '';
                    // Error codes from utils.js
                    switch(errorCode) {
                        case 0: errorMsg = 'Invalid number'; break;
                        case 1: errorMsg = 'Invalid country code'; break;
                        case 2: errorMsg = 'Number too short'; break;
                        case 3: errorMsg = 'Number too long'; break;
                        case 4: errorMsg = 'Invalid number'; break;
                        default: errorMsg = 'Invalid phone number'; break;
                    }
                    document.getElementById('phone-error').textContent = errorMsg;
                    document.getElementById('phone-error').classList.add('show');
                    e.preventDefault();
                    return false;
                } else {
                    document.getElementById('phone-error').classList.remove('show');
                }
                
                // Validate date of birth (27+ years old)
                var dobInput = document.getElementById('dob');
                if (dobInput) {
                    var dob = new Date(dobInput.value);
                    var today = new Date();
                    var age = today.getFullYear() - dob.getFullYear();
                    var monthDiff = today.getMonth() - dob.getMonth();
                    
                    if (monthDiff < 0 || (monthDiff === 0 && today.getDate() < dob.getDate())) {
                        age--;
                    }
                    
                    if (age < 27) {
                        e.preventDefault();
                        document.getElementById('dob-error').classList.add('show');
                        return false;
                    }
                }
                
                // Validate consent checkboxes
                var communicationConsent = document.getElementById('communication_consent');
                var dndConsent = document.getElementById('dnd_consent');
                if (!communicationConsent.checked || !dndConsent.checked) {
                    e.preventDefault();
                    alert('Please agree to the consent terms to proceed.');
                    return false;
                }
            });
        }
    }
});
</script>
<style>
.iti { width: 100%; }
.iti__country-list { z-index: 999999; background-color: white; border: 1px solid #CCC; }
</style>
