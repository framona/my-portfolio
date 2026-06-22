<?php
require_once '../auth/auth_check.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];

$profileId = $_GET['id'] ?? $userId;

$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone, profile_image, is_trainer FROM users WHERE id = :id");
$stmt->execute(['id' => $profileId]);
$user = $stmt->fetch();


$avgRating = null;
if ($user && $user['is_trainer']) {
    $ratingStmt = $pdo->prepare("SELECT AVG(rating) as avg_rating FROM trainer_ratings WHERE trainer_id = :tid");
    $ratingStmt->execute(['tid' => $profileId]);
    $avgRating = $ratingStmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT p.id, p.name AS plan_name, p.description, c.name AS category_name
        FROM workout_plans p
        JOIN training_categories c ON p.category_id = c.id
        WHERE p.user_id = :tid
        ORDER BY p.created_at DESC
    ");
    $stmt->execute(['tid' => $profileId]);
    $plans = $stmt->fetchAll();
} else {
    $plans = [];
}

$plansStmt = $pdo->prepare("
    SELECT p.name AS plan_name, c.name AS category, p.id AS plan_id
    FROM user_workout_selections s
    JOIN workout_plans p ON s.workout_plan_id = p.id
    JOIN training_categories c ON p.category_id = c.id
    WHERE s.user_id = :uid
    ORDER BY s.selected_at DESC
");
$plansStmt->execute(['uid' => $userId]);
$userPlans = $plansStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">
    <link rel="stylesheet" href="profileStyle.css">
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
                    <li><a href="../trainer/add_workout.php">Add workout plan</a></li>
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
<div class="profile-card">
    <div class="profile">
        <?php if ($user['profile_image'] && file_exists('../' . $user['profile_image'])): ?>
            <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile Image" class="profile-image">
        <?php else: ?>
            <img src="../images/default_avatar.png" alt="Default Avatar" class="profile-image">
        <?php endif; ?>

        <h2><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></h2>
    </div>
    <div class="main-content">
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>

        <?php if ($user['is_trainer']): ?>
            <div class="label">Average rating:</div>
            <div class="rating">
                <?php
                if ($avgRating) {
                    echo number_format($avgRating, 1) . " / 5 ";
                    $fullStars = floor($avgRating);
                    $halfStar = ($avgRating - $fullStars) >= 0.5 ? true : false;
                    for ($i = 0; $i < $fullStars; $i++) echo "&#9733;";  // star
                    if ($halfStar) echo "&#9734;";
                    for ($i = $fullStars + $halfStar; $i < 5; $i++) echo "&#9734;";
                } else {
                    echo "No ratings yet.";
                }
                ?>
            </div>
        <?php endif; ?>

        <div class="d-flex justify-content-center gap-3 mt-4">
            <a href="../profile/profile_edit.php" class="btn btn-danger">Edit profile</a>
            <a href="../profile/change_password.php" class="btn btn-outline-secondary">Change password</a>
        </div>
        <?php if ($user['id'] == $userId && !$user['is_trainer']): ?>
            <hr>
            <h5>Your enrolled workout plans:</h5>
            <?php if ($userPlans): ?>
                <ul class="list-group">
                    <?php foreach ($userPlans as $plan): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong><?= htmlspecialchars($plan['plan_name']) ?></strong> –
                                <span class="text-muted"><?= htmlspecialchars($plan['category']) ?></span>
                            </div>
                            <a href="../workout/view_workout.php?plan=<?= $plan['plan_id'] ?>" class="btn btn-danger">View</a>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <p class="text-muted">You have not enrolled in any workout plans yet.</p>
            <?php endif; ?>
        <?php endif; ?>

        <?php if (!empty($plans) && $user['is_trainer']): ?>
            <hr>
            <h5>Workout plans by this trainer:</h5>
            <div class="row g-3">
                <?php foreach ($plans as $plan): ?>
                    <div class="col-md-8">
                        <div class="card">
                            <div class="card-header category-<?= strtolower(str_replace(' ', '-', $plan['category_name'])) ?>">
                                <?= htmlspecialchars($plan['plan_name']) ?>
                            </div>
                            <div class="card-body">
                                <p><strong>Category:</strong> <?= htmlspecialchars($plan['category_name']) ?></p>
                                <p><?= nl2br(htmlspecialchars($plan['description'])) ?></p>
                                <a href="../workout/view_workout.php?plan=<?= $plan['id'] ?>" class="btn btn-outline-danger btn-sm">View plan</a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php elseif ($user['is_trainer']): ?>
            <p class="text-muted">This trainer has not created any workout plans yet.</p>
        <?php endif; ?>
    </div>
</div>
</body>
</html>
