<?php
require 'db.php';
if (isset($_GET['id'])) {
  $stmt = $pdo->prepare("DELETE FROM destinations WHERE id = ?");
  $stmt->execute([$_GET['id']]);
}
header("Location: destination_list.php");
exit();
?>
