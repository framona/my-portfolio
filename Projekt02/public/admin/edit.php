<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$id = $_GET['id'] ?? null;
if (!$id) {
    header("Location: users.php");
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM users WHERE id = :id");
$stmt->execute(['id' => $id]);
$vet = $stmt->fetch(PDO::FETCH_ASSOC);


$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $first = trim($_POST['first_name']);
    $last  = trim($_POST['last_name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($first === '' || $last === '' || $email === '') {
        $error = "Required fields are missing.";
    } else {
        $stmt = $pdo->prepare("
            UPDATE users
            SET first_name = :first,
                last_name = :last,
                email = :email,
                phone = :phone
            WHERE id = :id
        ");
        $stmt->execute([
            'first' => $first,
            'last'  => $last,
            'email' => $email,
            'phone' => $phone,
            'id'    => $id
        ]);

        header("Location: users.php");
    }
}
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
    <h2>Edit Veterinarian</h2>

    <?php if ($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <?php if ($success): ?>
        <div class="alert alert-success"><?= htmlspecialchars($success) ?></div>
    <?php endif; ?>

    <div class="card shadow-sm">
        <div class="card-body">
            <form method="post">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <label class="form-label">First name</label>
                        <input type="text" name="first_name" class="form-control"
                               value="<?= htmlspecialchars($vet['first_name']) ?>" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Last name</label>
                        <input type="text" name="last_name" class="form-control"
                               value="<?= htmlspecialchars($vet['last_name']) ?>" required>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control"
                           value="<?= htmlspecialchars($vet['email']) ?>" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Phone</label>
                    <input type="text" name="phone" class="form-control"
                           value="<?= htmlspecialchars($vet['phone']) ?>">
                </div>

                <div class="d-flex justify-content-between">
                    <button class="btn btn-success">Save Changes</button>
                    <a href="users.php" class="btn btn-secondary">Back</a>
                </div>
            </form>
        </div>
    </div>
</div>

</body>
</html>
