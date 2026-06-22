<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: panel.php');
    exit;
}

$user_id = (int) $_GET['id'];

$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, role, is_blocked FROM users WHERE id = :id");
$stmt->execute(['id' => $user_id]);
$user = $stmt->fetch();

if (!$user) {
    header('Location: panel.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <title>Edit user</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">
    <link rel="stylesheet" href="adminStyle.css">
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
<div class="container mt-5">
    <h2>Edit user #<?= $user['id'] ?></h2>
    <form action="update_user.php" method="POST">
        <input type="hidden" name="id" value="<?= $user['id'] ?>">

        <div class="mb-3">
            <label for="email" class="form-label">Email</label>
            <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars($user['email']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="first_name" class="form-label">First name</label>
            <input type="text" id="first_name" name="first_name" class="form-control" value="<?= htmlspecialchars($user['first_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="last_name" class="form-label">Last name</label>
            <input type="text" id="last_name" name="last_name" class="form-control" value="<?= htmlspecialchars($user['last_name']) ?>" required>
        </div>

        <div class="mb-3">
            <label for="role" class="form-label">Role</label>
            <select id="role" name="role" class="form-select" required>
                <option value="user" <?= $user['role'] === 'user' ? 'selected' : '' ?>>User</option>
                <option value="trainer" <?= $user['role'] === 'trainer' ? 'selected' : '' ?>>Trainer</option>
                <option value="admin" <?= $user['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
            </select>
        </div>

        <div class="mb-3 form-check">
            <input type="checkbox" class="form-check-input" id="is_blocked" name="is_blocked" value="1" <?= $user['is_blocked'] ? 'checked' : '' ?>>
            <label class="form-check-label" for="is_blocked">Blocked</label>
        </div>

        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="panel.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
