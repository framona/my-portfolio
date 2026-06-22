<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] == 'user') {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: panel.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM workout_plans WHERE id = :id");
$stmt->execute(['id' => $id]);
$workout = $stmt->fetch();

if (!$workout) {
    $_SESSION['error'] = "Workout plan not found.";
    header('Location: panel.php');
    exit;
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);

    if ($title === '') {
        $error = "Title cannot be empty.";
    } else {
        $update = $pdo->prepare("UPDATE workout_plans SET name = :title, description = :description WHERE id = :id");
        $update->execute([
            'title' => $title,
            'description' => $description,
            'id' => $id
        ]);
        $success = "Workout plan updated successfully.";
        $workout['title'] = $title;
        $workout['description'] = $description;
        header('Location: panel.php');
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Workout Plan</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">    <link rel="stylesheet" href="adminStyle.css">
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
    <h1>Edit workout plan</h1>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <form method="POST" action="">
        <div class="mb-3">
            <label for="title">Title</label>
            <input type="text" id="title" name="title" class="form-control" value="<?= htmlspecialchars($workout['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="description">Description</label>
            <textarea id="description" name="description" class="form-control" rows="5"><?= htmlspecialchars($workout['description']) ?></textarea>
        </div>
        <button type="submit" class="btn btn-primary">Update</button>
        <a href="panel.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
