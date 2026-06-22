<?php
require_once '../auth/auth_check.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstName = trim($_POST['first_name']);
    $lastName  = trim($_POST['last_name']);
    $phone      = trim($_POST['phone']);

    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/profile_images/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $fileName = uniqid() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        $allowed = ['jpg', 'jpeg', 'png', 'gif'];
        if (in_array(strtolower($ext), $allowed)) {
            move_uploaded_file($_FILES['profile_image']['tmp_name'], $filePath);
            $profilePath = 'uploads/profile_images/' . $fileName;
        } else {
            $errors[] = "Invalid image file type.";
        }
    }

    if (empty($errors)) {
        $sql = "UPDATE users SET first_name = :fn, last_name = :ln, phone = :ph";
        if (isset($profilePath)) $sql .= ", profile_image = :img";
        $sql .= " WHERE id = :id";

        $params = [
            'fn' => $firstName,
            'ln' => $lastName,
            'ph' => $phone,
            'id' => $userId
        ];
        if (isset($profilePath)) $params['img'] = $profilePath;

        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $_SESSION['success'] = "Profile updated successfully.";
        header('Location: profile.php');
        exit;
    }
}

$stmt = $pdo->prepare("SELECT first_name, last_name, phone, profile_image FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit profile</title>
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
<body class="bg-light">
<div class="container mt-5">
    <div class="card shadow-sm p-4">
        <h2>Edit Profile</h2>

        <?php foreach ($errors as $err): ?>
            <div class="alert alert-danger"><?= htmlspecialchars($err) ?></div>
        <?php endforeach; ?>

        <form action="" method="POST" enctype="multipart/form-data">
            <div class="mb-3">
                <label class="form-label">First Name:</label>
                <input type="text" name="first_name" class="form-control" required value="<?= htmlspecialchars($user['first_name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Last Name:</label>
                <input type="text" name="last_name" class="form-control" required value="<?= htmlspecialchars($user['last_name']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Phone:</label>
                <input type="text" name="phone" class="form-control" value="<?= htmlspecialchars($user['phone']) ?>">
            </div>

            <div class="mb-3">
                <label class="form-label">Profile Image (optional):</label><br>
                <?php if ($user['profile_image'] && file_exists('../' . $user['profile_image'])): ?>
                    <img src="../<?= htmlspecialchars($user['profile_image']) ?>" alt="Profile" style="width:100px; height:100px; border-radius:50%; object-fit:cover;">
                    <br><br>
                <?php endif; ?>
                <input type="file" name="profile_image" accept="image/*" class="form-control">
            </div>

            <button type="submit" class="btn btn-primary">Save changes</button>
            <a href="profile.php" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
</body>
</html>
