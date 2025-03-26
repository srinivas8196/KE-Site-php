<?php
require 'db.php';

// Fetch existing resort details if editing
$resort = null;
if (isset($_GET['resort_id']) && !empty($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination_id     = $_POST['destination_id'];
    $resort_name        = trim($_POST['resort_name']);
    $resort_description = trim($_POST['resort_description']);
    $banner_title       = trim($_POST['banner_title']);

    // Process is_active:
    // For new resorts, force active (1) by default.
    // For editing, set based on the checkbox.
    if (isset($_POST['resort_id'])) {
        $is_active = isset($_POST['is_active']) ? 1 : 0;
    } else {
        $is_active = 1;
    }

    // Use existing slug if editing; otherwise, generate a new one from the resort name.
    if (isset($_POST['resort_id']) && !empty($_POST['resort_id']) && !empty($resort['resort_slug'])) {
        $resort_slug = $resort['resort_slug'];
    } else {
        $resort_slug = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($resort_name));
        $resort_slug = preg_replace('/-+/', '-', $resort_slug); // remove duplicate dashes
    }
    
    // Define the folder path for storing assets inside "assets/resorts/"
    $resortFolderPath = "assets/resorts/$resort_slug";
    // Compute the landing page file name (for example, "abc.php")
    $resortPage = "$resort_slug.php";

    // Create directories if they don't exist
    if (!file_exists($resortFolderPath)) {
        mkdir($resortFolderPath, 0777, true);
        mkdir("$resortFolderPath/amenities", 0777, true);
        mkdir("$resortFolderPath/gallery", 0777, true);
        mkdir("$resortFolderPath/rooms", 0777, true);
    }

    // Handle banner image upload
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
        $banner_image = "banner-" . $_FILES['banner_image']['name'];
        move_uploaded_file($_FILES['banner_image']['tmp_name'], "$resortFolderPath/$banner_image");
    } else if (isset($resort['banner_image'])) {
        $banner_image = $resort['banner_image'];
    }

    // Process dynamic file uploads for amenities
    $amenities = [];
    if (isset($_FILES['amenities'])) {
        foreach ($_FILES['amenities']['tmp_name'] as $index => $tmpData) {
            if (isset($_FILES['amenities']['error'][$index]['icon']) && $_FILES['amenities']['error'][$index]['icon'] == UPLOAD_ERR_OK) {
                $file = $_FILES['amenities']['name'][$index]['icon'];
                $newFileName = "amenities-" . $file;
                move_uploaded_file($_FILES['amenities']['tmp_name'][$index]['icon'], "$resortFolderPath/amenities/$newFileName");
                $amenities[] = [
                    'name' => $_POST['amenities'][$index]['name'],
                    'icon' => "amenities/$newFileName"
                ];
            } else {
                // Retain existing icon if no new file is uploaded
                if (isset($_POST['amenities'][$index]['existing_icon'])) {
                    $amenities[] = [
                        'name' => $_POST['amenities'][$index]['name'],
                        'icon' => $_POST['amenities'][$index]['existing_icon']
                    ];
                }
            }
        }
    }

    // Process dynamic file uploads for rooms
    $rooms = [];
    if (isset($_FILES['rooms'])) {
        foreach ($_FILES['rooms']['tmp_name'] as $index => $tmpData) {
            if (isset($_FILES['rooms']['error'][$index]['image']) && $_FILES['rooms']['error'][$index]['image'] == UPLOAD_ERR_OK) {
                $file = $_FILES['rooms']['name'][$index]['image'];
                $newFileName = "rooms-" . $file;
                move_uploaded_file($_FILES['rooms']['tmp_name'][$index]['image'], "$resortFolderPath/rooms/$newFileName");
                $rooms[] = [
                    'name'  => $_POST['rooms'][$index]['name'],
                    'image' => "rooms/$newFileName"
                ];
            } else {
                // Retain existing room image if no new file is uploaded
                if (isset($_POST['rooms'][$index]['existing_image'])) {
                    $rooms[] = [
                        'name'  => $_POST['rooms'][$index]['name'],
                        'image' => $_POST['rooms'][$index]['existing_image']
                    ];
                }
            }
        }
    }

    // Process gallery images (multiple files)
    $galleryImages = [];
    if (isset($_FILES['gallery'])) {
        foreach ($_FILES['gallery']['tmp_name'] as $index => $tmpName) {
            if ($_FILES['gallery']['error'][$index] == UPLOAD_ERR_OK) {
                $file = $_FILES['gallery']['name'][$index];
                $newFileName = "gallery-" . $file;
                move_uploaded_file($tmpName, "$resortFolderPath/gallery/$newFileName");
                $galleryImages[] = "gallery/$newFileName";
            }
        }
    }

    // Process testimonials from POST (each with name, from, content)
    $testimonials = $_POST['testimonials'] ?? [];

    // Encode arrays as JSON
    $amenities_json    = json_encode($amenities);
    $rooms_json        = json_encode($rooms);
    $gallery_json      = json_encode($galleryImages);
    $testimonials_json = json_encode($testimonials);

    if (isset($_POST['resort_id']) && !empty($_POST['resort_id'])) {
        // Update existing resort record; update file_path with the landing page file name.
        $stmt = $pdo->prepare("UPDATE resorts SET destination_id = ?, resort_name = ?, resort_description = ?, banner_title = ?, is_active = ?, amenities = ?, room_details = ?, gallery = ?, testimonials = ?, resort_slug = ?, banner_image = ?, file_path = ? WHERE id = ?");
        $stmt->execute([
            $destination_id,
            $resort_name,
            $resort_description,
            $banner_title,
            $is_active,
            $amenities_json,
            $rooms_json,
            $gallery_json,
            $testimonials_json,
            $resort_slug,
            $banner_image,
            $resortPage,  // Save the landing page file path (e.g., "abc.php")
            $_POST['resort_id']
        ]);
    } else {
        // Insert new resort record; force is_active = 1 and store the landing page file path.
        $stmt = $pdo->prepare("INSERT INTO resorts (resort_name, resort_slug, resort_description, banner_title, is_active, amenities, room_details, gallery, testimonials, destination_id, banner_image, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $resort_name,
            $resort_slug,
            $resort_description,
            $banner_title,
            $is_active,
            $amenities_json,
            $rooms_json,
            $gallery_json,
            $testimonials_json,
            $destination_id,
            $banner_image,
            $resortPage  // Save the landing page file path (e.g., "abc.php")
        ]);
    }

    // Generate resort landing page file (e.g., abc.php)
    $pageContent  = "<?php\n";
    $pageContent .= "require 'db.php';\n";
    $pageContent .= "\$stmt = \$pdo->prepare(\"SELECT * FROM resorts WHERE resort_slug = ?\");\n";
    $pageContent .= "\$stmt->execute(['$resort_slug']);\n";
    $pageContent .= "\$resort = \$stmt->fetch();\n";
    $pageContent .= "if (!\$resort) { echo 'Resort not found.'; exit(); }\n";
    // Redirect to 404 page if resort is not active
    $pageContent .= "if (\$resort['is_active'] != 1) { header('Location: 404.php'); exit(); }\n";
    $pageContent .= "\$destStmt = \$pdo->prepare(\"SELECT * FROM destinations WHERE id = ?\");\n";
    $pageContent .= "\$destStmt->execute([\$resort['destination_id']]);\n";
    $pageContent .= "\$destination = \$destStmt->fetch();\n";
    $pageContent .= "\$amenities = json_decode(\$resort['amenities'] ?? '', true);\n";
    $pageContent .= "\$room_details = json_decode(\$resort['room_details'] ?? '', true);\n";
    $pageContent .= "\$gallery = json_decode(\$resort['gallery'] ?? '', true);\n";
    $pageContent .= "\$testimonials = json_decode(\$resort['testimonials'] ?? '', true);\n";
    // Build the assets folder path for images using the stored slug in the new structure
    $pageContent .= "\$resortFolder = 'assets/resorts/' . (\$resort['resort_slug'] ?? '');\n";
    $pageContent .= "?>\n";
    $pageContent .= "<?php include 'kresort_header.php'; ?>\n";
    $pageContent .= "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css\" />\n";
    $pageContent .= "<style>\n";
    $pageContent .= "h2 { font-size: 1.5rem; margin-bottom: 1.5rem; }\n";
    $pageContent .= ".section-spacing { margin-bottom: 2rem; }\n";
    $pageContent .= ".banner .banner-title { z-index: 2; color: white; font-weight: bold; }\n";
    $pageContent .= ".banner .overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1; }\n";
    $pageContent .= "</style>\n";
    // Banner Section with Overlay for Banner Title
    $pageContent .= "<div class=\"banner position-relative section-spacing\">\n";
    $pageContent .= "  <img src=\"<?php echo \$resortFolder . '/' . (\$resort['banner_image'] ?? ''); ?>\" alt=\"<?php echo \$resort['resort_name'] ?? ''; ?>\" class=\"img-fluid w-100\">\n";
    $pageContent .= "  <div class=\"overlay\"></div>\n";
    $pageContent .= "  <div class=\"position-absolute top-50 start-50 translate-middle text-white banner-title\">\n";
    $pageContent .= "    <h1 class=\"display-4\"><?php echo \$resort['banner_title'] ?? ''; ?></h1>\n";
    $pageContent .= "  </div>\n";
    $pageContent .= "</div>\n";
    // Main Content Section
    $pageContent .= "<div class=\"container section-spacing\">\n";
    $pageContent .= "  <div class=\"row\">\n";
    $pageContent .= "    <div class=\"col-md-8\">\n";
    $pageContent .= "      <h2><?php echo \$resort['resort_name'] ?? ''; ?></h2>\n";
    $pageContent .= "      <p><?php echo \$resort['resort_description'] ?? ''; ?></p>\n";
    $pageContent .= "      <hr class=\"my-4\">\n";
    // Amenities Section
    $pageContent .= "      <h3>Amenities</h3>\n";
    $pageContent .= "      <div class=\"row section-spacing\">\n";
    $pageContent .= "      <?php if(is_array(\$amenities)): foreach(\$amenities as \$a): ?>\n";
    $pageContent .= "         <div class=\"col-6 col-md-3 text-center mb-2\">\n";
    $pageContent .= "            <img src=\"<?php echo \$resortFolder . '/' . (\$a['icon'] ?? ''); ?>\" alt=\"<?php echo \$a['name'] ?? ''; ?>\" class=\"img-thumbnail\" style=\"max-width:50px;\">\n";
    $pageContent .= "            <p style=\"font-size:0.9rem;\"><?php echo \$a['name'] ?? ''; ?></p>\n";
    $pageContent .= "         </div>\n";
    $pageContent .= "      <?php endforeach; endif; ?>\n";
    $pageContent .= "      </div>\n";
    // Room Details Section
    $pageContent .= "      <h3>Room Details</h3>\n";
    $pageContent .= "      <div class=\"row section-spacing\">\n";
    $pageContent .= "      <?php if(is_array(\$room_details)): foreach(\$room_details as \$r): ?>\n";
    $pageContent .= "         <div class=\"col-6 col-md-4 text-center mb-2\">\n";
    $pageContent .= "            <img src=\"<?php echo \$resortFolder . '/' . (\$r['image'] ?? ''); ?>\" alt=\"<?php echo \$r['name'] ?? ''; ?>\" class=\"img-fluid rounded\" style=\"max-width:200px;\">\n";
    $pageContent .= "            <p><?php echo \$r['name'] ?? ''; ?></p>\n";
    $pageContent .= "         </div>\n";
    $pageContent .= "      <?php endforeach; endif; ?>\n";
    $pageContent .= "      </div>\n";
    // Gallery Section
    $pageContent .= "      <h3>Gallery</h3>\n";
    $pageContent .= "      <div class=\"row section-spacing\">\n";
    $pageContent .= "      <?php if(is_array(\$gallery)): foreach(\$gallery as \$img): ?>\n";
    $pageContent .= "         <div class=\"col-6 col-md-4 mb-2\">\n";
    $pageContent .= "           <a href=\"<?php echo \$resortFolder . '/' . \$img; ?>\" data-fancybox=\"gallery\">\n";
    $pageContent .= "             <img src=\"<?php echo \$resortFolder . '/' . \$img; ?>\" alt=\"Gallery Image\" class=\"img-fluid rounded\" style=\"max-width:200px;\">\n";
    $pageContent .= "           </a>\n";
    $pageContent .= "         </div>\n";
    $pageContent .= "      <?php endforeach; endif; ?>\n";
    $pageContent .= "      </div>\n";
    // Testimonials Section
    $pageContent .= "      <h3>Testimonials</h3>\n";
    $pageContent .= "      <div id=\"testimonialCarousel\" class=\"carousel slide section-spacing\" data-bs-ride=\"carousel\" data-bs-interval=\"4000\" data-bs-wrap=\"true\" data-bs-pause=\"false\">\n";
    $pageContent .= "        <div class=\"carousel-inner\">\n";
    $pageContent .= "          <?php if(is_array(\$testimonials) && count(\$testimonials) > 0): ?>\n";
    $pageContent .= "            <?php \$active = 'active'; foreach(\$testimonials as \$t): ?>\n";
    $pageContent .= "              <div class=\"carousel-item <?php echo \$active; ?>\">\n";
    $pageContent .= "                <div class=\"d-flex flex-column align-items-center justify-content-center\" style=\"min-height:180px; padding:1rem;\">\n";
    $pageContent .= "                  <blockquote class=\"blockquote text-center\">\n";
    $pageContent .= "                    <p class=\"mb-0\" style=\"font-size:1rem;\">\"<?php echo \$t['content'] ?? ''; ?>\"</p>\n";
    $pageContent .= "                    <footer class=\"blockquote-footer mt-2\" style=\"font-size:0.8rem;\">\n";
    $pageContent .= "                      <?php echo \$t['name'] ?? ''; ?>, <cite><?php echo \$t['from'] ?? ''; ?></cite>\n";
    $pageContent .= "                    </footer>\n";
    $pageContent .= "                  </blockquote>\n";
    $pageContent .= "                </div>\n";
    $pageContent .= "              </div>\n";
    $pageContent .= "              <?php \$active = ''; endforeach; ?>\n";
    $pageContent .= "          <?php else: ?>\n";
    $pageContent .= "            <div class=\"carousel-item active\">\n";
    $pageContent .= "              <div class=\"d-flex flex-column align-items-center justify-content-center\" style=\"min-height:180px; padding:1rem;\">\n";
    $pageContent .= "                <p>No testimonials available.</p>\n";
    $pageContent .= "              </div>\n";
    $pageContent .= "            </div>\n";
    $pageContent .= "          <?php endif; ?>\n";
    $pageContent .= "        </div>\n";
    $pageContent .= "        <button class=\"carousel-control-prev\" type=\"button\" data-bs-target=\"#testimonialCarousel\" data-bs-slide=\"prev\" style=\"width:30px; height:30px;\">\n";
    $pageContent .= "          <span class=\"carousel-control-prev-icon\" aria-hidden=\"true\" style=\"width:30px; height:30px;\"></span>\n";
    $pageContent .= "          <span class=\"visually-hidden\">Previous</span>\n";
    $pageContent .= "        </button>\n";
    $pageContent .= "        <button class=\"carousel-control-next\" type=\"button\" data-bs-target=\"#testimonialCarousel\" data-bs-slide=\"next\" style=\"width:30px; height:30px;\">\n";
    $pageContent .= "          <span class=\"carousel-control-next-icon\" aria-hidden=\"true\" style=\"width:30px; height:30px;\"></span>\n";
    $pageContent .= "          <span class=\"visually-hidden\">Next</span>\n";
    $pageContent .= "        </button>\n";
    $pageContent .= "      </div>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "    <div class=\"col-md-4\" style=\"position: sticky; top: 0;\">\n";
    $pageContent .= "      <?php include 'destination-form.php'; ?>\n";
    $pageContent .= "    </div>\n";
    $pageContent .= "  </div>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<div style='clear:both;'></div>\n";
    $pageContent .= "<?php include 'kfooter.php'; ?>\n";
    
    file_put_contents($resortPage, $pageContent);
    
    header("Location: $resortPage");
    exit();
}
