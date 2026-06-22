<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || ($_SESSION['role'] == 'user')) {
    header('Location: ../index.php');
    exit;
}

if (!isset($_GET['id'])) {
    header('Location: panel.php');
    exit;
}

$id = (int)$_GET['id'];

$stmt = $pdo->prepare("SELECT * FROM exercises WHERE id = :id");
$stmt->execute(['id' => $id]);
$exercise = $stmt->fetch();

if (!$exercise) {
    echo "Exercise not found.";
    exit;
}

$catStmt = $pdo->query("SELECT id, name FROM training_categories");
$categories = $catStmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $duration = (int)$_POST['duration_minutes'];
    $category_id = (int)$_POST['category_id'];
    $image_url = trim($_POST['image_url']);
    $video_url = trim($_POST['video_url']);

    $updateStmt = $pdo->prepare("UPDATE exercises SET name = :name, description = :description, duration_minutes = :duration, category_id = :category_id, image_url = :image_url, video_url = :video_url WHERE id = :id");
    $updateStmt->execute([
        'name' => $name,
        'description' => $description,
        'duration' => $duration,
        'category_id' => $category_id,
        'image_url' => $image_url,
        'video_url' => $video_url,
        'id' => $id,
    ]);

    header('Location: panel.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Exercise</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
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
    <h2>Edit Exercise</h2>
    <form method="POST">
        <div class="mb-3">
            <label for="name" class="form-label">Name</label>
            <input type="text" id="name" name="name" class="form-control" value="<?= htmlspecialchars($exercise['name']) ?>" required>
        </div>
        <div class="mb-3">
            <label for="description" class="form-label">Description</label>
            <textarea id="description" name="description" class="form-control" rows="4"><?= htmlspecialchars($exercise['description']) ?></textarea>
        </div>
        <div class="mb-3">
            <label for="duration_minutes" class="form-label">Duration (minutes)</label>
            <input type="number" id="duration_minutes" name="duration_minutes" class="form-control" value="<?= $exercise['duration_minutes'] ?>" required>
        </div>
        <div class="mb-3">
            <label for="category_id" class="form-label">Category</label>
            <select id="category_id" name="category_id" class="form-select" required>
                <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= $cat['id'] == $exercise['category_id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="mb-3">
            <label for="image_url" class="form-label">Image URL</label>
            <input type="text" id="image_url" name="image_url" class="form-control" value="<?= htmlspecialchars($exercise['image_url']) ?>">
        </div>
        <div class="mb-3">
            <label for="video_url" class="form-label">Video URL</label>
            <input type="text" id="video_url" name="video_url" class="form-control" value="<?= htmlspecialchars($exercise['video_url']) ?>">
        </div>
        <button type="submit" class="btn btn-primary">Save changes</button>
        <a href="panel.php" class="btn btn-secondary">Cancel</a>
    </form>
</div>
</body>
</html>
