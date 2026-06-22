<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

// Only allow admins
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

$searchEmail = $_GET['search_email'] ?? '';

if (!empty($searchEmail)) {
    $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, role, is_blocked 
                           FROM users 
                           WHERE email LIKE :email 
                           ORDER BY id DESC");
    $stmt->execute(['email' => '%' . $searchEmail . '%']);
} else {
    $stmt = $pdo->query("SELECT id, email, first_name, last_name, role, is_blocked 
                         FROM users 
                         ORDER BY id DESC");
}

$users = $stmt->fetchAll();

$searchWorkout = $_GET['search_workout'] ?? '';

if (!empty($searchWorkout)) {
    $stmt2 = $pdo->prepare("SELECT id, name, description, created_at 
                            FROM workout_plans 
                            WHERE name LIKE :name 
                            ORDER BY created_at DESC");
    $stmt2->execute(['name' => '%' . $searchWorkout . '%']);
} else {
    $stmt2 = $pdo->query("SELECT id, name, description, created_at 
                          FROM workout_plans 
                          ORDER BY created_at DESC");
}

$workouts = $stmt2->fetchAll();


$searchExercise = $_GET['search_exercise'] ?? '';
$searchCategory = $_GET['search_category'] ?? '';

if (!empty($searchExercise) || !empty($searchCategory)) {
    $query = "SELECT e.id, e.name, e.duration_minutes, e.created_at, c.name AS category_name 
              FROM exercises e 
              LEFT JOIN training_categories c ON e.category_id = c.id 
              WHERE 1";
    $params = [];

    if (!empty($searchExercise)) {
        $query .= " AND e.name LIKE :exercise";
        $params['exercise'] = '%' . $searchExercise . '%';
    }

    if (!empty($searchCategory)) {
        $query .= " AND c.name LIKE :category";
        $params['category'] = '%' . $searchCategory . '%';
    }

    $query .= " ORDER BY e.created_at DESC";

    $exercisesStmt = $pdo->prepare($query);
    $exercisesStmt->execute($params);
} else {
    $exercisesStmt = $pdo->query("SELECT e.id, e.name, e.duration_minutes, e.created_at, c.name AS category_name 
                                  FROM exercises e 
                                  LEFT JOIN training_categories c ON e.category_id = c.id
                                  ORDER BY e.created_at DESC");
}

$exercises = $exercisesStmt->fetchAll();



?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin panel</title>
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
<div class="container">
    <h1>Admin panel</h1>
    <h2>Manage users</h2>
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search_email" class="form-control" placeholder="Search by email..." value="<?= htmlspecialchars($_GET['search_email'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-danger">Search</button>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-white table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Email</th>
                <th>Name</th>
                <th>Role</th>
                <th>Status</th>
                <th>Toggle</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($users as $user): ?>
                <tr>
                    <td><?= $user['id'] ?></td>
                    <td><?= htmlspecialchars($user['email']) ?></td>
                    <td><?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?></td>
                    <td><?= htmlspecialchars($user['role']) ?></td>
                    <td><?= $user['is_blocked'] ? 'Blocked' : 'Active' ?></td>
                    <td>
                        <form action="toggle_user.php" method="POST" class="d-inline">
                            <input type="hidden" name="user_id" value="<?= $user['id'] ?>">
                            <input type="hidden" name="new_status" value="<?= $user['is_blocked'] ? 0 : 1 ?>">
                            <button class="btn <?= $user['is_blocked'] ? 'btn-success' : 'btn-danger' ?>" type="submit">
                                <?= $user['is_blocked'] ? 'Unblock' : 'Block' ?>
                            </button>
                        </form>
                    </td>
                    <td>
                        <a href="edit_user.php?id=<?= $user['id'] ?>" class="btn btn-primary btn-sm">Edit</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <h2 class="mt-5">Manage workouts</h2>
    <form method="GET" class="mb-3">
        <div class="input-group">
            <input type="text" name="search_workout" class="form-control" placeholder="Search workout by name..." value="<?= htmlspecialchars($_GET['search_workout'] ?? '') ?>">
            <button type="submit" class="btn btn-outline-danger">Search</button>
        </div>
    </form>
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
                            <a href="edit_workout.php?id=<?= $workout['id'] ?>" class="btn btn-primary btn-sm">Edit</a><br><br>
                            <form action="delete_workout.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure?');">
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
    <h2 class="mt-5">Manage exercises</h2>
    <form method="GET" class="mb-3">
        <div class="row g-2">
            <div class="col-md-6">
                <input type="text" name="search_exercise" class="form-control" placeholder="Search exercise name..." value="<?= htmlspecialchars($_GET['search_exercise'] ?? '') ?>">
            </div>
            <div class="col-md-4">
                <input type="text" name="search_category" class="form-control" placeholder="Search by category..." value="<?= htmlspecialchars($_GET['search_category'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <button type="submit" class="btn btn-outline-danger w-100">Search</button>
            </div>
        </div>
    </form>
    <div class="table-responsive">
        <table class="table table-white table-bordered table-hover">
            <thead>
            <tr>
                <th>ID</th>
                <th>Name</th>
                <th>Duration (min)</th>
                <th>Category</th>
                <th>Created at</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach($exercises as $ex): ?>
                <tr>
                    <td><?= $ex['id'] ?></td>
                    <td><?= htmlspecialchars($ex['name']) ?></td>
                    <td><?= $ex['duration_minutes'] ?></td>
                    <td><?= htmlspecialchars($ex['category_name']) ?></td>
                    <td><?= date('Y-m-d', strtotime($ex['created_at'])) ?></td>
                    <td>
                        <a href="edit_exercise.php?id=<?= $ex['id'] ?>" class="btn btn-primary btn-sm mb-1">Edit</a><br><br>
                        <form action="delete_exercise.php" method="POST" style="display:inline-block;" onsubmit="return confirm('Are you sure you want to delete this exercise?');">
                            <input type="hidden" name="id" value="<?= $ex['id'] ?>">
                            <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
</body>
</html>
