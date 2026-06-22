<?php
session_start();
require_once '../includes/db.php';

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Missing token.";
    header('Location: ../auth/forgot_password.php');
    exit;
}

$token = $_GET['token'];
$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :token");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Invalid or expired token.";
    header('Location: ../auth/forgot_password.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Reset password</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">
    <link rel="stylesheet" href="authStyle.css">
    <script src="../Script.js" defer></script>
</head>
<body>
<nav class="navbar">
    <div class="brand-title">WorkoutPro</div>
    <a href="#" class="toggle-button">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </a>
    <div class="navbar-links">
        <ul>
            <!-- Everyone sees Home -->
            <li><a href="../index.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <!-- Guest: only Register and Login -->
                <li><a href="../auth/register.php">Register</a></li>
                <li><a href="../auth/login.php">Login</a></li>

            <?php else: ?>
                <!-- Logged-in user -->
                <?php if (!empty($_SESSION['is_trainer']) && $_SESSION['is_trainer'] == 1): ?>
                    <!-- Trainer links -->
                    <li><a href="../trainer/dashboard.php">My exercises</a></li>
                    <li><a href="../trainer/add_exercise.php">Add exercise</a></li>
                <?php else: ?>
                    <!-- Regular user links -->
                    <li><a href="../workout/workouts.php">Browse workouts</a></li>
                    <li><a href="../workout/view_workout.php">My workouts</a></li>
                    <li><a href="../trainer/add_workout.php">Add workout plan</a></li>
                <?php endif; ?>

                <!-- Common links for any logged-in user -->
                <li><a href="../profile/profile.php">Profile</a></li>
                <li><a href="../auth/logout.php">Logout</a></li>
            <?php endif; ?>

            <?php if (!empty($_SESSION['role']) && $_SESSION['role'] === 'admin'): ?>
                <!-- Admin-only link -->
                <li><a href="../admin/panel.php">Admin</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="container">
    <div class="box">
        <h2 class="text-center mb-4">Reset password</h2>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <form action="../actions/process_reset_password.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

            <div class="mb-3">
                <label>New password</label>
                <input type="password" name="new_password" class="form-control" required>
            </div>

            <div class="mb-3">
                <label>Confirm new password</label>
                <input type="password" name="confirm_password" class="form-control" required>
            </div>

            <button type="submit" class="btn btn-danger w-100">Set new password</button>
        </form>
    </div>
</div>

</body>
</html>

