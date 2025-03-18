<?php
require 'db.php';

$stmt = $pdo->query("SELECT * FROM resorts");
$resorts = $stmt->fetchAll();
?>

<table>
    <thead>
        <tr>
            <th>Resort Name</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($resorts as $resort): ?>
        <tr>
            <td><?php echo htmlspecialchars($resort['resort_name']); ?></td>
            <td>
                <a href="edit_resort_form.php?resort_id=<?php echo $resort['id']; ?>">Edit</a>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>
