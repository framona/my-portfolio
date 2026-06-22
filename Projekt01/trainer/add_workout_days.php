<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';
if (!isset($_SESSION['role'])) {
    header('Location: ../index.php');
    exit;
}

$planId = (int)($_GET['plan'] ?? 0);
$days    = max(1, min(7, (int)($_GET['days'] ?? 1)));
$trainerId = $_SESSION['user_id'];

$exStmt = $pdo->query("SELECT id, name FROM exercises ORDER BY name");
$exercises = $exStmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Assign exercises to days</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="icon" type="image/png" href="../Images/profilepic.jpg">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="trainerStyle.css">
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
                    <li><a href="../trainer/add_workout.php">Add workout plan</a></li
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
<div class="box">
    <h2 class="mb-4">Select exercises for each day</h2>
    <form action="process_add_workout_days.php" method="POST">
        <input type="hidden" name="plan_id" value="<?= $planId ?>">
        <?php for ($d = 1; $d <= $days; $d++): ?>
            <div class="mb-3">
                <h5>Day <?= $d ?></h5>
                <?php foreach ($exercises as $ex): ?>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox"
                               name="day[<?= $d ?>][]" value="<?= $ex['id'] ?>"
                               id="day<?= $d ?>_ex<?= $ex['id'] ?>">
                        <label class="form-check-label" for="day<?= $d ?>_ex<?= $ex['id'] ?>">
                            <?= htmlspecialchars($ex['name']) ?>
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endfor; ?>
        <button type="submit" class="btn btn-danger">Save workout plan</button>
        <?php
        $redirectUrl = '../profile/profile.php';

        if (!empty($_SESSION['is_trainer']) && $_SESSION['is_trainer'] == 1) {
            $redirectUrl = 'dashboard.php';
        }
        ?>
        <a href="<?= $redirectUrl ?>" class="btn btn-secondary ms-2">Cancel</a>
    </form>
</div>
</body>
</html>
