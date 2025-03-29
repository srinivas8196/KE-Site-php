<?php
session_start();
require 'db.php';
$stmt = $pdo->prepare("SELECT * FROM resorts WHERE resort_slug = ?");
$stmt->execute(['karma-royal-palms']);
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
<?php endif; ?>
  <div class="banner-content-bottom-left">
    <h1 class="banner-title"><?php echo htmlspecialchars($resort['banner_title'] ?? ''); ?></h1>
  </div>
</div>
<div class="container resort-details-container section-padding">
  <div class="row">
    <div class="col-lg-8 resort-content-left">
      <h2 class="resort-name"><?php echo htmlspecialchars($resort['resort_name'] ?? ''); ?></h2>
      <p class="resort-description"><?php echo nl2br(htmlspecialchars($resort['resort_description'] ?? '')); ?></p>
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
<div class="resort-section rooms-section">
        <h3>Room Details</h3>
<div class="room-details-grid">
<?php if(is_array($room_details)): foreach($room_details as $r): ?>
<div class="room-item room-hover-effect">
<div class="room-image-container">
<img src="<?php echo $resortFolder . '/' . htmlspecialchars($r['image'] ?? ''); ?>" alt="<?php echo htmlspecialchars($r['name'] ?? ''); ?>">
</div>
<div class="room-info">
<p><?php echo htmlspecialchars($r['name'] ?? ''); ?></p>
</div>
</div>
<?php endforeach; else: ?>
<p>No room details available.</p>
<?php endif; ?>
</div>
</div>
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
<div class="resort-section testimonials-section modern-testimonials">
        <h3>What Our Guests Say</h3>
