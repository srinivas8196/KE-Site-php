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
</div>
<div class="container resort-details-container section-padding">
  <div class="row">
    <div class="col-lg-8 resort-content-left">
      <h1 class="banner-title"><?php echo htmlspecialchars($resort['banner_title'] ?? ''); ?></h1>
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
.banner-title {
  font-size: 3rem;
  font-weight: 700;
  margin-top: 1rem;
  margin-bottom: 1rem;
  color: #333;
}
@media (max-width: 768px) {
  .banner-title {
    font-size: 2rem;
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
.resort-details-container { padding: 40px 0; position: relative; }
.sticky-form-container { position: sticky; top: 100px; margin-bottom: 20px; z-index: 100; }
.resort-form-container { background: #fff; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
.resort-content-left { padding-right: 30px; }
@media (max-width: 991px) { .sticky-form-container { position: relative; top: 0; margin-top: 30px; } .resort-content-left { padding-right: 15px; } }
.form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }
.form-group { margin-bottom: 15px; position: relative; }
.form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }
.form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }
.btn-submit { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }
.btn-submit:hover { background: #0056b3; }
.error-message { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; }
.error-message.show { display: block; }
</style>
<script src="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js"></script>
<script src="https://unpkg.com/swiper/swiper-bundle.min.js"></script>
<script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
<?php include 'kfooter.php'; ?>
<!-- Phone Input Initialization -->
<link rel="stylesheet" href="assets/int-tel-input/css/intlTelInput.css">
<script src="assets/int-tel-input/js/intlTelInput.js"></script>
<script>
window.addEventListener('load', function() {
    var phoneInput = document.querySelector('#phone');
    if (phoneInput) {
        var iti = window.intlTelInput(phoneInput, {
            utilsScript: 'assets/int-tel-input/js/utils.js',
            initialCountry: 'us',
            preferredCountries: ['in', 'ae', 'gb', 'us'],
            separateDialCode: true,
            dropdownContainer: document.body
        });
        // Store the instance for later use
        window.iti = iti;
    }
});
</script>
<style>
.iti { width: 100%; }
.iti__country-list { z-index: 999999; background-color: white; border: 1px solid #CCC; }
</style>
