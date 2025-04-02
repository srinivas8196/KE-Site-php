<?php
// Prevent any output before JSON response
ob_start();

// Set JSON content type
header('Content-Type: application/json');

try {
    // Initialize database connection
    $pdo = require_once 'db.php';
    if (!$pdo) {
        throw new Exception('Database connection failed');
    }

    // Get JSON data
    $json = file_get_contents('php://input');
    $data = json_decode($json, true);

    if (!isset($data['action']) || !isset($data['resort_ids']) || !is_array($data['resort_ids'])) {
        throw new Exception('Missing or invalid parameters');
    }

    $action = $data['action'];
    $resort_ids = array_map('intval', $data['resort_ids']);

    if (empty($resort_ids)) {
        throw new Exception('No resorts selected');
    }

    // Start transaction
    $pdo->beginTransaction();

    try {
        switch ($action) {
            case 'activate':
                $stmt = $pdo->prepare("UPDATE resorts SET is_active = 1 WHERE id IN (" . str_repeat('?,', count($resort_ids) - 1) . "?)");
                $stmt->execute($resort_ids);
                $message = 'Selected resorts have been activated';
                break;

            case 'deactivate':
                $stmt = $pdo->prepare("UPDATE resorts SET is_active = 0 WHERE id IN (" . str_repeat('?,', count($resort_ids) - 1) . "?)");
                $stmt->execute($resort_ids);
                $message = 'Selected resorts have been deactivated';
                break;

            case 'delete':
                // First get the resort slugs and file paths for cleanup
                $stmt = $pdo->prepare("SELECT resort_slug, file_path FROM resorts WHERE id IN (" . str_repeat('?,', count($resort_ids) - 1) . "?)");
                $stmt->execute($resort_ids);
                $resorts_to_delete = $stmt->fetchAll(PDO::FETCH_ASSOC);

                // Delete the resorts from database
                $stmt = $pdo->prepare("DELETE FROM resorts WHERE id IN (" . str_repeat('?,', count($resort_ids) - 1) . "?)");
                $stmt->execute($resort_ids);

                // Delete associated files
                foreach ($resorts_to_delete as $resort) {
                    // Delete resort page file if it exists
                    if (!empty($resort['file_path']) && file_exists($resort['file_path'])) {
                        unlink($resort['file_path']);
                    }

                    // Delete resort assets directory if it exists
                    $assets_dir = "assets/resorts/" . $resort['resort_slug'];
                    if (is_dir($assets_dir)) {
                        array_map('unlink', glob("$assets_dir/*.*"));
                        rmdir($assets_dir);
                    }
                }

                $message = 'Selected resorts have been deleted';
                break;

            default:
                throw new Exception('Invalid action specified');
        }

        // Commit transaction
        $pdo->commit();

        // Clear any output buffers
        while (ob_get_level()) {
            ob_end_clean();
        }

        // Send success response
        echo json_encode([
            'success' => true,
            'message' => $message
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $pdo->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    // Clear any output buffers
    while (ob_get_level()) {
        ob_end_clean();
    }

    // Send error response
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 