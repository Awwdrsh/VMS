<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $stmt = $pdo->prepare("SELECT * FROM Assessment_Users WHERE username = ?");
    $stmt->execute([$_POST['username']]);
    $user = $stmt->fetch();
    if ($user && password_verify($_POST['password'], $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        header("Location: dashboard.php");
    }
}
?>
<?php include '../includes/header.php'; ?>
<form method="POST" style="max-width:300px; margin:auto;">
    <h2>Staff Login</h2>
    <input type="text" name="username" placeholder="Username" required>
    <input type="password" name="password" placeholder="Password" required>
    <button type="submit" class="btn btn-primary" style="width:100%">Login</button>
</form>
<?php include '../includes/footer.php'; ?>