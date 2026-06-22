<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

if (isset($_GET['toggle'])) {
    $userId = (int)$_GET['toggle'];
    $stmt = $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = :id AND role != 'admin'");
    $stmt->execute(['id' => $userId]);
    header("Location: users.php");
    exit;
}

if (isset($_GET['delete'])) {
    $userId = (int)$_GET['delete'];
    $stmt = $pdo->prepare("DELETE FROM vet_appointments WHERE owner_id = :id OR vet_id = :id");
    $stmt->execute(['id' => $userId]);
    $stmt = $pdo->prepare("DELETE FROM messages WHERE sender_id = :id OR receiver_id = :id");
    $stmt->execute(['id' => $userId]);
    $stmt = $pdo->prepare("DELETE FROM pets WHERE owner_id = :id");
    $stmt->execute(['id' => $userId]);
    $stmt = $pdo->prepare("DELETE FROM users WHERE id = :id AND role != 'admin'");
    $stmt->execute(['id' => $userId]);
    header("Location: users.php");
    exit;
}

$stmt = $pdo->query("SELECT id, first_name, last_name, email, role, is_active FROM users ORDER BY role, last_name");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Users | PetRegistry</title>
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
    <h2 class="mb-4">Manage Users</h2>

    <div class="row mb-3">
        <div class="col-md-12">
            <input type="text" id="userSearch" class="form-control" placeholder="Search by name or email...">
        </div>
    </div>

    <div class="card shadow-sm">
        <div class="card-body table-responsive">
            <table class="table table-hover align-middle" id="userTable">
                <thead class="table-success">
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
                </thead>
                <tbody>
                <?php foreach ($users as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['first_name'] . ' ' . $u['last_name']) ?></td>
                        <td><?= htmlspecialchars($u['email']) ?></td>
                        <td><span class="badge bg-secondary"><?= strtoupper($u['role']) ?></span></td>
                        <td>
                            <?php if ($u['is_active']): ?>
                                <span class="badge bg-success">Active</span>
                            <?php else: ?>
                                <span class="badge bg-danger">Disabled</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <div class="d-flex justify-content-end gap-2">
                                <?php if ($u['role'] !== 'admin'): ?>
                                    <a href="?toggle=<?= $u['id'] ?>" class="btn btn-sm <?= $u['is_active'] ? 'btn-warning' : 'btn-outline-success' ?>">
                                        <?= $u['is_active'] ? 'Disable' : 'Enable' ?>
                                    </a>
                                    <a href="edit.php?id=<?= $u['id'] ?>" class="btn btn-sm btn-primary">Edit</a>
                                    <a href="?delete=<?= $u['id'] ?>" onclick="return confirm('Are you sure?')" class="btn btn-sm btn-danger">Delete</a>
                                <?php else: ?>
                                    <span class="text-muted">Protected</span>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <a href="dashboard.php" class="btn btn-secondary mt-3">⬅ Back to Dashboard</a>
</div>

<script>
document.getElementById('userSearch').addEventListener('keyup', function() {
    let filter = this.value.toLowerCase();
    let rows = document.querySelectorAll('#userTable tbody tr');

    rows.forEach(row => {
        let name = row.cells[0].textContent.toLowerCase();
        let email = row.cells[1].textContent.toLowerCase();
        if (name.includes(filter) || email.includes(filter)) {
            row.style.display = '';
        } else {
            row.style.display = 'none';
        }
    });
});
</script>
</body>
</html>