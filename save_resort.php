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
        mkdir("$resortFolderPath/banner", 0777, true);
        mkdir("$resortFolderPath/amenities", 0777, true);
        mkdir("$resortFolderPath/gallery", 0777, true);
        mkdir("$resortFolderPath/rooms", 0777, true);
    }

    // Handle banner image upload
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
        $banner_image = "banner/" . basename($_FILES['banner_image']['name']);
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
                $newFileName = basename($file);
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
                $newFileName = basename($file);
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
                $newFileName = basename($file);
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

        // Generate resort landing page file (e.g., abc.php)
        $pageContent  = "<?php\n";
        $pageContent .= "require 'db.php';\n";
        $pageContent .= "require 'kheader.php';\n";
        $pageContent .= "// Get resort details from database\n";
        $pageContent .= "\$resort_slug = basename(\$_SERVER['PHP_SELF'], '.php');\n";
        $pageContent .= "\$stmt = \$pdo->prepare(\"SELECT r.*, d.destination_name \n";
        $pageContent .= "                       FROM resorts r \n";
        $pageContent .= "                       LEFT JOIN destinations d ON r.destination_id = d.id \n";
        $pageContent .= "                       WHERE r.resort_slug = ?\");\n";
        $pageContent .= "\$stmt->execute([\$resort_slug]);\n";
        $pageContent .= "\$resort = \$stmt->fetch();\n\n";
        $pageContent .= "if (!\$resort) {\n";
        $pageContent .= "    header('Location: 404.php');\n";
        $pageContent .= "    exit();\n";
        $pageContent .= "}\n\n";
        $pageContent .= "// Parse JSON data\n";
        $pageContent .= "\$amenities = json_decode(\$resort['amenities'], true) ?? [];\n";
        $pageContent .= "\$rooms = json_decode(\$resort['room_details'], true) ?? [];\n";
        $pageContent .= "\$gallery = json_decode(\$resort['gallery'], true) ?? [];\n";
        $pageContent .= "\$testimonials = json_decode(\$resort['testimonials'], true) ?? [];\n";
        $pageContent .= "?>\n";
        $pageContent .= "<!DOCTYPE html>\n";
        $pageContent .= "<html lang=\"en\">\n";
        $pageContent .= "<head>\n";
        $pageContent .= "    <meta charset=\"UTF-8\">\n";
        $pageContent .= "    <meta name=\"viewport\" content=\"width=device-width, initial-scale=1.0\">\n";
        $pageContent .= "    <title><?php echo htmlspecialchars(\$resort['resort_name']); ?> - Karma Experience</title>\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"css/resort-details.css\">\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css\">\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.carousel.min.css\">\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/assets/owl.theme.default.min.css\">\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.4/css/all.min.css\">\n";
        $pageContent .= "    <link rel=\"stylesheet\" href=\"https://unpkg.com/swiper/swiper-bundle.min.css\">\n";
        $pageContent .= "    <style>\n";
        $pageContent .= "        /* Room Cards */\n";
        $pageContent .= "        .rooms-grid {\n";
        $pageContent .= "            display: grid;\n";
        $pageContent .= "            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));\n";
        $pageContent .= "            gap: 20px;\n";
        $pageContent .= "            padding: 20px 0;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .room-card {\n";
        $pageContent .= "            background: white;\n";
        $pageContent .= "            border-radius: 10px;\n";
        $pageContent .= "            box-shadow: 0 4px 8px rgba(0,0,0,0.1);\n";
        $pageContent .= "            overflow: hidden;\n";
        $pageContent .= "            transition: transform 0.3s ease, box-shadow 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .room-card:hover {\n";
        $pageContent .= "            transform: translateY(-5px);\n";
        $pageContent .= "            box-shadow: 0 8px 16px rgba(0,0,0,0.2);\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .room-card img {\n";
        $pageContent .= "            width: 100%;\n";
        $pageContent .= "            height: 200px;\n";
        $pageContent .= "            object-fit: cover;\n";
        $pageContent .= "            transition: transform 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .room-card:hover img {\n";
        $pageContent .= "            transform: scale(1.05);\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .room-card h4 {\n";
        $pageContent .= "            padding: 15px;\n";
        $pageContent .= "            margin: 0;\n";
        $pageContent .= "            font-size: 1.2em;\n";
        $pageContent .= "            color: #333;\n";
        $pageContent .= "            text-align: center;\n";
        $pageContent .= "        }\n\n";
        $pageContent .= "        /* Gallery Grid */\n";
        $pageContent .= "        .gallery-grid {\n";
        $pageContent .= "            display: grid;\n";
        $pageContent .= "            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));\n";
        $pageContent .= "            gap: 15px;\n";
        $pageContent .= "            padding: 20px 0;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item {\n";
        $pageContent .= "            position: relative;\n";
        $pageContent .= "            overflow: hidden;\n";
        $pageContent .= "            border-radius: 8px;\n";
        $pageContent .= "            aspect-ratio: 1;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item img {\n";
        $pageContent .= "            width: 100%;\n";
        $pageContent .= "            height: 100%;\n";
        $pageContent .= "            object-fit: cover;\n";
        $pageContent .= "            transition: transform 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item:hover img {\n";
        $pageContent .= "            transform: scale(1.1);\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item::after {\n";
        $pageContent .= "            content: '';\n";
        $pageContent .= "            position: absolute;\n";
        $pageContent .= "            top: 0;\n";
        $pageContent .= "            left: 0;\n";
        $pageContent .= "            right: 0;\n";
        $pageContent .= "            bottom: 0;\n";
        $pageContent .= "            background: rgba(0,0,0,0.3);\n";
        $pageContent .= "            opacity: 0;\n";
        $pageContent .= "            transition: opacity 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item:hover::after {\n";
        $pageContent .= "            opacity: 1;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item::before {\n";
        $pageContent .= "            content: '🔍';\n";
        $pageContent .= "            position: absolute;\n";
        $pageContent .= "            top: 50%;\n";
        $pageContent .= "            left: 50%;\n";
        $pageContent .= "            transform: translate(-50%, -50%) scale(0);\n";
        $pageContent .= "            color: white;\n";
        $pageContent .= "            font-size: 24px;\n";
        $pageContent .= "            z-index: 1;\n";
        $pageContent .= "            transition: transform 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .gallery-item:hover::before {\n";
        $pageContent .= "            transform: translate(-50%, -50%) scale(1);\n";
        $pageContent .= "        }\n\n";
        $pageContent .= "        /* Amenities */\n";
        $pageContent .= "        .amenities-grid {\n";
        $pageContent .= "            display: grid;\n";
        $pageContent .= "            grid-template-columns: repeat(auto-fill, minmax(120px, 1fr));\n";
        $pageContent .= "            gap: 15px;\n";
        $pageContent .= "            padding: 20px 0;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .amenity-item {\n";
        $pageContent .= "            display: flex;\n";
        $pageContent .= "            flex-direction: column;\n";
        $pageContent .= "            align-items: center;\n";
        $pageContent .= "            text-align: center;\n";
        $pageContent .= "            padding: 10px;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .amenity-icon {\n";
        $pageContent .= "            width: 40px;\n";
        $pageContent .= "            height: 40px;\n";
        $pageContent .= "            object-fit: contain;\n";
        $pageContent .= "            margin-bottom: 8px;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .amenity-name {\n";
        $pageContent .= "            font-size: 0.85em;\n";
        $pageContent .= "            color: #666;\n";
        $pageContent .= "            margin: 0;\n";
        $pageContent .= "            line-height: 1.3;\n";
        $pageContent .= "        }\n\n";
        $pageContent .= "        /* Testimonials */\n";
        $pageContent .= "        .testimonials-section {\n";
        $pageContent .= "            padding: 30px 0;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial {\n";
        $pageContent .= "            background: #fff;\n";
        $pageContent .= "            padding: 25px;\n";
        $pageContent .= "            border-radius: 10px;\n";
        $pageContent .= "            box-shadow: 0 4px 6px rgba(0,0,0,0.1);\n";
        $pageContent .= "            margin: 10px;\n";
        $pageContent .= "            text-align: center;\n";
        $pageContent .= "            transition: all 0.3s ease;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial:hover {\n";
        $pageContent .= "            transform: translateY(-5px);\n";
        $pageContent .= "            box-shadow: 0 8px 15px rgba(0,0,0,0.2);\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial-content {\n";
        $pageContent .= "            font-size: 1.1em;\n";
        $pageContent .= "            line-height: 1.6;\n";
        $pageContent .= "            color: #555;\n";
        $pageContent .= "            margin-bottom: 20px;\n";
        $pageContent .= "            font-style: italic;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial-content i {\n";
        $pageContent .= "            color: #3498db;\n";
        $pageContent .= "            margin: 0 10px;\n";
        $pageContent .= "            font-size: 1.2em;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial-author {\n";
        $pageContent .= "            display: flex;\n";
        $pageContent .= "            flex-direction: column;\n";
        $pageContent .= "            align-items: center;\n";
        $pageContent .= "            gap: 5px;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial-author strong {\n";
        $pageContent .= "            color: #333;\n";
        $pageContent .= "            font-size: 1.1em;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .testimonial-author .location {\n";
        $pageContent .= "            color: #666;\n";
        $pageContent .= "            font-size: 0.9em;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .swiper-container {\n";
        $pageContent .= "            width: 100%;\n";
        $pageContent .= "            padding: 20px 0;\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .swiper-slide {\n";
        $pageContent .= "            display: flex;\n";
        $pageContent .= "            flex-direction: column;\n";
        $pageContent .= "            align-items: center;\n";
        $pageContent .= "            justify-content: center;\n";
        $pageContent .= "            text-align: center;\n";
        $pageContent .= "            background: #fff;\n";
        $pageContent .= "            padding: 30px;\n";
        $pageContent .= "            border-radius: 10px;\n";
        $pageContent .= "            box-shadow: 0 4px 6px rgba(0,0,0,0.1);\n";
        $pageContent .= "        }\n";
        $pageContent .= "        .swiper-pagination-bullet {\n";
        $pageContent .= "            background: #3498db;\n";
        $pageContent .= "        }\n";
        $pageContent .= "    </style>\n";
        $pageContent .= "</head>\n";
        $pageContent .= "<body>\n";
        $pageContent .= "    <!-- Banner Section -->\n";
        $pageContent .= "    <div class=\"banner\">\n";
        $pageContent .= "        <img src=\"assets/resorts/<?php echo \$resort_slug . '/' . htmlspecialchars(\$resort['banner_image']); ?>\" alt=\"<?php echo htmlspecialchars(\$resort['resort_name']); ?>\">\n";
        $pageContent .= "        <h1 class=\"banner-title\"><?php echo htmlspecialchars(\$resort['banner_title'] ?? \$resort['resort_name']); ?></h1>\n";
        $pageContent .= "    </div>\n\n";
        $pageContent .= "    <div class=\"container\">\n";
        $pageContent .= "        <div class=\"row\">\n";
        $pageContent .= "            <!-- Main Content Column -->\n";
        $pageContent .= "            <div class=\"col-lg-8\">\n";
        $pageContent .= "                <div class=\"resort-details\">\n";
        $pageContent .= "                    <h2><?php echo htmlspecialchars(\$resort['resort_name']); ?></h2>\n";
        $pageContent .= "                    <div class=\"resort-description\">\n";
        $pageContent .= "                        <?php echo \$resort['resort_description']; ?>\n";
        $pageContent .= "                    </div>\n\n";
        $pageContent .= "                    <!-- Amenities Section -->\n";
        $pageContent .= "                    <?php if (!empty(\$amenities)): ?>\n";
        $pageContent .= "                    <h3>Amenities</h3>\n";
        $pageContent .= "                    <div class=\"amenities-grid\">\n";
        $pageContent .= "                        <?php foreach (\$amenities as \$amenity): ?>\n";
        $pageContent .= "                            <div class=\"amenity-item\">\n";
        $pageContent .= "                                <?php if (!empty(\$amenity['icon'])): ?>\n";
        $pageContent .= "                                    <img src=\"assets/resorts/<?php echo \$resort_slug . '/' . htmlspecialchars(\$amenity['icon']); ?>\" alt=\"<?php echo htmlspecialchars(\$amenity['name']); ?>\" class=\"amenity-icon\">\n";
        $pageContent .= "                                <?php endif; ?>\n";
        $pageContent .= "                                <p class=\"amenity-name\"><?php echo htmlspecialchars(\$amenity['name']); ?></p>\n";
        $pageContent .= "                            </div>\n";
        $pageContent .= "                        <?php endforeach; ?>\n";
        $pageContent .= "                    </div>\n";
        $pageContent .= "                    <?php endif; ?>\n\n";
        $pageContent .= "                    <!-- Rooms Section -->\n";
        $pageContent .= "                    <?php if (!empty(\$rooms)): ?>\n";
        $pageContent .= "                    <h3>Accommodations</h3>\n";
        $pageContent .= "                    <div class=\"rooms-grid\">\n";
        $pageContent .= "                        <?php foreach (\$rooms as \$room): ?>\n";
        $pageContent .= "                            <div class=\"room-card\">\n";
        $pageContent .= "                                <img src=\"assets/resorts/<?php echo \$resort_slug . '/' . htmlspecialchars(\$room['image']); ?>\" alt=\"<?php echo htmlspecialchars(\$room['name']); ?>\">\n";
        $pageContent .= "                                <h4><?php echo htmlspecialchars(\$room['name']); ?></h4>\n";
        $pageContent .= "                            </div>\n";
        $pageContent .= "                        <?php endforeach; ?>\n";
        $pageContent .= "                    </div>\n";
        $pageContent .= "                    <?php endif; ?>\n\n";
        $pageContent .= "                    <!-- Gallery Section -->\n";
        $pageContent .= "                    <?php if (!empty(\$gallery)): ?>\n";
        $pageContent .= "                    <h3>Gallery</h3>\n";
        $pageContent .= "                    <div class=\"gallery-grid\">\n";
        $pageContent .= "                        <?php foreach (\$gallery as \$image): ?>\n";
        $pageContent .= "                            <a href=\"assets/resorts/<?php echo \$resort_slug . '/' . htmlspecialchars(\$image); ?>\" class=\"gallery-item\" data-lightbox=\"resort-gallery\">\n";
        $pageContent .= "                                <img src=\"assets/resorts/<?php echo \$resort_slug . '/' . htmlspecialchars(\$image); ?>\" alt=\"Resort Gallery Image\">\n";
        $pageContent .= "                            </a>\n";
        $pageContent .= "                        <?php endforeach; ?>\n";
        $pageContent .= "                    </div>\n";
        $pageContent .= "                    <?php endif; ?>\n\n";
        $pageContent .= "                    <!-- Testimonials Section -->\n";
        $pageContent .= "                    <?php if (!empty(\$testimonials)): ?>\n";
        $pageContent .= "                    <h3>Guest Testimonials</h3>\n";
        $pageContent .= "                    <div class=\"swiper-container\">\n";
        $pageContent .= "                        <div class=\"swiper-wrapper\">\n";
        $pageContent .= "                            <?php foreach (\$testimonials as \$testimonial): ?>\n";
        $pageContent .= "                                <div class=\"swiper-slide\">\n";
        $pageContent .= "                                    <?php if (!empty(\$testimonial['content'])): ?>\n";
        $pageContent .= "                                        <p class=\"testimonial-content\">\n";
        $pageContent .= "                                            <i class=\"fas fa-quote-left\"></i>\n";
        $pageContent .= "                                            <?php echo htmlspecialchars(\$testimonial['content']); ?>\n";
        $pageContent .= "                                            <i class=\"fas fa-quote-right\"></i>\n";
        $pageContent .= "                                        </p>\n";
        $pageContent .= "                                    <?php endif; ?>\n";
        $pageContent .= "                                    <?php if (!empty(\$testimonial['name']) || !empty(\$testimonial['from'])): ?>\n";
        $pageContent .= "                                        <p class=\"testimonial-author\">\n";
        $pageContent .= "                                            <strong><?php echo htmlspecialchars(\$testimonial['name'] ?? ''); ?></strong>\n";
        $pageContent .= "                                            <span><?php echo htmlspecialchars(\$testimonial['from'] ?? ''); ?></span>\n";
        $pageContent .= "                                        </p>\n";
        $pageContent .= "                                    <?php endif; ?>\n";
        $pageContent .= "                                </div>\n";
        $pageContent .= "                            <?php endforeach; ?>\n";
        $pageContent .= "                        </div>\n";
        $pageContent .= "                        <!-- Add Pagination -->\n";
        $pageContent .= "                        <div class=\"swiper-pagination\"></div>\n";
        $pageContent .= "                    </div>\n";
        $pageContent .= "                    <?php endif; ?>\n";
        $pageContent .= "                </div>\n";
        $pageContent .= "            </div>\n\n";
        $pageContent .= "            <!-- Sidebar with Enquiry Form -->\n";
        $pageContent .= "            <div class=\"col-lg-4\">\n";
        $pageContent .= "                <div class=\"booking-form\">\n";
        $pageContent .= "                    <?php include 'destination-form.php'; ?>\n";
        $pageContent .= "                </div>\n";
        $pageContent .= "            </div>\n";
        $pageContent .= "        </div>\n";
        $pageContent .= "    </div>\n\n";
        $pageContent .= "    <!-- Scripts -->\n";
        $pageContent .= "    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js\"></script>\n";
        $pageContent .= "    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js\"></script>\n";
        $pageContent .= "    <script src=\"https://cdnjs.cloudflare.com/ajax/libs/OwlCarousel2/2.3.4/owl.carousel.min.js\"></script>\n";
        $pageContent .= "    <script src=\"https://unpkg.com/swiper/swiper-bundle.min.js\"></script>\n";
        $pageContent .= "    <script>\n";
        $pageContent .= "        \$(document).ready(function(){\n";
        $pageContent .= "            // Initialize gallery carousel\n";
        $pageContent .= "            \$('.gallery-carousel').owlCarousel({\n";
        $pageContent .= "                loop: true,\n";
        $pageContent .= "                margin: 10,\n";
        $pageContent .= "                nav: true,\n";
        $pageContent .= "                responsive: {\n";
        $pageContent .= "                    0: { items: 1 },\n";
        $pageContent .= "                    600: { items: 2 },\n";
        $pageContent .= "                    1000: { items: 3 }\n";
        $pageContent .= "                },\n";
        $pageContent .= "                autoplay: true,\n";
        $pageContent .= "                autoplayTimeout: 5000,\n";
        $pageContent .= "                autoplayHoverPause: true\n";
        $pageContent .= "            });\n\n";
        $pageContent .= "            // Initialize testimonials carousel\n";
        $pageContent .= "            \$('.testimonial-carousel').owlCarousel({\n";
        $pageContent .= "                loop: true,\n";
        $pageContent .= "                margin: 20,\n";
        $pageContent .= "                nav: true,\n";
        $pageContent .= "                dots: true,\n";
        $pageContent .= "                items: 1, // Show one testimonial at a time\n";
        $pageContent .= "                autoplay: true,\n";
        $pageContent .= "                autoplayTimeout: 5000, // 5 seconds per slide\n";
        $pageContent .= "                autoplayHoverPause: true, // Pause on hover\n";
        $pageContent .= "                smartSpeed: 800, // Smooth transition speed\n";
        $pageContent .= "                navText: ['<i class=\"fas fa-chevron-left\"></i>', '<i class=\"fas fa-chevron-right\"></i>'],\n";
        $pageContent .= "                animateOut: 'fadeOut',\n";
        $pageContent .= "                animateIn: 'fadeIn'\n";
        $pageContent .= "            });\n\n";
        $pageContent .= "            // Initialize lightbox\n";
        $pageContent .= "            lightbox.option({\n";
        $pageContent .= "                'resizeDuration': 200,\n";
        $pageContent .= "                'wrapAround': true\n";
        $pageContent .= "            });\n";
        $pageContent .= "        });\n";
        $pageContent .= "        const swiper = new Swiper('.swiper-container', {\n";
        $pageContent .= "            loop: true,\n";
        $pageContent .= "            autoplay: {\n";
        $pageContent .= "                delay: 5000,\n";
        $pageContent .= "                disableOnInteraction: false,\n";
        $pageContent .= "            },\n";
        $pageContent .= "            pagination: {\n";
        $pageContent .= "                el: '.swiper-pagination',\n";
        $pageContent .= "                clickable: true,\n";
        $pageContent .= "            },\n";
        $pageContent .= "        });\n";
        $pageContent .= "<?php require 'kfooter.php'; ?>\n";
        $pageContent .= "</body>\n";
        $pageContent .= "</html>\n";

        file_put_contents($resortPage, $pageContent);
        // Instead of redirecting, show success message and open in new tab
        echo "<script>
            window.open('" . $resortPage . "', '_blank');
            window.location.href = 'resort_list.php';
        </script>";
        exit();
    }
}
?>
