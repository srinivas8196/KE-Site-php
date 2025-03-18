<?php
require 'db.php';

// Fetch existing resort details if editing
$resort = null;
if (isset($_GET['resort_id']) && !empty($_GET['resort_id'])) {
    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['resort_id']]);
    $resort = $stmt->fetch();
    $amenities = json_decode($resort['amenities'], true);
    $rooms = json_decode($resort['room_details'], true);
    $gallery = json_decode($resort['gallery'], true);
    $testimonials = json_decode($resort['testimonials'], true);
}
?>

<form action="save_resort.php" method="POST" enctype="multipart/form-data">
    <input type="hidden" name="resort_id" value="<?php echo $resort['id'] ?? ''; ?>">
    <div>
        <label for="destination_id">Destination ID:</label>
        <input type="text" id="destination_id" name="destination_id" value="<?php echo $resort['destination_id'] ?? ''; ?>">
    </div>
    <div>
        <label for="resort_name">Resort Name:</label>
        <input type="text" id="resort_name" name="resort_name" value="<?php echo $resort['resort_name'] ?? ''; ?>">
    </div>
    <div>
        <label for="resort_description">Resort Description:</label>
        <textarea id="resort_description" name="resort_description"><?php echo $resort['resort_description'] ?? ''; ?></textarea>
    </div>
    <div>
        <label for="banner_title">Banner Title:</label>
        <input type="text" id="banner_title" name="banner_title" value="<?php echo $resort['banner_title'] ?? ''; ?>">
    </div>

    <!-- Amenities Section -->
    <div>
        <h3>Amenities</h3>
        <?php if (isset($amenities) && is_array($amenities)): ?>
            <?php foreach ($amenities as $index => $amenity): ?>
                <div id="amenity_<?php echo $index; ?>">
                    <label for="amenity_name_<?php echo $index; ?>">Name:</label>
                    <input type="text" id="amenity_name_<?php echo $index; ?>" name="amenities[<?php echo $index; ?>][name]" value="<?php echo $amenity['name']; ?>">
                    <label for="amenity_icon_<?php echo $index; ?>">Icon:</label>
                    <input type="file" id="amenity_icon_<?php echo $index; ?>" name="amenities[<?php echo $index; ?>][icon]">
                    <img src="assets/destinations/<?php echo $amenity['icon']; ?>" alt="<?php echo $amenity['name']; ?>" style="max-width:50px;">
                    <button type="button" onclick="deleteAmenity(<?php echo $index; ?>)">Delete</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Room Details Section -->
    <div>
        <h3>Room Details</h3>
        <?php if (isset($rooms) && is_array($rooms)): ?>
            <?php foreach ($rooms as $index => $room): ?>
                <div id="room_<?php echo $index; ?>">
                    <label for="room_name_<?php echo $index; ?>">Name:</label>
                    <input type="text" id="room_name_<?php echo $index; ?>" name="rooms[<?php echo $index; ?>][name]" value="<?php echo $room['name']; ?>">
                    <label for="room_image_<?php echo $index; ?>">Image:</label>
                    <input type="file" id="room_image_<?php echo $index; ?>" name="rooms[<?php echo $index; ?>][image]">
                    <img src="assets/destinations/<?php echo $room['image']; ?>" alt="<?php echo $room['name']; ?>" style="max-width:200px;">
                    <button type="button" onclick="deleteRoom(<?php echo $index; ?>)">Delete</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Gallery Section -->
    <div>
        <h3>Gallery</h3>
        <?php if (isset($gallery) && is_array($gallery)): ?>
            <?php foreach ($gallery as $index => $image): ?>
                <div id="gallery_<?php echo $index; ?>">
                    <img src="assets/destinations/<?php echo $image; ?>" alt="Gallery Image" style="max-width:200px;">
                    <button type="button" onclick="deleteGalleryImage(<?php echo $index; ?>)">Delete</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Testimonials Section -->
    <div>
        <h3>Testimonials</h3>
        <?php if (isset($testimonials) && is_array($testimonials)): ?>
            <?php foreach ($testimonials as $index => $testimonial): ?>
                <div id="testimonial_<?php echo $index; ?>">
                    <label for="testimonial_name_<?php echo $index; ?>">Name:</label>
                    <input type="text" id="testimonial_name_<?php echo $index; ?>" name="testimonials[<?php echo $index; ?>][name]" value="<?php echo $testimonial['name']; ?>">
                    <label for="testimonial_from_<?php echo $index; ?>">From:</label>
                    <input type="text" id="testimonial_from_<?php echo $index; ?>" name="testimonials[<?php echo $index; ?>][from]" value="<?php echo $testimonial['from']; ?>">
                    <label for="testimonial_content_<?php echo $index; ?>">Content:</label>
                    <textarea id="testimonial_content_<?php echo $index; ?>" name="testimonials[<?php echo $index; ?>][content]"><?php echo $testimonial['content']; ?></textarea>
                    <button type="button" onclick="deleteTestimonial(<?php echo $index; ?>)">Delete</button>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <button type="submit"><?php echo isset($resort) ? 'Update Resort' : 'Save Resort'; ?></button>
</form>

<script>
function deleteAmenity(index) {
    var resortId = <?php echo $resort['id'] ?? 'null'; ?>;
    if (resortId) {
        fetch('delete_resort_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resort_id: resortId,
                item_type: 'amenities',
                item_index: index
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('amenity_' + index).remove();
            } else {
                alert(data.message);
            }
        });
    } else {
        document.getElementById('amenity_' + index).remove();
    }
}

function deleteRoom(index) {
    var resortId = <?php echo $resort['id'] ?? 'null'; ?>;
    if (resortId) {
        fetch('delete_resort_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resort_id: resortId,
                item_type: 'room_details',
                item_index: index
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('room_' + index).remove();
            } else {
                alert(data.message);
            }
        });
    } else {
        document.getElementById('room_' + index).remove();
    }
}

function deleteGalleryImage(index) {
    var resortId = <?php echo $resort['id'] ?? 'null'; ?>;
    if (resortId) {
        fetch('delete_resort_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resort_id: resortId,
                item_type: 'gallery',
                item_index: index
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('gallery_' + index).remove();
            } else {
                alert(data.message);
            }
        });
    } else {
        document.getElementById('gallery_' + index).remove();
    }
}

function deleteTestimonial(index) {
    var resortId = <?php echo $resort['id'] ?? 'null'; ?>;
    if (resortId) {
        fetch('delete_resort_item.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                resort_id: resortId,
                item_type: 'testimonials',
                item_index: index
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                document.getElementById('testimonial_' + index).remove();
            } else {
                alert(data.message);
            }
        });
    } else {
        document.getElementById('testimonial_' + index).remove();
    }
}
</script>
