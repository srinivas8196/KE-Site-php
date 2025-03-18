<?php
session_start();
if (!isset($_SESSION['user']) || $_SESSION['user']['user_type'] !== 'super_admin') {
    header("Location: dashboard.php");
    exit();
}

require 'db.php';

// Fetch all users
$stmt = $pdo->query("SELECT id, username, email, user_type FROM users");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Update user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_user'])) {
    $id = $_POST['id'];
    $username = $_POST['username'];
    $email = $_POST['email'];
    $user_type = $_POST['user_type'];

    $stmt = $pdo->prepare("UPDATE users SET username=?, email=?, user_type=? WHERE id=?");
    $stmt->execute([$username, $email, $user_type, $id]);

    $_SESSION['success'] = "User updated successfully!";
    header("Location: edit_users.php");
    exit();
}

// Delete user
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM users WHERE id=?");
    $stmt->execute([$id]);

    $_SESSION['success'] = "User deleted successfully!";
    header("Location: edit_users.php");
    exit();
}
?>

<?php include 'bheader.php'; ?>
<!-- Include Tailwind CSS -->
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
<div class="container mx-auto p-8">
    <h2 class="text-3xl font-bold mb-6">Manage Users</h2>

    <?php if (isset($_SESSION['success'])): ?>
        <p class="bg-green-500 text-white p-3 rounded mb-4"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php endif; ?>

    <table class="w-full border-collapse border border-gray-300 shadow-lg">
        <thead>
            <tr class="bg-gray-200">
                <th class="border px-4 py-2">ID</th>
                <th class="border px-4 py-2">Username</th>
                <th class="border px-4 py-2">Email</th>
                <th class="border px-4 py-2">User Type</th>
                <th class="border px-4 py-2">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
                <tr class="hover:bg-gray-100">
                    <td class="border px-4 py-2 text-center"><?php echo $user['id']; ?></td>
                    <td class="border px-4 py-2 text-center"><?php echo $user['username']; ?></td>
                    <td class="border px-4 py-2 text-center"><?php echo $user['email']; ?></td>
                    <td class="border px-4 py-2 text-center"><?php echo $user['user_type']; ?></td>
                    <td class="border px-4 py-2 text-center">
                        <button onclick="editUser(<?php echo $user['id']; ?>, '<?php echo $user['username']; ?>', '<?php echo $user['email']; ?>', '<?php echo $user['user_type']; ?>')" class="bg-blue-500 text-white px-3 py-1 rounded">Edit</button>
                        <a href="?delete=<?php echo $user['id']; ?>" class="bg-red-500 text-white px-3 py-1 rounded" onclick="return confirm('Are you sure?');">Delete</a>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Edit User Modal -->
    <div id="editModal" class="fixed inset-0 bg-gray-800 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg w-1/3">
            <h3 class="text-2xl font-bold mb-4">Edit User</h3>
            <form method="POST">
                <input type="hidden" name="id" id="editUserId">
                <label class="block mb-2">Username</label>
                <input type="text" name="username" id="editUsername" class="w-full p-2 border rounded mb-3">
                <label class="block mb-2">Email</label>
                <input type="email" name="email" id="editEmail" class="w-full p-2 border rounded mb-3">
                <label class="block mb-2">User Type</label>
                <select name="user_type" id="editUserType" class="w-full p-2 border rounded mb-3">
                    <option value="user">User</option>
                    <option value="admin">Admin</option>
                    <option value="super_admin">Super Admin</option>
                </select>
                <div class="flex justify-between">
                    <button type="submit" name="update_user" class="bg-green-500 text-white px-4 py-2 rounded">Update</button>
                    <button type="button" onclick="closeModal()" class="bg-gray-500 text-white px-4 py-2 rounded">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function editUser(id, username, email, userType) {
            document.getElementById('editUserId').value = id;
            document.getElementById('editUsername').value = username;
            document.getElementById('editEmail').value = email;
            document.getElementById('editUserType').value = userType;
            document.getElementById('editModal').classList.remove('hidden');
        }

        function closeModal() {
            document.getElementById('editModal').classList.add('hidden');
        }
    </script>
</div>
<?php include 'bfooter.php'; ?>
