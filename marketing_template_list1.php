<?php
require 'db.php';

// Query marketing templates along with a comma-separated list of campaign names using each template
$query = "SELECT m.*, 
                 GROUP_CONCAT(c.campaign_name SEPARATOR ', ') AS used_in 
          FROM marketing_templates m 
          LEFT JOIN campaigns c ON m.id = c.marketing_template_id 
          GROUP BY m.id 
          ORDER BY m.template_name";
$stmt = $pdo->query($query);
$templates = $stmt->fetchAll();
?>
<?php include 'kheader.php'; ?>
<div class="container mt-5">
  <!-- Breadcrumb -->
  <nav aria-label="breadcrumb">
    <ol class="breadcrumb">
      <li class="breadcrumb-item"><a href="dashboard.php">Dashboard</a></li>
      <li class="breadcrumb-item active" aria-current="page">Marketing Templates</li>
    </ol>
  </nav>
  <h2>Marketing Templates</h2>
  <a href="create_marketing_template.php" class="btn btn-primary mb-3">Create New Template</a>
  <?php if(count($templates) > 0): ?>
  <table class="table table-bordered">
    <thead>
      <tr>
        <th>Template Name</th>
        <th>Resort</th>
        <th>Nights</th>
        <th>Button Label</th>
        <th>Used In Campaigns</th>
        <th>Actions</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($templates as $temp): ?>
      <tr>
        <td><?php echo htmlspecialchars($temp['template_name']); ?></td>
        <td><?php echo htmlspecialchars($temp['resort_for_template']); ?></td>
        <td><?php echo $temp['nights']; ?></td>
        <td><?php echo $temp['button_label']; ?></td>
        <td><?php echo $temp['used_in'] ? htmlspecialchars($temp['used_in']) : 'None'; ?></td>
        <td>
          <a href="edit_marketing_template.php?id=<?php echo $temp['id']; ?>" class="btn btn-sm btn-secondary">Edit</a>
          <a href="delete_marketing_template.php?id=<?php echo $temp['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this template?');">Delete</a>
          <a href="clone_marketing_template.php?id=<?php echo $temp['id']; ?>" class="btn btn-sm btn-warning">Clone</a>
        </td>
      </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <?php else: ?>
    <p>No marketing templates found.</p>
    <a href="create_marketing_template.php" class="btn btn-primary">Create New Template</a>
  <?php endif; ?>
</div>
<?php include 'kfooter.php'; ?>
