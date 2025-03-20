<!DOCTYPE html>
<html lang='en'>
<head>
  <meta charset='UTF-8'>
  <meta name='viewport' content='width=device-width, initial-scale=1.0'>
  <title>Karma Royal Palms</title>
  <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css'>
  <style>
    .section-spacing { margin-bottom: 2rem; }
    .banner { position: relative; }
    .banner img { width: 100%; }
    .banner .banner-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; }
    .row { display: flex; flex-wrap: wrap; }
    .col-md-8 { width: 66.6667%; padding: 0 15px; }
    .col-md-4 { width: 33.3333%; padding: 0 15px; }
    .container { max-width: 1140px; margin: 0 auto; padding: 0 15px; }
  </style>
</head>
<body>
  <?php define('RESORT_PAGE', true); ?>
  <?php include 'kheader.php'; ?>
  <div class='banner'>
    <img src='assets/resorts/karma-royal-palms/banner-teaplant.webp' alt='Karma Royal Palms'>
    <div class='banner-text'><h1>Goa, India</h1></div>
  </div>
  <div class='container section-spacing'>
    <div class='row'>
      <div class='col-md-8'>
        <h2>Karma Royal Palms</h2>
        <p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla pretium erat sed nisi laoreet consectetur. Aliquam lacinia blandit ultricies. Sed viverra erat nisl, vitae venenatis dui malesuada eget. Proin posuere venenatis erat in aliquet. Maecenas malesuada consequat magna id mollis. Maecenas in libero eu risus facilisis mattis. Nam vulputate odio quis dui commodo, molestie faucibus orci accumsan.</p>
        <div class='amenities section-spacing'>
          <h3>Amenities</h3>
          <div class='amenity-item'>
            <img src='assets/resorts/karma-royal-palms/amenities/amenities-hill.png' alt='hill'>
            <p>hill</p>
          </div>
          <div class='amenity-item'>
            <img src='assets/resorts/karma-royal-palms/amenities/amenities-res.png' alt='rest'>
            <p>rest</p>
          </div>
          <div class='amenity-item'>
            <img src='assets/resorts/karma-royal-palms/amenities/amenities-tea.png' alt='asassad'>
            <p>asassad</p>
          </div>
        </div>
        <div class='rooms section-spacing'>
          <h3>Room Details</h3>
          <div class='room-item'>
            <img src='assets/resorts/karma-royal-palms/rooms/rooms-WBU_KMartam_Assist.png' alt='Deluxe room'>
            <p>Deluxe room</p>
          </div>
          <div class='room-item'>
            <img src='assets/resorts/karma-royal-palms/rooms/rooms-WBU_KMartam_Save.png' alt='1 BHK'>
            <p>1 BHK</p>
          </div>
        </div>
        <div class='gallery section-spacing'>
          <h3>Gallery</h3>
          <div class='gallery-item'>
            <a href='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Assist.png' data-fancybox='gallery'>
              <img src='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Assist.png' alt='Gallery Image'>
            </a>
          </div>
          <div class='gallery-item'>
            <a href='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Save.png' data-fancybox='gallery'>
              <img src='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Save.png' alt='Gallery Image'>
            </a>
          </div>
          <div class='gallery-item'>
            <a href='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Trust.png' data-fancybox='gallery'>
              <img src='assets/resorts/karma-royal-palms/gallery/gallery-WBU_KMartam_Trust.png' alt='Gallery Image'>
            </a>
          </div>
          <div class='gallery-item'>
            <a href='assets/resorts/karma-royal-palms/gallery/gallery-WBU-KRP.png' data-fancybox='gallery'>
              <img src='assets/resorts/karma-royal-palms/gallery/gallery-WBU-KRP.png' alt='Gallery Image'>
            </a>
          </div>
        </div>
        <div class='testimonials section-spacing'>
          <h3>Testimonials</h3>
          <div class='testimonial-item'>
            <blockquote>
              <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla pretium erat sed nisi laoreet consectetur. Aliquam lacinia blandit ultricies. Sed viverra erat nisl, vitae venenatis dui malesuada eget. Proin posuere venenatis erat in aliquet. Maecenas malesuada consequat magna id mollis. Maecenas in libero eu risus facilisis mattis. Nam vulputate odio quis dui commodo, molestie faucibus orci accumsan."</p>
              <footer>Adam, <cite>Trip Advisor</cite></footer>
            </blockquote>
          </div>
          <div class='testimonial-item'>
            <blockquote>
              <p>"Lorem ipsum dolor sit amet, consectetur adipiscing elit. Nulla pretium erat sed nisi laoreet consectetur. Aliquam lacinia blandit ultricies. Sed viverra erat nisl, vitae venenatis dui malesuada eget. Proin posuere venenatis erat in aliquet. Maecenas malesuada consequat magna id mollis. Maecenas in libero eu risus facilisis mattis. Nam vulputate odio quis dui commodo, molestie faucibus orci accumsan."</p>
              <footer>John, <cite>Google</cite></footer>
            </blockquote>
          </div>
        </div>
      </div>
      <div class='col-md-4' style='position: sticky; top: 0;'>
        <?php include 'destination-form.php'; ?>
      </div>
    </div>
  </div>
</body>
</html>
