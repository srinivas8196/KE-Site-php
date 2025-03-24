<?php
session_start();
require 'db_mongo.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user'] = $user;
        // Set initial last activity time for session timeout tracking
        $_SESSION['last_activity'] = time();
        header("Location: dashboard.php");
        exit();
    } else {
        $error = "Invalid username or password.";
    }
}
?>
<?php include 'bheader.php'; ?>
<div class="container mt-5">
  <h2>Login</h2>
  <?php if(isset($error)): ?>
    <div class="alert alert-danger"><?php echo $error; ?></div>
  <?php endif; ?>
  
  <?php if(isset($_GET['timeout']) && $_GET['timeout'] == 1): ?>
    <div class="alert alert-warning">Your session has expired due to inactivity. Please log in again.</div>
  <?php endif; ?>
  <form method="post" action="login.php">
    <div class="mb-3">
      <label for="username" class="form-label">Username</label>
      <input type="text" id="username" name="username" class="form-control" required>
    </div>
    <div class="mb-3">
      <label for="password" class="form-label">Password</label>
      <input type="password" id="password" name="password" class="form-control" required>
    </div>
    <button type="submit" class="btn btn-primary">Login</button>
  </form>
</div>
<?php include 'bfooter.php'; ?>
