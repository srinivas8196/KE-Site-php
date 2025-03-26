<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $resort['resort_name']; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- Include your CSS frameworks and other head elements -->
</head>
<body>
    <?php include 'kheader.php'; ?>

    <!-- Banner Section -->
    <section class="banner-section" style="background-image: url('<?php echo $supabase->storage()->from('resort-assets')->getPublicUrl($resort['banner_image']); ?>')">
        <div class="container">
            <h1><?php echo $resort['banner_title']; ?></h1>
        </div>
    </section>

    <!-- Resort Description -->
    <section class="resort-description">
        <div class="container">
            <h2><?php echo $resort['resort_name']; ?></h2>
            <div class="description">
                <?php echo $resort['resort_description']; ?>
            </div>
        </div>
    </section>

    <!-- Amenities Section -->
    <section class="amenities">
        <div class="container">
            <h2>Amenities</h2>
            <div class="amenities-grid">
                <?php foreach(json_decode($resort['amenities'], true) as $amenity): ?>
                    <div class="amenity-item">
                        <img src="<?php echo $supabase->storage()->from('resort-assets')->getPublicUrl($amenity['icon']); ?>" 
                             alt="<?php echo $amenity['name']; ?>">
                        <span><?php echo $amenity['name']; ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Room Types Section -->
    <section class="rooms">
        <div class="container">
            <h2>Room Types</h2>
            <div class="rooms-grid">
                <?php foreach(json_decode($resort['room_details'], true) as $room): ?>
                    <div class="room-item">
                        <img src="<?php echo $supabase->storage()->from('resort-assets')->getPublicUrl($room['image']); ?>" 
                             alt="<?php echo $room['name']; ?>">
                        <h3><?php echo $room['name']; ?></h3>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Gallery Section -->
    <section class="gallery">
        <div class="container">
            <h2>Gallery</h2>
            <div class="gallery-grid">
                <?php foreach(json_decode($resort['gallery'], true) as $image): ?>
                    <div class="gallery-item">
                        <img src="<?php echo $supabase->storage()->from('resort-assets')->getPublicUrl($image); ?>" 
                             alt="Gallery Image">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Testimonials Section -->
    <section class="testimonials">
        <div class="container">
            <h2>Guest Testimonials</h2>
            <div class="testimonials-slider">
                <?php foreach(json_decode($resort['testimonials'], true) as $testimonial): ?>
                    <div class="testimonial-item">
                        <blockquote>
                            <?php echo $testimonial['content']; ?>
                        </blockquote>
                        <cite>
                            <?php echo $testimonial['name']; ?>
                            <span><?php echo $testimonial['from']; ?></span>
                        </cite>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Booking/Enquiry Section -->
    <section class="booking">
        <div class="container">
            <h2>Make a Reservation</h2>
            <div class="booking-form">
                <form action="process_booking.php" method="POST">
                    <input type="hidden" name="resort_id" value="<?php echo $resort['id']; ?>">
                    <!-- Add your booking form fields here -->
                </form>
            </div>
        </div>
    </section>

    <?php include 'kfooter.php'; ?>
</body>
</html>
