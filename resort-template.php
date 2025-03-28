<?php
require 'db.php';

// Handle existing resort data
$resort = null;
if (isset($_GET['resort_id']) && !empty($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $destination_id = isset($_POST['destination_id']) ? intval($_POST['destination_id']) : 0;
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
    }  

    $pageContent = <<<HTML
require 'db.php';
require 'kheader.php';

\$resort_slug = basename(\$_SERVER['PHP_SELF'], '.php');
\$stmt = \$pdo->prepare("SELECT r.*, d.destination_name
                       FROM resorts r
                       LEFT JOIN destinations d ON r.destination_id = d.id
                       WHERE r.resort_slug = ?");
\$stmt->execute([\$resort_slug]);
\$resort = \$stmt->fetch();

if (!\$resort) {
    header('Location: 404.php');
    exit();
}

\$amenities = json_decode(\$resort['amenities'], true) ?? [];
\$rooms = json_decode(\$resort['room_details'], true) ?? [];
\$gallery = json_decode(\$resort['gallery'], true) ?? [];
\$testimonials = json_decode(\$resort['testimonials'], true) ?? [];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(\$resort['resort_name']) ?> - Luxury Resort</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/swiper@8/swiper-bundle.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css">
    <style>
        :root {
            --primary: #2d3748;
            --secondary: #4a5568;
            --accent: #ecc94b;
            --light: #f7fafc;
            --dark: #1a202c;
        }

        .hero-section {
            height: 70vh;
            position: relative;
            overflow: hidden;
            margin-bottom: 4rem;
        }

        .hero-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            filter: brightness(0.7);
        }

        .hero-content {
            position: absolute;
            bottom: 2rem;
            left: 2rem;
            color: white;
            max-width: 800px;
        }

        .sticky-form {
            position: sticky;
            top: 120px;
            background: white;
            border-radius: 12px;
            box-shadow: 0 10px 15px -3px rgba(0,0,0,0.1);
            padding: 2rem;
            margin-top: 2rem;
            z-index: 10;
        }

        .section-heading {
            font-size: 2.25rem;
            position: relative;
            padding-left: 1.5rem;
            margin: 3rem 0;
            color: var(--primary);
        }

        .section-heading::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            transform: translateY(-50%);
            width: 4px;
            height: 60%;
            background: var(--accent);
        }

        .amenities-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            padding: 2rem 0;
        }

        .amenity-card {
            text-align: center;
            padding: 1.5rem;
            background: white;
            border-radius: 8px;
            transition: transform 0.3s ease;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
        }

        .amenity-card:hover {
            transform: translateY(-5px);
        }

        .room-card {
            border-radius: 12px;
            overflow: hidden;
            box-shadow: 0 4px 6px -1px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 1rem;
            padding: 2rem 0;
        }

        .testimonial-slider {
            padding: 2rem 0;
        }

        @media (max-width: 768px) {
            .sticky-form {
                position: static;
                margin-top: 0;
            }
            
            .hero-content {
                left: 1rem;
                bottom: 1rem;
            }
            
            .section-heading {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <section class="hero-section">
        <img src="assets/resorts/<?= \$resort_slug ?>/<?= htmlspecialchars(\$resort['banner_image']) ?>"
             alt="<?= htmlspecialchars(\$resort['resort_name']) ?>"
             class="hero-image">
        <div class="hero-content">
            <h1 class="text-4xl md:text-5xl font-bold mb-4"><?= htmlspecialchars(\$resort['banner_title'] ?? \$resort['resort_name']) ?></h1>
        </div>
    </section>

    <div class="container mx-auto px-4">
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
            <main class="lg:col-span-3">
                <section class="mb-16">
                    <h2 class="section-heading">About the Resort</h2>
                    <div class="prose max-w-none text-lg text-gray-600">
                        <?= \$resort['resort_description'] ?>
                    </div>
                </section>

                <?php if (!empty(\$amenities)): ?>
                <section class="mb-16">
                    <h2 class="section-heading">Amenities & Services</h2>
                    <div class="amenities-grid">
                        <?php foreach (\$amenities as \$amenity): ?>
                        <div class="amenity-card">
                            <img src="assets/resorts/<?= \$resort_slug ?>/<?= htmlspecialchars(\$amenity['icon']) ?>"
                                 alt="<?= htmlspecialchars(\$amenity['name']) ?>"
                                 class="w-16 h-16 mx-auto mb-4 object-contain">
                            <h3 class="font-semibold text-lg text-gray-700"><?= htmlspecialchars(\$amenity['name']) ?></h3>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty(\$rooms)): ?>
                <section class="mb-16">
                    <h2 class="section-heading">Accommodations</h2>
                    <div class="space-y-8">
                        <?php foreach (\$rooms as \$room): ?>
                        <div class="room-card">
                            <img src="assets/resorts/<?= \$resort_slug ?>/<?= htmlspecialchars(\$room['image']) ?>"
                                 alt="<?= htmlspecialchars(\$room['name']) ?>"
                                 class="w-full h-64 object-cover">
                            <div class="p-6">
                                <h3 class="text-2xl font-bold mb-2 text-gray-800"><?= htmlspecialchars(\$room['name']) ?></h3>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty(\$gallery)): ?>
                <section class="mb-16">
                    <h2 class="section-heading">Gallery</h2>
                    <div class="gallery-grid">
                        <?php foreach (\$gallery as \$image): ?>
                        <a href="assets/resorts/<?= \$resort_slug ?>/<?= htmlspecialchars(\$image) ?>"
                           data-lightbox="gallery"
                           class="block rounded-lg overflow-hidden aspect-square transition-transform hover:scale-105">
                            <img src="assets/resorts/<?= \$resort_slug ?>/<?= htmlspecialchars(\$image) ?>"
                                 alt="Gallery image"
                                 class="w-full h-full object-cover">
                        </a>
                        <?php endforeach; ?>
                    </div>
                </section>
                <?php endif; ?>

                <?php if (!empty(\$testimonials)): ?>
                <section class="mb-16">
                    <h2 class="section-heading">Guest Experiences</h2>
                    <div class="testimonial-slider">
                        <div class="swiper-container">
                            <div class="swiper-wrapper">
                                <?php foreach (\$testimonials as \$testimonial): ?>
                                <div class="swiper-slide">
                                    <div class="bg-white p-8 rounded-xl shadow-lg">
                                        <p class="text-xl italic mb-4 text-gray-600">"<?= htmlspecialchars(\$testimonial['content']) ?>"</p>
                                        <div class="font-semibold">
                                            <p class="text-lg text-gray-800"><?= htmlspecialchars(\$testimonial['name']) ?></p>
                                            <p class="text-gray-600"><?= htmlspecialchars(\$testimonial['from']) ?></p>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="swiper-pagination"></div>
                        </div>
                    </div>
                </section>
                <?php endif; ?>
            </main>

            <!-- Sticky Booking Form -->
            <aside class="lg:col-span-1">
                <div class="sticky-form">
                    <h3 class="text-2xl font-bold mb-6 text-center text-gray-800">Enquire Now</h3>
                    <?php include 'destination-form.php'; ?>
                </div>
            </aside>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://unpkg.com/swiper@8/swiper-bundle.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
    <script>
        // Initialize Swiper
        new Swiper('.swiper-container', {
            loop: true,
            autoplay: {
                delay: 5000,
            },
            pagination: {
                el: '.swiper-pagination',
                clickable: true
            },
            breakpoints: {
                640: {
                    slidesPerView: 1,
                    spaceBetween: 20
                },
                1024: {
                    slidesPerView: 1,
                    spaceBetween: 30
                }
            }
        });

        // Initialize lightbox
        lightbox.option({
            'resizeDuration': 200,
            'wrapAround': true,
            'albumLabel': 'Image %1 of %2'
        });
    </script>
    <?php require 'kfooter.php'; ?>
</body>
</html>
HTML;

    file_put_contents($resortPage, $pageContent);
    
    echo "<script>
        window.open('" . $resortPage . "', '_blank');
        window.location.href = 'resort_list.php';
    </script>";
    exit();
}
?>
