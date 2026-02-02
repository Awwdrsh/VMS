<!DOCTYPE html>
<html>

<head>
    <title>VMS - Visitor System</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>

<body>
    <div class="container">
        <?php if (isset($_SESSION['user_id'])): ?>
            <nav>
                <strong>Visitor Management</strong>
                <div>
                    <a href="dashboard.php">Dashboard</a>
                    <a href="reports.php">Reports</a>
                    <a href="visitor.php">New Visitor</a>
                    <a href="profile.php">Profile</a>
                    <a href="handler.php?action=logout">Logout</a>
                </div>
            </nav>
        <?php endif; ?>