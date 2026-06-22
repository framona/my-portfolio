<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$stats = [
    'users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'vets' => $pdo->query("SELECT COUNT(*) FROM users WHERE role='vet'")->fetchColumn(),
    'pets' => $pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn(),
    'appointments' => $pdo->query("SELECT COUNT(*) FROM vet_appointments")->fetchColumn(),
];

$recentUsers = $pdo->query("
    SELECT first_name, last_name, email, created_at
    FROM users
    ORDER BY created_at DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin | PetRegistry</title>
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
<div class="container mt-4">
    <h2 class="mb-4">Admin Dashboard</h2>
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5>Users</h5>
                    <h2><?= $stats['users'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5>Veterinarians</h5>
                    <h2><?= $stats['vets'] ?></h2>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center shadow-sm">
                <div class="card-body">
                    <h5>Pets</h5>
                    <h2><?= $stats['pets'] ?></h2>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-3">
            <a href="add_vet.php" class="btn btn-success w-100">Add Veterinarian</a>
        </div>
        <div class="col-md-3">
            <a href="users.php" class="btn btn-success w-100">Manage Users</a>
        </div>
        <div class="col-md-3">
            <a href="ratings.php" class="btn btn-success w-100">Statistics</a>
        </div>
        <div class="col-md-3">
            <a href="profile.php" class="btn btn-success w-100">My Profile</a>
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-header">
            <strong>Recent Registrations</strong>
        </div>
        <div class="card-body p-0">
            <table class="table table-striped mb-0">
                <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Registered</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($recentUsers as $user): ?>
                    <tr>
                        <td><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></td>
                        <td><?= htmlspecialchars($user['email']) ?></td>
                        <td><?= $user['created_at'] ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

</body>
</html>
