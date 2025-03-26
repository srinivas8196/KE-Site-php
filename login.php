<?php
session_start();
<<<<<<< HEAD
require 'db.php';
=======
require_once __DIR__ . '/vendor/autoload.php';
use Database\SupabaseConnection;
>>>>>>> 4a5601790339d4600a7b11e571b96a5533d4d839

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $supabase = SupabaseConnection::getInstance();
        $user = $supabase->verifyUser($_POST['email'], $_POST['password']);
        
        if ($user) {
            $_SESSION['user'] = [
                'id' => $user['id'],
                'email' => $user['email'],
                'role' => $user['role']
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
                                <label>Email</label>
                                <input type="email" name="email" class="form-control" required>
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
