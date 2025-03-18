<?php
require 'db.php';
if($_SERVER['REQUEST_METHOD'] == 'POST'){
    // Basic text fields
    $template_name = trim($_POST['template_name']);
    $nights = $_POST['nights']; // Dropdown: "3 Nights", "4 Nights", "7 Nights"
    $resort_for_template = trim($_POST['resort_for_template']); // Selected resort name (from dropdown)
    $button_label = $_POST['button_label']; // 'Book Now' or 'Enquire Now'
    
    // Dummy placeholders for campaign title/subtitle (will be replaced from campaign data)
    $campaign_title = "";
    $campaign_subtitle = "";
    
    // Process resort banner image
    $resort_banner_tmp = $_FILES['resort_banner']['tmp_name'];
    $resort_banner_name = $_FILES['resort_banner']['name'];
    
    // Process about image and content
    $about_content = trim($_POST['about_content']);
    $about_image_tmp = $_FILES['about_image']['tmp_name'];
    $about_image_name = $_FILES['about_image']['name'];
    
    $templateFolder = "assets/templates";
    if(!file_exists($templateFolder)){
        mkdir($templateFolder, 0777, true);
    }
    move_uploaded_file($resort_banner_tmp, "$templateFolder/$resort_banner_name");
    move_uploaded_file($about_image_tmp, "$templateFolder/$about_image_name");
    
    // Repeater: Amenities
    $amenities_arr = [];
    if(isset($_POST['amenity_title'])){
        foreach($_POST['amenity_title'] as $index => $title){
            $amenity_image = $_FILES['amenity_icon']['name'][$index];
            $amenity_tmp = $_FILES['amenity_icon']['tmp_name'][$index];
            move_uploaded_file($amenity_tmp, "$templateFolder/$amenity_image");
            $amenities_arr[] = [
                'title' => $title,
                'icon' => $amenity_image
            ];
        }
    }
    $amenities_json = json_encode($amenities_arr);
    
    // Repeater: Attractions
    $attractions_arr = [];
    if(isset($_POST['attraction_title'])){
        foreach($_POST['attraction_title'] as $index => $title){
            $attraction_content = $_POST['attraction_content'][$index];
            $attraction_image = $_FILES['attraction_image']['name'][$index];
            $attraction_tmp = $_FILES['attraction_image']['tmp_name'][$index];
            move_uploaded_file($attraction_tmp, "$templateFolder/$attraction_image");
            $attractions_arr[] = [
                'title' => $title,
                'content' => $attraction_content,
                'image' => $attraction_image
            ];
        }
    }
    $attractions_json = json_encode($attractions_arr);
    
    // Gallery images
    $gallery_arr = [];
    if(isset($_FILES['template_gallery'])){
        foreach($_FILES['template_gallery']['tmp_name'] as $index => $tmpName){
            $file = $_FILES['template_gallery']['name'][$index];
            move_uploaded_file($tmpName, "$templateFolder/$file");
            $gallery_arr[] = $file;
        }
    }
    $gallery_json = json_encode($gallery_arr);
    
    // Repeater: Testimonials
    $testimonials_arr = [];
    if(isset($_POST['testimonial_name'])){
        foreach($_POST['testimonial_name'] as $index => $name){
            $testimonial_source = $_POST['testimonial_source'][$index];
            $testimonial_content = $_POST['testimonial_content'][$index];
            $testimonials_arr[] = [
                'name' => $name,
                'source' => $testimonial_source,
                'content' => $testimonial_content
            ];
        }
    }
    $testimonials_json = json_encode($testimonials_arr);
    
    // Generate a random 4-character alphanumeric string to append to slug
    function randomString($length = 4) {
      $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
      $result = '';
      for ($i = 0; $i < $length; $i++) {
         $result .= $characters[rand(0, strlen($characters) - 1)];
      }
      return $result;
    }
    $slugBase = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($resort_for_template));
    $resort_slug = $slugBase . randomString();
    
    // Insert into marketing_templates table
    $stmt = $pdo->prepare("INSERT INTO marketing_templates (template_name, resort_for_template, nights, resort_banner, about_image, about_content, amenities, attractions, gallery, testimonials, button_label, campaign_title, campaign_subtitle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$template_name, $resort_for_template, $nights, $resort_banner_name, $about_image_name, $about_content, $amenities_json, $attractions_json, $gallery_json, $testimonials_json, $button_label, $campaign_title, $campaign_subtitle]);
    
    // Generate a marketing template landing page URL; this page is used by campaign manager when creating campaign
    $templatePage = $resort_slug . ".php";
    
    // Generate a live page with a unique layout. (This is an example layout for marketing.)
    $pageContent  = "<?php\n";
    $pageContent .= "require 'db.php';\n";
    $pageContent .= "\$stmt = \$pdo->prepare(\"SELECT * FROM marketing_templates WHERE id = ?\");\n";
    $pageContent .= "\$stmt->execute([\$pdo->lastInsertId()]);\n"; // Simplified retrieval
    $pageContent .= "\$templateData = \$stmt->fetch();\n";
    $pageContent .= "\$amenities = json_decode(\$templateData['amenities'], true);\n";
    $pageContent .= "\$attractions = json_decode(\$templateData['attractions'], true);\n";
    $pageContent .= "\$gallery = json_decode(\$templateData['gallery'], true);\n";
    $pageContent .= "\$testimonials = json_decode(\$templateData['testimonials'], true);\n";
    $pageContent .= "?>\n";
    $pageContent .= "<?php include 'kheader.php'; ?>\n";
    $pageContent .= "<div class='container section-spacing'>\n";
    // Leadgen Campaign Section (if applicable)
    $pageContent .= "  <div class='row section-spacing'>\n";
    $pageContent .= "    <div class='col-md-8'>\n";
    $pageContent .= "      <h2><?php echo \$templateData['template_name']; ?></h2>\n";
    $pageContent .= "      <p>Campaign Title & Subtitle will be populated from campaign data.</p>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "    <div class='col-md-4'>\n";
    $pageContent .= "      <form>\n";
    $pageContent .= "        <input type='text' placeholder='Your Name' class='form-control mb-2'>\n";
    $pageContent .= "        <input type='email' placeholder='Your Email' class='form-control mb-2'>\n";
    $pageContent .= "        <button type='submit' class='btn btn-primary'>Enquire Now</button>\n";
    $pageContent .= "      </form>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // About Resort Section with fade animations
    $pageContent .= "  <div class='row section-spacing'>\n";
    $pageContent .= "    <div class='col-md-6 animate__animated animate__fadeInLeft'>\n";
    $pageContent .= "      <h3>About Resort</h3>\n";
    $pageContent .= "      <p><?php echo \$templateData['about_content']; ?></p>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "    <div class='col-md-6 animate__animated animate__fadeInRight'>\n";
    $pageContent .= "      <img src='assets/templates/<?php echo \$templateData['about_image']; ?>' alt='About Image' class='img-fluid' style='box-shadow: 0 4px 8px rgba(0,0,0,0.2);'>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // Amenities Section (6 grid)
    $pageContent .= "  <div class='section-spacing'>\n";
    $pageContent .= "    <h3>Amenities</h3>\n";
    $pageContent .= "    <div class='row'>\n";
    $pageContent .= "      <?php if(is_array(\$amenities)): foreach(\$amenities as \$amenity): ?>\n";
    $pageContent .= "        <div class='col-6 col-md-2 text-center'>\n";
    $pageContent .= "          <img src='assets/templates/<?php echo \$amenity['icon']; ?>' alt='<?php echo \$amenity['title']; ?>' class='img-fluid' style='max-width:50px;'>\n";
    $pageContent .= "          <p style='font-size:12px;'><?php echo \$amenity['title']; ?></p>\n";
    $pageContent .= "        </div>\n";
    $pageContent .= "      <?php endforeach; endif; ?>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // Rooms Section (dummy layout)
    $pageContent .= "  <div class='section-spacing'>\n";
    $pageContent .= "    <h3>Rooms</h3>\n";
    $pageContent .= "    <div class='row'>\n";
    $pageContent .= "      <div class='col-md-4 text-center'>\n";
    $pageContent .= "        <img src='assets/templates/dummy_room.jpg' alt='Room' class='img-fluid' style='max-width:200px;'>\n";
    $pageContent .= "        <div class='row'>\n";
    $pageContent .= "          <div class='col-6'><p>Nights: <?php echo \$templateData['nights']; ?></p></div>\n";
    $pageContent .= "          <div class='col-6'><p><del>$200</del> $150</p></div>\n";
    $pageContent .= "        </div>\n";
    $pageContent .= "        <button class='btn btn-primary'><?php echo \$templateData['button_label']; ?></button>\n";
    $pageContent .= "      </div>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // Attractions Section (Card carousel)
    $pageContent .= "  <div class='section-spacing'>\n";
    $pageContent .= "    <h3>Nearby Attractions</h3>\n";
    $pageContent .= "    <div id='attractionCarousel' class='carousel slide' data-bs-ride='carousel'>\n";
    $pageContent .= "      <div class='carousel-inner'>\n";
    $pageContent .= "        <?php if(is_array(\$attractions) && count(\$attractions) > 0): ?>\n";
    $pageContent .= "          <?php \$active = 'active'; foreach(\$attractions as \$a): ?>\n";
    $pageContent .= "            <div class='carousel-item <?php echo \$active; ?>'>\n";
    $pageContent .= "              <div class='card text-center'>\n";
    $pageContent .= "                <img src='assets/templates/<?php echo \$a['image']; ?>' class='card-img-top' alt='<?php echo \$a['title']; ?>'>\n";
    $pageContent .= "                <div class='card-body'>\n";
    $pageContent .= "                  <h5 class='card-title'><?php echo \$a['title']; ?></h5>\n";
    $pageContent .= "                  <p class='card-text'><?php echo \$a['content']; ?></p>\n";
    $pageContent .= "                </div>\n";
    $pageContent .= "              </div>\n";
    $pageContent .= "            </div>\n";
    $pageContent .= "            <?php \$active=''; endforeach; ?>\n";
    $pageContent .= "        <?php else: ?>\n";
    $pageContent .= "          <div class='carousel-item active'>\n";
    $pageContent .= "            <div class='card text-center'>\n";
    $pageContent .= "              <div class='card-body'>\n";
    $pageContent .= "                <p>No attractions available.</p>\n";
    $pageContent .= "              </div>\n";
    $pageContent .= "            </div>\n";
    $pageContent .= "          </div>\n";
    $pageContent .= "        <?php endif; ?>\n";
    $pageContent .= "      </div>\n";
    $pageContent .= "      <button class='carousel-control-prev' type='button' data-bs-target='#attractionCarousel' data-bs-slide='prev'>\n";
    $pageContent .= "        <span class='carousel-control-prev-icon' aria-hidden='true'></span>\n";
    $pageContent .= "        <span class='visually-hidden'>Previous</span>\n";
    $pageContent .= "      </button>\n";
    $pageContent .= "      <button class='carousel-control-next' type='button' data-bs-target='#attractionCarousel' data-bs-slide='next'>\n";
    $pageContent .= "        <span class='carousel-control-next-icon' aria-hidden='true'></span>\n";
    $pageContent .= "        <span class='visually-hidden'>Next</span>\n";
    $pageContent .= "      </button>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // Gallery Section
    $pageContent .= "  <div class='section-spacing'>\n";
    $pageContent .= "    <h3>Gallery</h3>\n";
    $pageContent .= "    <div class='row'>\n";
    $pageContent .= "      <?php if(is_array(\$gallery)): foreach(\$gallery as \$img): ?>\n";
    $pageContent .= "        <div class='col-6 col-md-4 mb-2'>\n";
    $pageContent .= "          <a href='assets/templates/<?php echo \$img; ?>' data-fancybox='gallery'>\n";
    $pageContent .= "            <img src='assets/templates/<?php echo \$img; ?>' alt='Gallery Image' class='img-fluid rounded' style='max-width:200px;'>\n";
    $pageContent .= "          </a>\n";
    $pageContent .= "        </div>\n";
    $pageContent .= "      <?php endforeach; endif; ?>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    // Testimonials Section as Carousel
    $pageContent .= "  <div class='section-spacing'>\n";
    $pageContent .= "    <h3>Testimonials</h3>\n";
    $pageContent .= "    <div id='testimonialCarousel' class='carousel slide' data-bs-ride='carousel' data-bs-interval='4000' data-bs-wrap='true' data-bs-pause='false'>\n";
    $pageContent .= "      <div class='carousel-inner'>\n";
    $pageContent .= "        <?php if(is_array(\$testimonials) && count(\$testimonials) > 0): ?>\n";
    $pageContent .= "          <?php \$active = 'active'; foreach(\$testimonials as \$t): ?>\n";
    $pageContent .= "            <div class='carousel-item <?php echo \$active; ?>'>\n";
    $pageContent .= "              <blockquote class='blockquote text-center'>\n";
    $pageContent .= "                <p class='mb-0' style='font-size:1rem;'>\"<?php echo \$t['content']; ?>\"</p>\n";
    $pageContent .= "                <footer class='blockquote-footer mt-2' style='font-size:0.8rem;'><?php echo \$t['name']; ?>, <cite><?php echo \$t['from']; ?></cite></footer>\n";
    $pageContent .= "              </blockquote>\n";
    $pageContent .= "            </div>\n";
    $pageContent .= "            <?php \$active = ''; endforeach; ?>\n";
    $pageContent .= "        <?php else: ?>\n";
    $pageContent .= "          <div class='carousel-item active'>\n";
    $pageContent .= "            <p>No testimonials available.</p>\n";
    $pageContent .= "          </div>\n";
    $pageContent .= "        <?php endif; ?>\n";
    $pageContent .= "      </div>\n";
    $pageContent .= "      <button class='carousel-control-prev' type='button' data-bs-target='#testimonialCarousel' data-bs-slide='prev' style='width:30px; height:30px;'>\n";
    $pageContent .= "        <span class='carousel-control-prev-icon' aria-hidden='true' style='width:30px; height:30px;'></span>\n";
    $pageContent .= "        <span class='visually-hidden'>Previous</span>\n";
    $pageContent .= "      </button>\n";
    $pageContent .= "      <button class='carousel-control-next' type='button' data-bs-target='#testimonialCarousel' data-bs-slide='next' style='width:30px; height:30px;'>\n";
    $pageContent .= "        <span class='carousel-control-next-icon' aria-hidden='true' style='width:30px; height:30px;'></span>\n";
    $pageContent .= "        <span class='visually-hidden'>Next</span>\n";
    $pageContent .= "      </button>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    $pageContent .= "</div>\n";
    
    $pageContent .= "<?php include 'kfooter.php'; ?>\n";
    
    file_put_contents($resortPage, $pageContent);
    
    header("Location: $resortPage");
    exit();
}
?>
