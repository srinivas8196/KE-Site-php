<?php
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
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css" />
<style>
h2 { font-size: 1.5rem; margin-bottom: 1.5rem; }
.section-spacing { margin-bottom: 2rem; }
.banner .banner-title { z-index: 2; color: white; font-weight: bold; }
.banner .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1; }
</style>
<div class="banner position-relative section-spacing">
  <img src="<?php echo $resortFolder . '/' . ($resort['banner_image'] ?? ''); ?>" alt="<?php echo $resort['resort_name'] ?? ''; ?>" class="img-fluid w-100">
  <div class="overlay"></div>
  <div class="position-absolute top-50 start-50 translate-middle text-white banner-title">
    <h1 class="display-4"><?php echo $resort['banner_title'] ?? ''; ?></h1>
  </div>
</div>
<div class="container section-spacing">
  <div class="row">
    <div class="col-md-8">
      <h2><?php echo $resort['resort_name'] ?? ''; ?></h2>
      <p><?php echo $resort['resort_description'] ?? ''; ?></p>
      <hr class="my-4">
      <h3>Amenities</h3>
      <div class="row section-spacing">
      <?php if(is_array($amenities)): foreach($amenities as $a): ?>
         <div class="col-6 col-md-3 text-center mb-2">
            <img src="<?php echo $resortFolder . '/' . ($a['icon'] ?? ''); ?>" alt="<?php echo $a['name'] ?? ''; ?>" class="img-thumbnail" style="max-width:50px;">
            <p style="font-size:0.9rem;"><?php echo $a['name'] ?? ''; ?></p>
         </div>
      <?php endforeach; endif; ?>
      </div>
      <h3>Room Details</h3>
      <div class="row section-spacing">
      <?php if(is_array($room_details)): foreach($room_details as $r): ?>
         <div class="col-6 col-md-4 text-center mb-2">
            <img src="<?php echo $resortFolder . '/' . ($r['image'] ?? ''); ?>" alt="<?php echo $r['name'] ?? ''; ?>" class="img-fluid rounded" style="max-width:200px;">
            <p><?php echo $r['name'] ?? ''; ?></p>
         </div>
      <?php endforeach; endif; ?>
      </div>
      <h3>Gallery</h3>
      <div class="row section-spacing">
      <?php if(is_array($gallery)): foreach($gallery as $img): ?>
         <div class="col-6 col-md-4 mb-2">
           <a href="<?php echo $resortFolder . '/' . $img; ?>" data-fancybox="gallery">
             <img src="<?php echo $resortFolder . '/' . $img; ?>" alt="Gallery Image" class="img-fluid rounded" style="max-width:200px;">
           </a>
         </div>
      <?php endforeach; endif; ?>
      </div>
      <h3>Testimonials</h3>
      <div id="testimonialCarousel" class="carousel slide section-spacing" data-bs-ride="carousel" data-bs-interval="4000" data-bs-wrap="true" data-bs-pause="false">
        <div class="carousel-inner">
          <?php if(is_array($testimonials) && count($testimonials) > 0): ?>
            <?php $active = 'active'; foreach($testimonials as $t): ?>
              <div class="carousel-item <?php echo $active; ?>">
                <div class="d-flex flex-column align-items-center justify-content-center" style="min-height:180px; padding:1rem;">
                  <blockquote class="blockquote text-center">
                    <p class="mb-0" style="font-size:1rem;">"<?php echo $t['content'] ?? ''; ?>"</p>
                    <footer class="blockquote-footer mt-2" style="font-size:0.8rem;">
                      <?php echo $t['name'] ?? ''; ?>, <cite><?php echo $t['from'] ?? ''; ?></cite>
                    </footer>
                  </blockquote>
                </div>
              </div>
              <?php $active = ''; endforeach; ?>
          <?php else: ?>
            <div class="carousel-item active">
              <div class="d-flex flex-column align-items-center justify-content-center" style="min-height:180px; padding:1rem;">
                <p>No testimonials available.</p>
              </div>
            </div>
          <?php endif; ?>
        </div>
        <button class="carousel-control-prev" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="prev" style="width:30px; height:30px;">
          <span class="carousel-control-prev-icon" aria-hidden="true" style="width:30px; height:30px;"></span>
          <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#testimonialCarousel" data-bs-slide="next" style="width:30px; height:30px;">
          <span class="carousel-control-next-icon" aria-hidden="true" style="width:30px; height:30px;"></span>
          <span class="visually-hidden">Next</span>
        </button>
      </div>
    </div>
    <div class="col-md-4" style="position: sticky; top: 0;">
      <?php include 'destination-form.php'; ?>
    </div>
  </div>
</div>
<div style='clear:both;'></div>
<?php include 'kfooter.php'; ?>