<?php
$testimonials = json_decode($resort['testimonials'], true);
if(is_array($testimonials) && count($testimonials) > 0): ?>
<div class="swiper testimonial-carousel">
<div class="swiper-wrapper">
<?php foreach($testimonials as $t): ?>
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
<?php endforeach; ?>
</div>
<div class="swiper-pagination testimonial-pagination"></div>
</div>
<?php else: ?>
<p>No testimonials available at the moment.</p>
<?php endif; ?>
</div>
<style>
.testimonial-carousel { padding: 20px 0; }
.testimonial-item { text-align: center; padding: 20px; }
.testimonial-content { font-style: italic; margin-bottom: 15px; }
.testimonial-text { font-size: 1.1em; line-height: 1.6; margin-bottom: 15px; }
.testimonial-author { font-size: 0.9em; color: #666; }
.testimonial-author strong { color: #333; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
  if(document.querySelector('.testimonial-carousel')) {
    const testimonialCarousel = new Swiper('.testimonial-carousel', {
      loop: true,
      autoplay: {
        delay: 5000,
        disableOnInteraction: false,
        pauseOnMouseEnter: true
      },
      speed: 1000,
      effect: 'fade',
      fadeEffect: { crossFade: true },
      pagination: {
        el: '.testimonial-pagination',
        clickable: true
      }
    });
  }
});
</script>
</div>
<div class="col-lg-4">
<div class="sticky-form-container">
<div class="resort-form-container">
<form id="resortEnquiryForm" method="POST" action="process_resort_enquiry.php">
<input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
<input type="hidden" name="resort_id" value="<?php echo htmlspecialchars($resort['id']); ?>">
<input type="hidden" name="resort_name" value="<?php echo htmlspecialchars($resort['resort_name']); ?>">
<input type="hidden" name="destination_name" value="<?php echo htmlspecialchars($destination['destination_name']); ?>">
<input type="hidden" name="resort_code" value="<?php echo htmlspecialchars($resort['resort_code']); ?>">
<div class="form-grid">
<div class="form-group">
<label for="firstName">First Name *</label>
<input type="text" id="firstName" name="firstName" class="form-control" required>
</div>
<div class="form-group">
<label for="lastName">Last Name *</label>
<input type="text" id="lastName" name="lastName" class="form-control" required>
</div>
<div class="form-group">
<label for="email">Email *</label>
<input type="email" id="email" name="email" class="form-control" required>
</div>
<div class="form-group">
<label for="phone">Phone Number *</label>
<input type="tel" id="phone" name="phone" class="form-control" required>
<div id="phone-error" class="error-message">Please enter a valid phone number</div>
</div>
<div class="form-group">
<label for="dob">Date of Birth * (Must be 27 years or older)</label>
<input type="date" id="dob" name="dob" class="form-control" required>
<div id="dob-error" class="error-message">You must be at least 27 years old</div>
</div>
<div class="form-group">
<label for="hasPassport">Do you have a passport? *</label>
<select id="hasPassport" name="hasPassport" class="form-control" required>
<option value="">Select an option</option>
<option value="yes">Yes</option>
<option value="no">No</option>
</select>
</div>
</div>
<button type="submit" class="btn-submit">Submit Enquiry</button>
</form>
</div>
<style>
.resort-form-container { width: 100%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
.btn-submit { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
.btn-submit:hover { background: #0056b3; }
.error-message { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; }
.error-message.show { display: block; }
.iti { width: 100% !important; }
.iti__country-list { max-height: 200px !important; overflow-y: auto !important; width: 260px !important; position: absolute !important; z-index: 9999 !important; background-color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important; }
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Phone number initialization
    var phoneInput = document.querySelector('#phone');
    var iti = window.intlTelInput(phoneInput, {
        preferredCountries: ['in', 'ae', 'gb', 'us'],
        separateDialCode: true,
        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js'
    });
    // Create hidden input for full phone number
    var hiddenPhoneInput = document.createElement('input');
    hiddenPhoneInput.type = 'hidden';
    hiddenPhoneInput.name = 'full_phone';
    phoneInput.parentNode.insertBefore(hiddenPhoneInput, phoneInput.nextSibling);
    // Date of birth validation
    var dobInput = document.getElementById('dob');
    dobInput.max = new Date(new Date().setFullYear(new Date().getFullYear() - 27)).toISOString().split('T')[0];
    function validateDOB() {
        var dob = new Date(dobInput.value);
        var age = Math.floor((new Date() - dob) / (365.25 * 24 * 60 * 60 * 1000));
        var errorDiv = document.getElementById('dob-error');
        if (age < 27) {
            errorDiv.classList.add('show');
            return false;
        } else {
            errorDiv.classList.remove('show');
            return true;
        }
    }
    function validatePhoneNumber() {
        var errorDiv = document.getElementById('phone-error');
        if (!iti.isValidNumber()) {
            errorDiv.classList.add('show');
            return false;
        } else {
            errorDiv.classList.remove('show');
            hiddenPhoneInput.value = iti.getNumber();
            return true;
        }
    }
    // Form validation
    document.getElementById('resortEnquiryForm').addEventListener('submit', function(event) {
        var isPhoneValid = validatePhoneNumber();
        var isDOBValid = validateDOB();
        if (!isPhoneValid || !isDOBValid) {
            event.preventDefault();
        }
    });
    // Live validation
    phoneInput.addEventListener('input', validatePhoneNumber);
    phoneInput.addEventListener('countrychange', validatePhoneNumber);
    dobInput.addEventListener('change', validateDOB);
});
</script>
</div>
</div>
</div>
</div>
<?php include 'kfooter.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="assets/int-tel-input/js/intlTelInput.min.js"></script>
<script src="assets/int-tel-input/js/utils.js"></script>
<script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
<script>
Fancybox.bind('[data-fancybox="gallery"]', {
  carousel: { infinite: true },
  Toolbar: {
    display: ['slideshow', 'fullscreen', 'thumbs', 'close']
  },
  Thumbs: { autoStart: true },
  Slideshow: { autoStart: false, speed: 4000 }
});
const galleryCarousel = new Swiper('.gallery-carousel', {
loop: true,
slidesPerView: 1,
spaceBetween: 10,
pagination: { el: '.gallery-pagination', clickable: true },
navigation: { nextEl: '.gallery-button-next', prevEl: '.gallery-button-prev' },
breakpoints: { 640: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 30 } }
});
const phoneInputField = document.querySelector('#phone');
if (phoneInputField) {
const phoneInput = window.intlTelInput(phoneInputField, {
initialCountry: 'auto',
geoIpLookup: function(callback) {
fetch('https://ipapi.co/json')
.then(function(res) { return res.json(); })
.then(function(data) { callback(data.country_code); })
.catch(function() { callback('us'); });
},
utilsScript: 'assets/int-tel-input/js/utils.js'
});
// You might want to store the full number on form submit
const form = phoneInputField.closest('form');
if (form) {
form.addEventListener('submit', function() {
const fullNumber = phoneInput.getNumber();
// Add a hidden input to store the full number if needed
let hiddenInput = form.querySelector('input[name="full_phone"]');
if (!hiddenInput) {
hiddenInput = document.createElement('input');
hiddenInput.type = 'hidden';
hiddenInput.name = 'full_phone';
form.appendChild(hiddenInput);
}
hiddenInput.value = fullNumber;
});
}
}
</script>
