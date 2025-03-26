<?php
session_start();
require_once 'db.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
       

        $username = $_POST['username']; // Changed from $_POST['email'] to $_POST['username']
        $password = $_POST['password'];

        // Fetch user from MySQL using username
        $stmt = $pdo->prepare("SELECT * FROM users WHERE username = :username"); // Changed WHERE clause to use username
        $stmt->execute(['username' => $username]); // Changed parameter to 'username'
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        // Verify password
        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'username' => $user['username'], // You might want to store username instead of email
                'role' => $user['role'],
                'user_type' => $user['user_type']
            ];
            header('Location: dashboard.php');
            exit;
        } else {
            $error = 'Invalid credentials';
        }
    } catch (Exception $e) {
        $error = 'Login error occurred';
    }
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/css/bootstrap.min.css">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Admin Login</div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="post">
                            <div class="mb-3">
                                <label>username</label>
                                <input type="text" name="username" class="form-control" required>
                            </div>
                            <div class="mb-3">
                                <label>Password</label>
                                <input type="password" name="password" class="form-control" required>
                            </div>
                            <button type="submit" class="btn btn-primary">Login</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
