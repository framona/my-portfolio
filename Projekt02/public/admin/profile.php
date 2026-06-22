<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

$user_id = $_SESSION['user_id'];

$profile_id = $_GET['id'] ?? $user_id;

$stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone FROM users WHERE id = :id");
$stmt->execute(['id' => $profile_id]);
$user = $stmt->fetch();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Profile | PetRegistry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/authStyle.css">
    <script src="../assets/js/Script.js" defer></script>
</head>
<body>
<nav class="navbar">
    <div class="brand-title">PetRegistry</div>

    <a href="#" class="toggle-button">
        <span class="bar"></span>
        <span class="bar"></span>
        <span class="bar"></span>
    </a>

    <div class="navbar-links">
        <ul>
            <li><a href="../index.php">Home</a></li>

            <?php if (!isset($_SESSION['user_id'])): ?>
                <li><a href="../auth/login.php">Login</a></li>
                <li><a href="../auth/register.php">Register</a></li>
            <?php else: ?>

                <?php if ($_SESSION['role'] === 'vet'): ?>
                    <li><a href="../vet/dashboard.php">Dashboard</a></li>
                    <li><a href="../vet/vet_appointments.php">Appointments</a></li>
                    <li><a href="../vet/vet_messages.php">Messages</a></li>

                <?php elseif ($_SESSION['role'] === 'admin'): ?>
                    <li><a href="../admin/dashboard.php">Admin panel</a></li>

                <?php else: ?>
                    <li><a href="../user/dashboard.php">Dashboard</a></li>
                    <li><a href="../user/pets.php">My Pets</a></li>
                    <li><a href="../user/appointments.php">Appointments</a></li>
                    <li><a href="../user/messages.php">Messages</a></li>
                <?php endif; ?>

                <li><a href="../auth/logout.php">Logout</a></li>
            <?php endif; ?>
        </ul>
    </div>
</nav>
<div class="container mt-5">
    <div class="box">
        <h2>Welcome, <?= htmlspecialchars($user['first_name'] . ' ' . $user['last_name']) ?>!</h2>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']) ?></p>
        <p><strong>Phone:</strong> <?= htmlspecialchars($user['phone']) ?></p>
        <div class="d-flex gap-2">
            <a href="../user/profile_edit.php" class="btn btn-success">Edit profile</a>
            <a href="../auth/change_password.php" class="btn btn-outline-secondary">Change password</a>
        </div>
    </div>
</div>
</body>
</html>
