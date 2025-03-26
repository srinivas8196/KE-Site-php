<?php
require 'db.php';
if(isset($_GET['id'])){
    $stmt = $pdo->prepare("DELETE FROM marketing_templates WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}
header("Location: marketing_template_list.php");
exit();
?>
