<?php
require_once '../config/db.php';
require_once '../includes/functions.php';
protect();

$message = '';
$error = '';
$user_id = $_SESSION['user_id'];

// Fetch current user details
$stmt = $pdo->prepare("SELECT * FROM Assessment_Users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && verifyCsrf($_POST['csrf'] ?? '')) {
    $username = trim($_POST['username']);
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];

    // Verify current password
    if (password_verify($current_password, $user['password'])) {
        $updateData = [];
        $params = [];

        // Update Username
        if ($username !== $user['username']) {
            // Check if username already exists
            $check = $pdo->prepare("SELECT id FROM Assessment_Users WHERE username = ? AND id != ?");
            $check->execute([$username, $user_id]);
            if ($check->rowCount() > 0) {
                $error = "Username already taken.";
            } else {
                $updateData[] = "username = ?";
                $params[] = $username;
            }
        }

        // Update Password
        if (empty($error) && !empty($new_password)) {
            if ($new_password === $confirm_password) {
                $updateData[] = "password = ?";
                $params[] = password_hash($new_password, PASSWORD_DEFAULT);
            } else {
                $error = "New passwords do not match.";
            }
        }

        if (empty($error) && !empty($updateData)) {
            $sql = "UPDATE Assessment_Users SET " . implode(', ', $updateData) . " WHERE id = ?";
            $params[] = $user_id; // Add ID relative to WHERE clause

            // Wait, params are positional. 
            // $updateData has "username = ?", "password = ?".
            // $params needs to be [username_val, password_val, user_id_val]

            $stmt = $pdo->prepare($sql);
            if ($stmt->execute($params)) {
                $message = "Profile updated successfully.";
                // Refresh user data
                $stmt = $pdo->prepare("SELECT * FROM Assessment_Users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            } else {
                $error = "Failed to update profile.";
            }
        } elseif (empty($error) && empty($updateData)) {
            $message = "No changes made.";
        }

    } else {
        $error = "Incorrect current password.";
    }
}

include '../includes/header.php';
?>

<div style="max-width: 600px; margin: 2rem auto;">
    <h2>Profile Settings</h2>

    <?php if ($message): ?>
        <div class="alert alert-success"
            style="background: #d4edda; color: #155724; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= esc($message) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger"
            style="background: #f8d7da; color: #721c24; padding: 1rem; margin-bottom: 1rem; border-radius: 4px;">
            <?= esc($error) ?>
        </div>
    <?php endif; ?>

    <form method="POST" class="form-grid" style="display:block;">
        <!-- Block display override grid for simple vertical layout -->
        <input type="hidden" name="csrf" value="<?= generateCsrf() ?>">

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="username">Username</label>
            <input type="text" name="username" id="username" value="<?= esc($user['username']) ?>" required
                class="form-control" style="width:100%; padding:0.5rem;">
        </div>

        <hr style="margin: 2rem 0; border: 0; border-top: 1px solid #ddd;">

        <h3 style="margin-bottom: 1rem;">Change Password</h3>
        <p style="color: #666; margin-bottom: 1rem; font-size: 0.9em;">Leave blank if you don't want to change it.</p>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="current_password">Current Password (Required to make changes)</label>
            <input type="password" name="current_password" id="current_password" required class="form-control"
                style="width:100%; padding:0.5rem;">
        </div>

        <div class="form-group" style="margin-bottom: 1rem;">
            <label for="new_password">New Password</label>
            <input type="password" name="new_password" id="new_password" class="form-control"
                style="width:100%; padding:0.5rem;">
        </div>

        <div class="form-group" style="margin-bottom: 1.5rem;">
            <label for="confirm_password">Confirm New Password</label>
            <input type="password" name="confirm_password" id="confirm_password" class="form-control"
                style="width:100%; padding:0.5rem;">
        </div>

        <button type="submit" class="btn btn-primary" style="width: 100%;">Save Changes</button>
    </form>
</div>

<?php include '../includes/footer.php'; ?>