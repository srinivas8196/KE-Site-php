<?php
require 'db.php';
if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM resorts WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header("Location: resort_list.php");
exit();
?>
