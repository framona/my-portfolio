<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['is_trainer']) || $_SESSION['is_trainer'] != 1) {
    header('Location: ../index.php');
    exit;
}

$trainerId = $_SESSION['user_id'];

$stmt = $pdo->prepare("
    SELECT e.*, c.name AS category_name 
    FROM exercises e 
    INNER JOIN training_categories c ON e.category_id = c.id 
    WHERE e.trainer_id = :trainer_id
    ORDER BY e.created_at DESC
");
$stmt->execute(['trainer_id' => $trainerId]);
$exercises = $stmt->fetchAll();

$stmt2 = $pdo->query("SELECT id, name, description, created_at 
                          FROM workout_plans 
                          ORDER BY created_at DESC");

$workouts = $stmt2->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Trainer dashboard</title>
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
<div class="container mt-5">
    <h2 class="mb-4">Trainer dashboard</h2>

    <a href="../trainer/add_exercise.php" class="btn btn-danger">Add new exercise</a><br><br>

    <?php if (count($exercises) > 0): ?>
        <div class="table-responsive">
            <table class="table table-white table-bordered table-hover">
                <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Duration</th>
                    <th>Created</th>
                    <th>Video</th>
                    <th>Image</th>
                    <th>Category</th>
                    <th>Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($exercises as $exercise): ?>
                    <tr>
                        <td><?= htmlspecialchars($exercise['name']) ?></td>
                        <td><?= htmlspecialchars($exercise['description']) ?></td>
                        <td><?= htmlspecialchars($exercise['duration_minutes']) ?> min</td>
                        <td><?= date('Y-m-d', strtotime($exercise['created_at'])) ?></td>
                        <td>
                            <?php if ($exercise['video_url']): ?>
                                <a href="<?= htmlspecialchars($exercise['video_url']) ?>" target="_blank" class="btn btn-outline-light btn-sm">YouTube</a>
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($exercise['image_url'])): ?>
                                <img src="../<?= htmlspecialchars($exercise['image_url']) ?>"
                                     alt="Exercise image"
                                     style="max-height:80px; max-width:100px;" />
                            <?php else: ?>
                                -
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($exercise['category_name']) ?></td>
                        <td>
                            <a href="../admin/edit_exercise.php?id=<?= $exercise['id'] ?>" class="btn btn-primary btn-sm mb-1">Edit</a><br><br>
                            <form action="../admin/delete_exercise.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this exercise?');">
                                <input type="hidden" name="id" value="<?= $exercise['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php else: ?>
        <p>No exercises created yet.</p>
    <?php endif; ?>
    <h2 class="mt-5">My workouts</h2>
    <div class="table-responsive">
        <table class="table table-white table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Description</th>
                <th>Created at</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php if (count($workouts) > 0): ?>
                <?php foreach ($workouts as $workout): ?>
                    <tr>
                        <td><?= $workout['id'] ?></td>
                        <td><?= htmlspecialchars($workout['name']) ?></td>
                        <td><?= htmlspecialchars($workout['description']) ?></td>
                        <td><?= date('Y-m-d', strtotime($workout['created_at'])) ?></td>
                        <td>
                            <a href="../admin/edit_workout.php?id=<?= $workout['id'] ?>" class="btn btn-primary btn-sm">Edit</a><br><br>
                            <form action="../admin/delete_workout.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
                                <input type="hidden" name="id" value="<?= $workout['id'] ?>">
                                <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            <?php else: ?>
                <tr><td colspan="5">No workout plans found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

</body>
</html>
