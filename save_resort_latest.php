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
    $resort_code        = $_POST['resort_code'] ?? '';
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

    // Process gallery images (multiple files)
    $galleryImages = [];
    
    // First, handle existing gallery images from POST data
    if (isset($_POST['existing_gallery']) && is_array($_POST['existing_gallery'])) {
        foreach ($_POST['existing_gallery'] as $existingImage) {
            if (!empty($existingImage)) {
                $galleryImages[] = $existingImage;
            }
        }
    }

    // Then add new gallery images if any were uploaded
    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        foreach ($_FILES['gallery']['name'] as $index => $filename) {
            if (!empty($filename) && $_FILES['gallery']['error'][$index] == UPLOAD_ERR_OK) {
                $newFileName = "gallery-" . time() . "-" . $filename;
                if (move_uploaded_file($_FILES['gallery']['tmp_name'][$index], "$resortFolderPath/gallery/$newFileName")) {
                    $galleryImages[] = "gallery/$newFileName";
                }
            }
        }
    }

    // If no new images and no existing images in POST, keep the old ones from database
    if (empty($galleryImages) && isset($resort['gallery']) && !empty($resort['gallery'])) {
        $existingGallery = json_decode($resort['gallery'], true);
        if (is_array($existingGallery)) {
            $galleryImages = $existingGallery;
        }
    }

    // Handle banner image
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK && !empty($_FILES['banner_image']['name'])) {
        // New banner image uploaded
        $banner_image = "banner-" . time() . "-" . $_FILES['banner_image']['name'];
        move_uploaded_file($_FILES['banner_image']['tmp_name'], "$resortFolderPath/$banner_image");
    } elseif (isset($_POST['existing_banner_image']) && !empty($_POST['existing_banner_image'])) {
        // Keep existing banner image from POST data
        $banner_image = $_POST['existing_banner_image'];
    } elseif (isset($resort['banner_image']) && !empty($resort['banner_image'])) {
        // Fallback to database banner image
        $banner_image = $resort['banner_image'];
    } else {
        $banner_image = ''; // Default empty if no image
    }

    // Debug logging
    error_log("Banner image path: " . $banner_image);
    error_log("Gallery images: " . print_r($galleryImages, true));

    // Process dynamic file uploads for rooms
    $rooms = [];
    if (isset($_POST['rooms']) && is_array($_POST['rooms'])) {
        foreach ($_POST['rooms'] as $index => $room) {
            if (!empty($room['name'])) {
                $roomData = [
                    'name' => trim($room['name'])
                ];

                // Handle room image
                if (isset($_FILES['rooms']['tmp_name'][$index]['image']) && $_FILES['rooms']['error'][$index]['image'] == UPLOAD_ERR_OK) {
                $file = $_FILES['rooms']['name'][$index]['image'];
                $newFileName = "rooms-" . $file;
                move_uploaded_file($_FILES['rooms']['tmp_name'][$index]['image'], "$resortFolderPath/rooms/$newFileName");
                    $roomData['image'] = "rooms/$newFileName";
                } else if (isset($room['existing_image'])) {
                    $roomData['image'] = $room['existing_image'];
                }

                $rooms[] = $roomData;
            }
        }
    }

    // Process dynamic file uploads for amenities
    $amenities = [];
    if (isset($_POST['amenities']) && is_array($_POST['amenities'])) {
        foreach ($_POST['amenities'] as $index => $amenity) {
            if (!empty($amenity['name'])) {
                $amenityData = [
                    'name' => trim($amenity['name'])
                ];

                // Handle amenity icon
                if (isset($_FILES['amenities']['tmp_name'][$index]['icon']) && $_FILES['amenities']['error'][$index]['icon'] == UPLOAD_ERR_OK) {
                    $file = $_FILES['amenities']['name'][$index]['icon'];
                    $newFileName = "amenities-" . $file;
                    move_uploaded_file($_FILES['amenities']['tmp_name'][$index]['icon'], "$resortFolderPath/amenities/$newFileName");
                    $amenityData['icon'] = "amenities/$newFileName";
                } else if (isset($amenity['existing_icon'])) {
                    $amenityData['icon'] = $amenity['existing_icon'];
                }

                $amenities[] = $amenityData;
            }
        }
    }

    // Process testimonials from POST (each with name, from, content)
    $testimonials = [];
    if (isset($_POST['testimonials']) && is_array($_POST['testimonials'])) {
        foreach ($_POST['testimonials'] as $testimonial) {
            // Only add testimonials that have at least a name and content
            if (!empty($testimonial['name']) || !empty($testimonial['content'])) {
                $testimonials[] = [
                    'name' => trim($testimonial['name'] ?? ''),
                    'from' => trim($testimonial['from'] ?? ''),
                    'content' => trim($testimonial['content'] ?? '')
                ];
            }
        }
    }

    // If editing and no new testimonials were provided, keep existing ones
    if (empty($testimonials) && isset($_POST['resort_id']) && isset($resort['testimonials'])) {
        $existingTestimonials = json_decode($resort['testimonials'], true);
        if (is_array($existingTestimonials) && !empty($existingTestimonials)) {
            $testimonials = $existingTestimonials;
        }
    }

    // Debug testimonials
    error_log("Testimonials before JSON encode: " . print_r($testimonials, true));
    
    // Encode arrays as JSON with error checking
    $testimonials_json = json_encode($testimonials);
    if (json_last_error() !== JSON_ERROR_NONE) {
        error_log("JSON encode error for testimonials: " . json_last_error_msg());
        $testimonials_json = json_encode([]); // Fallback to empty array if encoding fails
    }
    error_log("Testimonials JSON: " . $testimonials_json);

    $amenities_json    = json_encode($amenities);
    $rooms_json        = json_encode($rooms);
    $gallery_json      = json_encode($galleryImages);

    if (isset($_POST['resort_id']) && !empty($_POST['resort_id'])) {
        // Update existing resort record
        $stmt = $pdo->prepare("UPDATE resorts SET destination_id = ?, resort_name = ?, resort_code = ?, resort_description = ?, banner_title = ?, is_active = ?, amenities = ?, room_details = ?, gallery = ?, testimonials = ?, resort_slug = ?, banner_image = ?, file_path = ? WHERE id = ?");
        $success = $stmt->execute([
            $destination_id,
            $resort_name,
            $resort_code,
            $resort_description,
            $banner_title,
            $is_active,
            $amenities_json,
            $rooms_json,
            $gallery_json,
            $testimonials_json,
            $resort_slug,
            $banner_image,
            $resortPage,
            $_POST['resort_id']
        ]);
        
        if ($success) {
            $_SESSION['success_message'] = "Resort updated successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to update resort. Please try again.";
        }
    } else {
        // Insert new resort record
        $stmt = $pdo->prepare("INSERT INTO resorts (resort_name, resort_code, resort_slug, resort_description, banner_title, is_active, amenities, room_details, gallery, testimonials, destination_id, banner_image, file_path) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $success = $stmt->execute([
            $resort_name,
            $resort_code,
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
            $resortPage
        ]);
        
        if ($success) {
            $_SESSION['success_message'] = "New resort created successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to create resort. Please try again.";
        }
    }

    // Generate resort landing page file (e.g., abc.php)
    $pageContent  = "<?php\n";
    $pageContent .= "session_start();\n";
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
    // Link CSS files
    $pageContent .= "<link rel=\"stylesheet\" href=\"css/resort-details.css\" />\n";
    $pageContent .= "<link rel=\"stylesheet\" href=\"https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css\" />\n";
    $pageContent .= "<link rel=\"stylesheet\" href=\"https://unpkg.com/swiper/swiper-bundle.min.css\" />\n"; // Swiper CSS
    $pageContent .= "<link rel=\"stylesheet\" href=\"assets/int-tel-input/css/intlTelInput.min.css\">\n"; // Intl Tel Input CSS

    // Banner Section (Modified for bottom-left title)
    $pageContent .= "<div class=\"resort-banner modern-banner\">\n";
    $pageContent .= "<?php if (!empty(\$resort['banner_image'])): ?>\n";
    $pageContent .= "  <img src=\"<?php echo \$resortFolder . '/' . htmlspecialchars(\$resort['banner_image']); ?>\" alt=\"<?php echo htmlspecialchars(\$resort['resort_name']); ?> Banner\" class=\"banner-image\">\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "  <div class=\"banner-content-bottom-left\">\n";
    $pageContent .= "    <h1 class=\"banner-title\"><?php echo htmlspecialchars(\$resort['banner_title'] ?? ''); ?></h1>\n";
    $pageContent .= "  </div>\n";
    $pageContent .= "</div>\n";

    // Main Content Section (2 Columns)
    $pageContent .= "<div class=\"container resort-details-container section-padding\">\n";
    $pageContent .= "  <div class=\"row\">\n";
    // Left Column: Resort Details
    $pageContent .= "    <div class=\"col-lg-8 resort-content-left\">\n";
    // Resort Name and Description
    $pageContent .= "      <h2 class=\"resort-name\"><?php echo htmlspecialchars(\$resort['resort_name'] ?? ''); ?></h2>\n";
    $pageContent .= "      <p class=\"resort-description\"><?php echo nl2br(htmlspecialchars(\$resort['resort_description'] ?? '')); ?></p>\n";

    // Amenities Section (Added classes for animation)
    $pageContent .= "<div class=\"resort-section amenities-section\">\n"; // Added class
    $pageContent .= "        <h3>Amenities</h3>\n";
    $pageContent .= "<div class=\"amenities-grid\">\n";
    $pageContent .= "<?php if(is_array(\$amenities)): foreach(\$amenities as \$a): ?>\n";
    $pageContent .= "<div class=\"amenity-item animate-icon\">\n"; // Added class for animation
    $pageContent .= "<img src=\"<?php echo \$resortFolder . '/' . htmlspecialchars(\$a['icon'] ?? ''); ?>\" alt=\"<?php echo htmlspecialchars(\$a['name'] ?? ''); ?>\">\n";
    $pageContent .= "<p><?php echo htmlspecialchars(\$a['name'] ?? ''); ?></p>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php endforeach; else: ?>\n";
    $pageContent .= "<p>No amenities listed.</p>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "</div>\n";

    // Room Details Section (Added classes for hover effect)
    $pageContent .= "<div class=\"resort-section rooms-section\">\n"; // Added class
    $pageContent .= "        <h3>Room Details</h3>\n";
    $pageContent .= "<div class=\"room-details-grid\">\n";
    $pageContent .= "<?php if(is_array(\$room_details)): foreach(\$room_details as \$r): ?>\n";
    $pageContent .= "<div class=\"room-item room-hover-effect\">\n"; // Added class for hover
    $pageContent .= "<div class=\"room-image-container\">\n"; // Container for image
    $pageContent .= "<img src=\"<?php echo \$resortFolder . '/' . htmlspecialchars(\$r['image'] ?? ''); ?>\" alt=\"<?php echo htmlspecialchars(\$r['name'] ?? ''); ?>\">\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<div class=\"room-info\">\n"; // Container for text
    $pageContent .= "<p><?php echo htmlspecialchars(\$r['name'] ?? ''); ?></p>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php endforeach; else: ?>\n";
    $pageContent .= "<p>No room details available.</p>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "</div>\n";

    // Gallery Section (Modified for Grid Layout + Lightbox)
    $pageContent .= "<div class=\"resort-section gallery-section\">\n"; 
    $pageContent .= "        <h3>Gallery</h3>\n";
    $pageContent .= "<?php\n";
    $pageContent .= "\$gallery = json_decode(\$resort['gallery'] ?? '[]', true);\n";
    $pageContent .= "if(is_array(\$gallery) && count(\$gallery) > 0): ?>\n";
    $pageContent .= "<div class=\"gallery-grid\">\n";
    $pageContent .= "<?php foreach(\$gallery as \$img): ?>\n";
    $pageContent .= "<?php if(!empty(\$img)): ?>\n";
    $pageContent .= "<div class=\"gallery-item\">\n";
    $pageContent .= "<a href=\"<?php echo \$resortFolder . '/' . htmlspecialchars(\$img); ?>\" data-fancybox=\"gallery\" class=\"gallery-link\" data-caption=\"<?php echo htmlspecialchars(\$resort['resort_name']); ?> Gallery Image\">\n";
    $pageContent .= "<img src=\"<?php echo \$resortFolder . '/' . htmlspecialchars(\$img); ?>\" alt=\"Gallery Image\" class=\"gallery-image\">\n";
    $pageContent .= "<div class=\"gallery-overlay\"><i class=\"fas fa-search-plus\"></i></div>\n";
    $pageContent .= "</a>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "<?php endforeach; ?>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php else: ?>\n";
    $pageContent .= "<p>No gallery images available.</p>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "</div>\n";

    // Testimonials Section (Fixed Autoplay)
    $pageContent .= "<div class=\"resort-section testimonials-section modern-testimonials\">\n";
    $pageContent .= "        <h3>What Our Guests Say</h3>\n";
    $pageContent .= "<?php\n";
    $pageContent .= "\$testimonials = json_decode(\$resort['testimonials'], true);\n";
    $pageContent .= "if(is_array(\$testimonials) && count(\$testimonials) > 0): ?>\n";
    $pageContent .= "<div class=\"swiper testimonial-carousel\">\n";
    $pageContent .= "<div class=\"swiper-wrapper\">\n";
    $pageContent .= "<?php foreach(\$testimonials as \$t): ?>\n";
    $pageContent .= "<div class=\"swiper-slide testimonial-item\">\n";
    $pageContent .= "<blockquote class=\"testimonial-content\">\n";
    $pageContent .= "<p class=\"testimonial-text\">\"<?php echo htmlspecialchars(\$t['content']); ?>\"</p>\n";
    $pageContent .= "<footer class=\"testimonial-author\">\n";
    $pageContent .= "<strong><?php echo htmlspecialchars(\$t['name']); ?></strong>\n";
    $pageContent .= "<?php if(!empty(\$t['from'])): ?>\n";
    $pageContent .= "<span>, <?php echo htmlspecialchars(\$t['from']); ?></span>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "</footer>\n";
    $pageContent .= "</blockquote>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php endforeach; ?>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<div class=\"swiper-pagination testimonial-pagination\"></div>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "<?php else: ?>\n";
    $pageContent .= "<p>No testimonials available at the moment.</p>\n";
    $pageContent .= "<?php endif; ?>\n";
    $pageContent .= "</div>\n";

    // Add CSS for testimonials
    $pageContent .= "<style>\n";
    $pageContent .= ".testimonial-carousel { padding: 20px 0; }\n";
    $pageContent .= ".testimonial-item { text-align: center; padding: 20px; }\n";
    $pageContent .= ".testimonial-content { font-style: italic; margin-bottom: 15px; }\n";
    $pageContent .= ".testimonial-text { font-size: 1.1em; line-height: 1.6; margin-bottom: 15px; }\n";
    $pageContent .= ".testimonial-author { font-size: 0.9em; color: #666; }\n";
    $pageContent .= ".testimonial-author strong { color: #333; }\n";
    $pageContent .= "</style>\n";

    // Initialize Swiper for testimonials
    $pageContent .= "<script>\n";
    $pageContent .= "document.addEventListener('DOMContentLoaded', function() {\n";
    $pageContent .= "  if(document.querySelector('.testimonial-carousel')) {\n";
    $pageContent .= "    const testimonialCarousel = new Swiper('.testimonial-carousel', {\n";
    $pageContent .= "      loop: true,\n";
    $pageContent .= "      autoplay: {\n";
    $pageContent .= "        delay: 5000,\n";
    $pageContent .= "        disableOnInteraction: false,\n";
    $pageContent .= "        pauseOnMouseEnter: true\n";
    $pageContent .= "      },\n";
    $pageContent .= "      speed: 1000,\n";
    $pageContent .= "      effect: 'fade',\n";
    $pageContent .= "      fadeEffect: { crossFade: true },\n";
    $pageContent .= "      pagination: {\n";
    $pageContent .= "        el: '.testimonial-pagination',\n";
    $pageContent .= "        clickable: true\n";
    $pageContent .= "      }\n";
    $pageContent .= "    });\n";
    $pageContent .= "  }\n";
    $pageContent .= "});\n";
    $pageContent .= "</script>\n";

    $pageContent .= "</div>\n"; // End Left Column

    // Right Column: Sticky Form
    $pageContent .= "<div class=\"col-lg-4\">\n";
    $pageContent .= "<div class=\"sticky-form-container\">\n";
    $pageContent .= "<div class=\"resort-form-container\">\n";
    $pageContent .= "<form id=\"resortEnquiryForm\" method=\"POST\" action=\"process_resort_enquiry.php\">\n";
    $pageContent .= "<input type=\"hidden\" name=\"csrf_token\" value=\"<?php echo \$_SESSION['csrf_token']; ?>\">\n";
    $pageContent .= "<input type=\"hidden\" name=\"resort_id\" value=\"<?php echo htmlspecialchars(\$resort['id']); ?>\">\n";
    $pageContent .= "<input type=\"hidden\" name=\"resort_name\" value=\"<?php echo htmlspecialchars(\$resort['resort_name']); ?>\">\n";
    $pageContent .= "<input type=\"hidden\" name=\"destination_name\" value=\"<?php echo htmlspecialchars(\$destination['destination_name']); ?>\">\n";
    $pageContent .= "<input type=\"hidden\" name=\"resort_code\" value=\"<?php echo htmlspecialchars(\$resort['resort_code']); ?>\">\n";

    $pageContent .= "<div class=\"form-grid\">\n";
    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"firstName\">First Name *</label>\n";
    $pageContent .= "<input type=\"text\" id=\"firstName\" name=\"firstName\" class=\"form-control\" required>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"lastName\">Last Name *</label>\n";
    $pageContent .= "<input type=\"text\" id=\"lastName\" name=\"lastName\" class=\"form-control\" required>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"email\">Email *</label>\n";
    $pageContent .= "<input type=\"email\" id=\"email\" name=\"email\" class=\"form-control\" required>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"phone\">Phone Number *</label>\n";
    $pageContent .= "<input type=\"tel\" id=\"phone\" name=\"phone\" class=\"form-control\" required>\n";
    $pageContent .= "<div id=\"phone-error\" class=\"error-message\">Please enter a valid phone number</div>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"dob\">Date of Birth * (Must be 27 years or older)</label>\n";
    $pageContent .= "<input type=\"date\" id=\"dob\" name=\"dob\" class=\"form-control\" required>\n";
    $pageContent .= "<div id=\"dob-error\" class=\"error-message\">You must be at least 27 years old</div>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<div class=\"form-group\">\n";
    $pageContent .= "<label for=\"hasPassport\">Do you have a passport? *</label>\n";
    $pageContent .= "<select id=\"hasPassport\" name=\"hasPassport\" class=\"form-control\" required>\n";
    $pageContent .= "<option value=\"\">Select an option</option>\n";
    $pageContent .= "<option value=\"yes\">Yes</option>\n";
    $pageContent .= "<option value=\"no\">No</option>\n";
    $pageContent .= "</select>\n";
    $pageContent .= "</div>\n";
    $pageContent .= "</div>\n";

    $pageContent .= "<button type=\"submit\" class=\"btn-submit\">Submit Enquiry</button>\n";
    $pageContent .= "</form>\n";
    $pageContent .= "</div>\n";

    // Add form styles
    $pageContent .= "<style>\n";
    $pageContent .= ".resort-form-container { width: 100%; padding: 20px; background: #fff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }\n";
    $pageContent .= ".form-grid { display: grid; grid-template-columns: 1fr; gap: 20px; margin-bottom: 20px; }\n";
    $pageContent .= ".form-group { margin-bottom: 15px; }\n";
    $pageContent .= ".form-group label { display: block; margin-bottom: 5px; font-weight: 600; color: #333; }\n";
    $pageContent .= ".form-control { width: 100%; padding: 8px 12px; border: 1px solid #ddd; border-radius: 4px; font-size: 14px; }\n";
    $pageContent .= ".btn-submit { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; font-size: 16px; width: 100%; }\n";
    $pageContent .= ".btn-submit:hover { background: #0056b3; }\n";
    $pageContent .= ".error-message { color: #dc3545; font-size: 12px; margin-top: 5px; display: none; }\n";
    $pageContent .= ".error-message.show { display: block; }\n";
    $pageContent .= ".iti { width: 100% !important; }\n";
    $pageContent .= ".iti__country-list { max-height: 200px !important; overflow-y: auto !important; width: 260px !important; position: absolute !important; z-index: 9999 !important; background-color: white !important; box-shadow: 0 2px 4px rgba(0,0,0,0.2) !important; }\n";
    $pageContent .= "</style>\n";

    // Add form validation and phone input initialization
    $pageContent .= "<script>\n";
    $pageContent .= "document.addEventListener('DOMContentLoaded', function() {\n";
    $pageContent .= "    // Phone number initialization\n";
    $pageContent .= "    var phoneInput = document.querySelector('#phone');\n";
    $pageContent .= "    var iti = window.intlTelInput(phoneInput, {\n";
    $pageContent .= "        preferredCountries: ['in', 'ae', 'gb', 'us'],\n";
    $pageContent .= "        separateDialCode: true,\n";
    $pageContent .= "        utilsScript: 'https://cdnjs.cloudflare.com/ajax/libs/intl-tel-input/17.0.8/js/utils.js'\n";
    $pageContent .= "    });\n";

    $pageContent .= "    // Create hidden input for full phone number\n";
    $pageContent .= "    var hiddenPhoneInput = document.createElement('input');\n";
    $pageContent .= "    hiddenPhoneInput.type = 'hidden';\n";
    $pageContent .= "    hiddenPhoneInput.name = 'full_phone';\n";
    $pageContent .= "    phoneInput.parentNode.insertBefore(hiddenPhoneInput, phoneInput.nextSibling);\n";

    $pageContent .= "    // Date of birth validation\n";
    $pageContent .= "    var dobInput = document.getElementById('dob');\n";
    $pageContent .= "    dobInput.max = new Date(new Date().setFullYear(new Date().getFullYear() - 27)).toISOString().split('T')[0];\n";

    $pageContent .= "    function validateDOB() {\n";
    $pageContent .= "        var dob = new Date(dobInput.value);\n";
    $pageContent .= "        var age = Math.floor((new Date() - dob) / (365.25 * 24 * 60 * 60 * 1000));\n";
    $pageContent .= "        var errorDiv = document.getElementById('dob-error');\n";
    $pageContent .= "        if (age < 27) {\n";
    $pageContent .= "            errorDiv.classList.add('show');\n";
    $pageContent .= "            return false;\n";
    $pageContent .= "        } else {\n";
    $pageContent .= "            errorDiv.classList.remove('show');\n";
    $pageContent .= "            return true;\n";
    $pageContent .= "        }\n";
    $pageContent .= "    }\n";

    $pageContent .= "    function validatePhoneNumber() {\n";
    $pageContent .= "        var errorDiv = document.getElementById('phone-error');\n";
    $pageContent .= "        if (!iti.isValidNumber()) {\n";
    $pageContent .= "            errorDiv.classList.add('show');\n";
    $pageContent .= "            return false;\n";
    $pageContent .= "        } else {\n";
    $pageContent .= "            errorDiv.classList.remove('show');\n";
    $pageContent .= "            hiddenPhoneInput.value = iti.getNumber();\n";
    $pageContent .= "            return true;\n";
    $pageContent .= "        }\n";
    $pageContent .= "    }\n";

    $pageContent .= "    // Form validation\n";
    $pageContent .= "    document.getElementById('resortEnquiryForm').addEventListener('submit', function(event) {\n";
    $pageContent .= "        var isPhoneValid = validatePhoneNumber();\n";
    $pageContent .= "        var isDOBValid = validateDOB();\n";
    $pageContent .= "        if (!isPhoneValid || !isDOBValid) {\n";
    $pageContent .= "            event.preventDefault();\n";
    $pageContent .= "        }\n";
    $pageContent .= "    });\n";

    $pageContent .= "    // Live validation\n";
    $pageContent .= "    phoneInput.addEventListener('input', validatePhoneNumber);\n";
    $pageContent .= "    phoneInput.addEventListener('countrychange', validatePhoneNumber);\n";
    $pageContent .= "    dobInput.addEventListener('change', validateDOB);\n";
    $pageContent .= "});\n";
    $pageContent .= "</script>\n";

    $pageContent .= "</div>\n"; // End sticky-form-container
    $pageContent .= "</div>\n"; // End col-lg-4

    $pageContent .= "</div>\n"; // End Row
    $pageContent .= "</div>\n"; // End Container

    // Include Footer
    $pageContent .= "<?php include 'kfooter.php'; ?>\n";

    // Include JS Libraries
    $pageContent .= "<script src=\"https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.umd.js\"></script>\n";
    $pageContent .= "<script src=\"https://unpkg.com/swiper/swiper-bundle.min.js\"></script>\n"; // Swiper JS
    $pageContent .= "<script src=\"assets/int-tel-input/js/intlTelInput.min.js\"></script>\n"; // Intl Tel Input JS
    $pageContent .= "<script src=\"assets/int-tel-input/js/utils.js\"></script>\n"; // Intl Tel Input Utils
    $pageContent .= "<script src=\"https://kit.fontawesome.com/your-font-awesome-kit.js\"></script>\n"; // Add FontAwesome for gallery icons

    // Custom JS Initializations
    $pageContent .= "<script>\n";
    // Fancybox Init with carousel effect
    $pageContent .= "Fancybox.bind('[data-fancybox=\"gallery\"]', {\n";
    $pageContent .= "  carousel: { infinite: true },\n";
    $pageContent .= "  Toolbar: {\n";
    $pageContent .= "    display: ['slideshow', 'fullscreen', 'thumbs', 'close']\n";
    $pageContent .= "  },\n";
    $pageContent .= "  Thumbs: { autoStart: true },\n";
    $pageContent .= "  Slideshow: { autoStart: false, speed: 4000 }\n";
    $pageContent .= "});\n";

    // Gallery Carousel Init
    $pageContent .= "const galleryCarousel = new Swiper('.gallery-carousel', {\n";
    $pageContent .= "loop: true,\n";
    $pageContent .= "slidesPerView: 1,\n";
    $pageContent .= "spaceBetween: 10,\n";
    $pageContent .= "pagination: { el: '.gallery-pagination', clickable: true },\n";
    $pageContent .= "navigation: { nextEl: '.gallery-button-next', prevEl: '.gallery-button-prev' },\n";
    $pageContent .= "breakpoints: { 640: { slidesPerView: 2, spaceBetween: 20 }, 1024: { slidesPerView: 3, spaceBetween: 30 } }\n";
    $pageContent .= "});\n";

    // Intl Tel Input Init (Target the phone input field in destination-form.php)
    $pageContent .= "const phoneInputField = document.querySelector('#phone');\n";
    $pageContent .= "if (phoneInputField) {\n";
    $pageContent .= "const phoneInput = window.intlTelInput(phoneInputField, {\n";
    $pageContent .= "initialCountry: 'auto',\n";
    $pageContent .= "geoIpLookup: function(callback) {\n";
    $pageContent .= "fetch('https://ipapi.co/json')\n";
    $pageContent .= ".then(function(res) { return res.json(); })\n";
    $pageContent .= ".then(function(data) { callback(data.country_code); })\n";
    $pageContent .= ".catch(function() { callback('us'); });\n";
    $pageContent .= "},\n";
    $pageContent .= "utilsScript: 'assets/int-tel-input/js/utils.js'\n";
    $pageContent .= "});\n";
    $pageContent .= "// You might want to store the full number on form submit\n";
    $pageContent .= "const form = phoneInputField.closest('form');\n";
    $pageContent .= "if (form) {\n";
    $pageContent .= "form.addEventListener('submit', function() {\n";
    $pageContent .= "const fullNumber = phoneInput.getNumber();\n";
    $pageContent .= "// Add a hidden input to store the full number if needed\n";
    $pageContent .= "let hiddenInput = form.querySelector('input[name=\"full_phone\"]');\n";
    $pageContent .= "if (!hiddenInput) {\n";
    $pageContent .= "hiddenInput = document.createElement('input');\n";
    $pageContent .= "hiddenInput.type = 'hidden';\n";
    $pageContent .= "hiddenInput.name = 'full_phone';\n";
    $pageContent .= "form.appendChild(hiddenInput);\n";
    $pageContent .= "}\n";
    $pageContent .= "hiddenInput.value = fullNumber;\n";
    $pageContent .= "});\n";
    $pageContent .= "}\n";
    $pageContent .= "}\n";
    $pageContent .= "</script>\n";

    file_put_contents($resortPage, $pageContent);
    
    // Different redirect behavior for create vs update
    if (isset($_POST['resort_id']) && !empty($_POST['resort_id'])) {
        // Update case - redirect to resort list in current tab
        header('Location: resort_list.php');
        exit();
    } else {
        // Create case - open new resort in new tab and redirect current tab to resort list
        echo "<script>
            window.open('$resortPage', '_blank');
            window.location.href = 'resort_list.php';
        </script>";
    }
    exit();
}
?>
