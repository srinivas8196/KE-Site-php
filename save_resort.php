<?php
session_start();
if (!isset($_SESSION['user'])) {
    header('Location: login.php');
    exit();
}

use Models\Resort;
use Database\SupabaseConnection;
require_once __DIR__ . '/vendor/autoload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $supabase = SupabaseConnection::getClient();
        $resortSlug = strtolower(str_replace(' ', '-', $_POST['resort_name']));
        
        // Base resort data
        $resortData = [
            'resort_name' => $_POST['resort_name'],
            'resort_description' => $_POST['resort_description'],
            'banner_title' => $_POST['banner_title'],
            'destination_id' => $_POST['destination_id'],
            'is_active' => isset($_POST['is_active']) ? 1 : 0,
            'is_partner' => $_POST['resort_type'] === 'partner' ? 1 : 0,
            'resort_slug' => $resortSlug,
            'updated_at' => date('Y-m-d H:i:s')
        ];

        // Handle banner image upload
        if (!empty($_FILES['banner_image']['name'])) {
            $bannerFile = $_FILES['banner_image'];
            $bannerFileName = time() . '_' . basename($bannerFile['name']);
            $storagePath = "resorts/{$resortSlug}/banner/{$bannerFileName}";
            
            // Upload to Supabase Storage
            $result = $supabase
                ->storage()
                ->from('resort-assets')
                ->upload($storagePath, file_get_contents($bannerFile['tmp_name']));

            if ($result->isSuccess()) {
                $resortData['banner_image'] = $storagePath;
            }
        }

        // Handle amenities
        if (!empty($_POST['amenities'])) {
            $amenities = [];
            foreach ($_POST['amenities'] as $index => $amenity) {
                $amenityData = ['name' => $amenity['name']];

                // Handle amenity icon upload
                if (!empty($_FILES['amenities']['name'][$index]['icon'])) {
                    $iconFile = [
                        'name' => $_FILES['amenities']['name'][$index]['icon'],
                        'tmp_name' => $_FILES['amenities']['tmp_name'][$index]['icon']
                    ];
                    $iconFileName = time() . '_' . basename($iconFile['name']);
                    $storagePath = "resorts/{$resortSlug}/amenities/{$iconFileName}";
                    
                    $result = $supabase
                        ->storage()
                        ->from('resort-assets')
                        ->upload($storagePath, file_get_contents($iconFile['tmp_name']));

                    if ($result->isSuccess()) {
                        $amenityData['icon'] = $storagePath;
                    }
                } elseif (!empty($amenity['existing_icon'])) {
                    $amenityData['icon'] = $amenity['existing_icon'];
                }

                $amenities[] = $amenityData;
            }
            $resortData['amenities'] = json_encode($amenities);
        }

        // Handle rooms
        if (!empty($_POST['rooms'])) {
            $rooms = [];
            foreach ($_POST['rooms'] as $index => $room) {
                $roomData = ['name' => $room['name']];

                if (!empty($_FILES['rooms']['name'][$index]['image'])) {
                    $roomFile = [
                        'name' => $_FILES['rooms']['name'][$index]['image'],
                        'tmp_name' => $_FILES['rooms']['tmp_name'][$index]['image']
                    ];
                    $roomFileName = time() . '_' . basename($roomFile['name']);
                    $storagePath = "resorts/{$resortSlug}/rooms/{$roomFileName}";
                    
                    $result = $supabase
                        ->storage()
                        ->from('resort-assets')
                        ->upload($storagePath, file_get_contents($roomFile['tmp_name']));

                    if ($result->isSuccess()) {
                        $roomData['image'] = $storagePath;
                    }
                } elseif (!empty($room['existing_image'])) {
                    $roomData['image'] = $room['existing_image'];
                }

                $rooms[] = $roomData;
            }
            $resortData['room_details'] = json_encode($rooms);
        }

        // Handle gallery images
        if (!empty($_FILES['gallery']['name'][0])) {
            $gallery = [];
            $fileCount = count($_FILES['gallery']['name']);
            
            for ($i = 0; $i < $fileCount; $i++) {
                if ($_FILES['gallery']['error'][$i] === UPLOAD_ERR_OK) {
                    $galleryFile = [
                        'name' => $_FILES['gallery']['name'][$i],
                        'tmp_name' => $_FILES['gallery']['tmp_name'][$i]
                    ];
                    $galleryFileName = time() . '_' . basename($galleryFile['name']);
                    $storagePath = "resorts/{$resortSlug}/gallery/{$galleryFileName}";
                    
                    $result = $supabase
                        ->storage()
                        ->from('resort-assets')
                        ->upload($storagePath, file_get_contents($galleryFile['tmp_name']));

                    if ($result->isSuccess()) {
                        $gallery[] = $storagePath;
                    }
                }
            }
            
            if (!empty($gallery)) {
                // Merge with existing gallery images if updating
                if (isset($_POST['resort_id']) && !empty($_POST['existing_gallery'])) {
                    $existingGallery = json_decode($_POST['existing_gallery'], true);
                    $gallery = array_merge($existingGallery, $gallery);
                }
                $resortData['gallery'] = json_encode($gallery);
            }
        }

        // Handle testimonials
        if (!empty($_POST['testimonials'])) {
            $testimonials = [];
            foreach ($_POST['testimonials'] as $testimonial) {
                if (!empty($testimonial['name']) && !empty($testimonial['content'])) {
                    $testimonials[] = [
                        'name' => $testimonial['name'],
                        'from' => $testimonial['from'],
                        'content' => $testimonial['content']
                    ];
                }
            }
            $resortData['testimonials'] = json_encode($testimonials);
        }

        // Save or update resort
        if (isset($_POST['resort_id'])) {
            $result = Resort::updateRecord($_POST['resort_id'], $resortData);
            $successMessage = 'Resort updated successfully';
            $resortId = $_POST['resort_id'];
        } else {
            $result = Resort::create($resortData);
            $successMessage = 'Resort created successfully';
            $resortId = $result->id;
        }

        // Generate resort page
        $pageFile = generateResortPage($resortId);
        if ($pageFile) {
            $resortData['file_path'] = $pageFile;
            Resort::updateRecord($resortId, ['file_path' => $pageFile]);
        }

        $_SESSION['success'] = $successMessage;
        header('Location: resort_list.php');
        exit();

    } catch (Exception $e) {
        $_SESSION['error'] = 'Error saving resort: ' . $e->getMessage();
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }
}

header('Location: resort_list.php');
exit();
