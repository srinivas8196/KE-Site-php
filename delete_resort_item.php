<?php
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $resort_id = $_POST['resort_id'];
    $item_type = $_POST['item_type'];
    $item_index = $_POST['item_index'];

    $stmt = $pdo->prepare("SELECT * FROM resorts WHERE id = ?");
    $stmt->execute([$resort_id]);
    $resort = $stmt->fetch();

    if ($resort) {
        $items = json_decode($resort[$item_type], true);
        if (isset($items[$item_index])) {
            unset($items[$item_index]);
            $items = array_values($items); // Re-index the array
            $items_json = json_encode($items);

            $stmt = $pdo->prepare("UPDATE resorts SET $item_type = ? WHERE id = ?");
            $stmt->execute([$items_json, $resort_id]);

            echo json_encode(['status' => 'success']);
        } else {
            echo json_encode(['status' => 'error', 'message' => 'Item not found']);
        }
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Resort not found']);
    }
}
?>
