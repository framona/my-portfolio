<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role='vet'");
$stmt->execute();
$vets = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Pet | PetRegistry</title>
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
    <h2>Add New Pet</h2>

    <?php if(isset($_SESSION['error'])): ?>
        <div class="alert alert-danger"><?php echo $_SESSION['error']; unset($_SESSION['error']); ?></div>
    <?php endif; ?>

    <form action="../actions/process_add_pet.php" method="POST" enctype="multipart/form-data">
        <div class="mb-3">
            <label for="name" class="form-label">Pet Name</label>
            <input type="text" name="name" id="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="species" class="form-label">Species</label>
            <input type="text" name="species" id="species" class="form-control" required>
        </div>

        <div class="mb-3">
            <label for="breed" class="form-label">Breed</label>
            <input type="text" name="breed" id="breed" class="form-control">
        </div>

        <div class="mb-3">
            <label for="age" class="form-label">Age (years)</label>
            <input type="number" name="age" id="age" class="form-control" min="0">
        </div>

        <div class="mb-3">
            <label for="vet_id" class="form-label">Select Vet</label>
            <select name="vet_id" id="vet_id" class="form-select" required>
                <option value="">-- Choose a vet --</option>
                <?php foreach($vets as $vet): ?>
                    <option value="<?php echo $vet['id']; ?>">
                        Dr. <?php echo htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="mb-3">
            <label for="pet_image" class="form-label">Pet Photo</label>
            <input type="file" name="pet_image" id="pet_image" class="form-control" accept="image/png, image/jpeg">
        </div>
        <div class="d-flex gap-2">
            <button type="submit" class="btn btn-success">Add Pet</button>
            <a href="pets.php" class="btn btn-secondary">Cancel</a>
        </div>
    </form>
</div>
</body>
</html>
