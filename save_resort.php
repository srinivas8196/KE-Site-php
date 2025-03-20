<?php
require 'db.php';

// Fetch existing resort details if editing (via GET parameter "resort_id")
$resort = null;
if (isset($_GET['resort_id']) && !empty($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Retrieve form data; if editing, fallback to existing values when needed.
    $destination_id     = $_POST['destination_id'];
    $resort_name        = isset($_POST['resort_name']) ? trim($_POST['resort_name']) : (isset($resort['resort_name']) ? $resort['resort_name'] : '');
    $resort_description = isset($_POST['resort_description']) ? trim($_POST['resort_description']) : (isset($resort['resort_description']) ? $resort['resort_description'] : '');
    $banner_title       = isset($_POST['banner_title']) ? trim($_POST['banner_title']) : (isset($resort['banner_title']) ? $resort['banner_title'] : '');

    // Active status: New resorts default to active (1); for editing, use the checkbox.
    if (isset($_POST['resort_id'])) {
        $is_active = isset($_POST['is_active']) ? 1 : 0;
    } else {
        $is_active = 1;
    }

    // Use existing slug if editing; otherwise, generate a new one.
    if (isset($_POST['resort_id']) && !empty($_POST['resort_id']) && !empty($resort['resort_slug'])) {
        $resort_slug = $resort['resort_slug'];
    } else {
        $resort_slug = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($resort_name));
        $resort_slug = preg_replace('/-+/', '-', $resort_slug);
    }

    // Define assets folder inside "assets/resorts/{resort_slug}" and landing page file name (e.g., "abc.php")
    $resortFolderPath = "assets/resorts/$resort_slug";
    $resortPage = "$resort_slug.php";

    // Create assets directories if they don't exist
    if (!file_exists($resortFolderPath)) {
        mkdir($resortFolderPath, 0777, true);
        mkdir("$resortFolderPath/amenities", 0777, true);
        mkdir("$resortFolderPath/gallery", 0777, true);
        mkdir("$resortFolderPath/rooms", 0777, true);
    }

    // --- Banner Image Upload ---
    $banner_image = '';
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == UPLOAD_ERR_OK) {
        $banner_image = "banner-" . $_FILES['banner_image']['name'];
        move_uploaded_file($_FILES['banner_image']['tmp_name'], "$resortFolderPath/$banner_image");
    } else if (isset($resort['banner_image'])) {
        $banner_image = $resort['banner_image'];
    }

    // --- Process Amenities Uploads ---
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
                // Retain existing icon if no new file is uploaded.
                if (isset($_POST['amenities'][$index]['existing_icon'])) {
                    $amenities[] = [
                        'name' => $_POST['amenities'][$index]['name'],
                        'icon' => $_POST['amenities'][$index]['existing_icon']
                    ];
                }
            }
        }
    }

    // --- Process Rooms Uploads ---
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
                if (isset($_POST['rooms'][$index]['existing_image'])) {
                    $rooms[] = [
                        'name'  => $_POST['rooms'][$index]['name'],
                        'image' => $_POST['rooms'][$index]['existing_image']
                    ];
                }
            }
        }
    }

    // --- Process Gallery Uploads ---
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

    // --- Process Testimonials ---
    $testimonials = $_POST['testimonials'];

    // Encode dynamic arrays as JSON strings for database storage.
    $amenities_json    = json_encode($amenities);
    $rooms_json        = json_encode($rooms);
    $gallery_json      = json_encode($galleryImages);
    $testimonials_json = json_encode($testimonials);

    // --- Insert or Update Database Record ---
    if (isset($_POST['resort_id']) && !empty($_POST['resort_id'])) {
        // Update existing record.
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
            $resortPage, // Store landing page file name (e.g., "abc.php")
            $_POST['resort_id']
        ]);
    } else {
        // Insert new record (new resorts default to active).
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
            $resortPage // Store landing page file name (e.g., "abc.php")
        ]);
    }

    // Fetch destinations and resorts for the mega menu
    $sql = "SELECT d.destination_name, r.resort_name, r.resort_slug 
            FROM resorts r 
            JOIN destinations d ON r.destination_id = d.id 
            WHERE r.is_active = 1 
            ORDER BY d.destination_name, r.resort_name";
    $stmt = $pdo->query($sql);

    $destinations = [];
    while ($row = $stmt->fetch()) {
        $destinations[$row['destination_name']][] = $row;
    }

    // --- Generate Landing Page File ---
    $pageContent  = "<!DOCTYPE html>\n";
    $pageContent .= "<html lang='en'>\n";
    $pageContent .= "<head>\n";
    $pageContent .= "  <meta charset='UTF-8'>\n";
    $pageContent .= "  <meta name='viewport' content='width=device-width, initial-scale=1.0'>\n";
    $pageContent .= "  <title>" . htmlspecialchars($resort_name) . "</title>\n";
    $pageContent .= "  <link rel='stylesheet' href='https://cdn.jsdelivr.net/npm/@fancyapps/ui/dist/fancybox.css'>\n";
    $pageContent .= "  <style>\n";
    $pageContent .= "    .section-spacing { margin-bottom: 2rem; }\n";
    $pageContent .= "    .banner { position: relative; }\n";
    $pageContent .= "    .banner img { width: 100%; }\n";
    $pageContent .= "    .banner .banner-text { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); color: white; }\n";
    $pageContent .= "    .row { display: flex; flex-wrap: wrap; }\n";
    $pageContent .= "    .col-md-8 { width: 66.6667%; padding: 0 15px; }\n";
    $pageContent .= "    .col-md-4 { width: 33.3333%; padding: 0 15px; }\n";
    $pageContent .= "    .container { max-width: 1140px; margin: 0 auto; padding: 0 15px; }\n";
    $pageContent .= "  </style>\n";
    $pageContent .= "</head>\n";
    $pageContent .= "<body>\n";
    $pageContent .= "  <?php define('RESORT_PAGE', true); ?>\n"; // Define the constant
    $pageContent .= "  <?php include 'kheader.php'; ?>\n"; // Include your mega menu header
    $pageContent .= "  <div class='banner'>\n";
    $pageContent .= "    <img src='assets/resorts/" . htmlspecialchars($resort_slug) . "/" . htmlspecialchars($banner_image) . "' alt='" . htmlspecialchars($resort_name) . "'>\n";
    $pageContent .= "    <div class='banner-text'><h1>" . htmlspecialchars($banner_title) . "</h1></div>\n";
    $pageContent .= "  </div>\n";
    // Main Content Row with two columns: left (resort content) and right (destination form)
    $pageContent .= "  <div class='container section-spacing'>\n";
    $pageContent .= "    <div class='row'>\n";
    // Left Column: Resort Name, Description, Amenities, Rooms, Gallery, Testimonials
    $pageContent .= "      <div class='col-md-8'>\n";
    $pageContent .= "        <h2>" . htmlspecialchars($resort_name) . "</h2>\n";
    $pageContent .= "        <p>" . nl2br(htmlspecialchars($resort_description)) . "</p>\n";
    // Amenities Section
    if (!empty($amenities)) {
        $pageContent .= "        <div class='amenities section-spacing'>\n";
        $pageContent .= "          <h3>Amenities</h3>\n";
        foreach ($amenities as $a) {
            $pageContent .= "          <div class='amenity-item'>\n";
            $pageContent .= "            <img src='assets/resorts/" . htmlspecialchars($resort_slug) . "/" . htmlspecialchars($a['icon']) . "' alt='" . htmlspecialchars($a['name']) . "'>\n";
            $pageContent .= "            <p>" . htmlspecialchars($a['name']) . "</p>\n";
            $pageContent .= "          </div>\n";
        }
        $pageContent .= "        </div>\n";
    }
    // Rooms Section
    if (!empty($rooms)) {
        $pageContent .= "        <div class='rooms section-spacing'>\n";
        $pageContent .= "          <h3>Room Details</h3>\n";
        foreach ($rooms as $r) {
            $pageContent .= "          <div class='room-item'>\n";
            $pageContent .= "            <img src='assets/resorts/" . htmlspecialchars($resort_slug) . "/" . htmlspecialchars($r['image']) . "' alt='" . htmlspecialchars($r['name']) . "'>\n";
            $pageContent .= "            <p>" . htmlspecialchars($r['name']) . "</p>\n";
            $pageContent .= "          </div>\n";
        }
        $pageContent .= "        </div>\n";
    }
    // Gallery Section
    if (!empty($galleryImages)) {
        $pageContent .= "        <div class='gallery section-spacing'>\n";
        $pageContent .= "          <h3>Gallery</h3>\n";
        foreach ($galleryImages as $img) {
            $pageContent .= "          <div class='gallery-item'>\n";
            $pageContent .= "            <a href='assets/resorts/" . htmlspecialchars($resort_slug) . "/" . htmlspecialchars($img) . "' data-fancybox='gallery'>\n";
            $pageContent .= "              <img src='assets/resorts/" . htmlspecialchars($resort_slug) . "/" . htmlspecialchars($img) . "' alt='Gallery Image'>\n";
            $pageContent .= "            </a>\n";
            $pageContent .= "          </div>\n";
        }
        $pageContent .= "        </div>\n";
    }
    // Testimonials Section
    if (!empty($testimonials)) {
        $pageContent .= "        <div class='testimonials section-spacing'>\n";
        $pageContent .= "          <h3>Testimonials</h3>\n";
        foreach ($testimonials as $t) {
            $pageContent .= "          <div class='testimonial-item'>\n";
            $pageContent .= "            <blockquote>\n";
            $pageContent .= "              <p>\"" . htmlspecialchars($t['content']) . "\"</p>\n";
            $pageContent .= "              <footer>" . htmlspecialchars($t['name']) . ", <cite>" . htmlspecialchars($t['from']) . "</cite></footer>\n";
            $pageContent .= "            </blockquote>\n";
            $pageContent .= "          </div>\n";
        }
        $pageContent .= "        </div>\n";
    }
    $pageContent .= "      </div>\n";
    // Right Column: Destination Form (Sticky)
    $pageContent .= "      <div class='col-md-4' style='position: sticky; top: 0;'>\n";
    $pageContent .= "        <?php include 'destination-form.php'; ?>\n";
    $pageContent .= "      </div>\n";
    $pageContent .= "    </div>\n"; // Close row
    $pageContent .= "  </div>\n"; // Close container
    $pageContent .= "</body>\n";
    $pageContent .= "</html>\n";

    file_put_contents($resortPage, $pageContent);

    header("Location: $resortPage");
    exit();
}
?>
