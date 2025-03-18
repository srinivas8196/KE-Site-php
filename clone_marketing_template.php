<?php
require 'db.php';

if (!isset($_GET['id'])) {
    echo "Template ID not specified.";
    exit();
}

$templateId = $_GET['id'];
// Fetch the template to clone
$stmt = $pdo->prepare("SELECT * FROM marketing_templates WHERE id = ?");
$stmt->execute([$templateId]);
$template = $stmt->fetch();

if (!$template) {
    echo "Template not found.";
    exit();
}

// Modify the template name for the clone
$newTemplateName = "Copy of " . $template['template_name'];

// You might also want to generate a new slug if necessary (here we simply append a random suffix)
function randomString($length = 4) {
  $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
  $result = '';
  for ($i = 0; $i < $length; $i++) {
     $result .= $characters[rand(0, strlen($characters) - 1)];
  }
  return $result;
}
$newSlug = preg_replace('/[^a-zA-Z0-9]/', '-', strtolower($template['resort_for_template'])) . randomString();

$stmtClone = $pdo->prepare("INSERT INTO marketing_templates (template_name, resort_for_template, nights, resort_banner, about_image, about_content, amenities, attractions, gallery, testimonials, button_label, campaign_title, campaign_subtitle) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
$stmtClone->execute([
    $newTemplateName,
    $template['resort_for_template'],
    $template['nights'],
    $template['resort_banner'],
    $template['about_image'],
    $template['about_content'],
    $template['amenities'],
    $template['attractions'],
    $template['gallery'],
    $template['testimonials'],
    $template['button_label'],
    $template['campaign_title'],
    $template['campaign_subtitle']
]);

header("Location: marketing_template_list.php");
exit();
?>
