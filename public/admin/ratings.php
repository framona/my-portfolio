<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../index.php");
    exit;
}

$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = ? ORDER BY first_name");
$stmt->execute(['vet']);
$vets = $stmt->fetchAll(PDO::FETCH_ASSOC);


$selectedVetId = $_GET['vet_id'] ?? null;

$animalCount = $totalAppointments = $weeklyAppointments = $monthlyAppointments = null;

if ($selectedVetId) {

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pets WHERE vet_id = ?");
    $stmt->execute([$selectedVetId]);
    $animalCount = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM vet_appointments WHERE vet_id = ?");
    $stmt->execute([$selectedVetId]);
    $totalAppointments = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM vet_appointments 
        WHERE vet_id = ?
        AND appointment_time BETWEEN CURDATE() 
        AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)
    ");
    $stmt->execute([$selectedVetId]);
    $weeklyAppointments = $stmt->fetchColumn();

    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM vet_appointments 
        WHERE vet_id = ?
        AND MONTH(appointment_time) = MONTH(CURDATE())
        AND YEAR(appointment_time) = YEAR(CURDATE())
    ");
    $stmt->execute([$selectedVetId]);
    $monthlyAppointments = $stmt->fetchColumn();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Statistics | PetRegistry</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="../assets/css/authStyle.css">
    <script src="../assets/js/Script.js" defer></script>
    <style>
        .chart-card {
            width: 100%;
            max-width: 500px;
        }

        .chart-wrapper {
            width: 100%;
            height: 320px;
        }

        .chart-wrapper canvas {
            width: 100% !important;
            height: 100% !important;
        }

    </style>
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
    <h1 class="mb-4">Statistics</h1>

    <form method="get" class="mb-4">
        <label class="form-label">Select veterinarian</label>
        <select name="vet_id" class="form-select" onchange="this.form.submit()">
            <option value="">-- Select veterinarian --</option>
            <?php foreach ($vets as $vet): ?>
                <option value="<?= $vet['id'] ?>"
                    <?= ($selectedVetId == $vet['id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <?php if ($selectedVetId): ?>
        <div class="row g-4">

            <div class="col-md-3">
                <div class="card text-bg-success text-center">
                    <div class="card-body">
                        <h6>Animals</h6>
                        <p class="display-6"><?= $animalCount ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-primary text-center">
                    <div class="card-body">
                        <h6>All dates</h6>
                        <p class="display-6"><?= $totalAppointments ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-warning text-center">
                    <div class="card-body">
                        <h6>Weekly dates</h6>
                        <p class="display-6"><?= $weeklyAppointments ?></p>
                    </div>
                </div>
            </div>

            <div class="col-md-3">
                <div class="card text-bg-info text-center">
                    <div class="card-body">
                        <h6>Monthly dates</h6>
                        <p class="display-6"><?= $monthlyAppointments ?></p>
                    </div>
                </div>
            </div>

        </div>
    <?php endif; ?>
    <?php if ($selectedVetId): ?>
        <div class="mt-4 d-flex flex-column align-items-center gap-4">

            <div class="card shadow-sm chart-card ">
                <div class="card-body">
                    <h5 class="card-title text-center mb-3">Appointments overview</h5>
                    <div class="chart-wrapper">
                        <canvas id="appointmentsChart"></canvas>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm chart-card">
                <div class="card-body">
                    <h5 class="card-title text-center mb-3">Veterinarian popularity</h5>
                    <div class="chart-wrapper">
                        <canvas id="vetPieChart"></canvas>
                    </div>
                </div>
            </div>

        </div>


    <?php endif; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

    <script>
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom'
                }
            }
        };
        new Chart(document.getElementById('appointmentsChart'), {
            type: 'bar',
            data: {
                labels: ['This week', 'This month'],
                datasets: [{
                    label: 'Appointments',
                    data: [
                        <?= (int)$weeklyAppointments ?>,
                        <?= (int)$monthlyAppointments ?>
                    ],
                    backgroundColor: ['#ffc107', '#0dcaf0']
                }]
            },
            options: chartOptions
        });
        new Chart(document.getElementById('vetPieChart'), {
            type: 'pie',
            data: {
                labels: ['Animals'],
                datasets: [{
                    data: [<?= (int)$animalCount ?>],
                    backgroundColor: ['#198754']
                }]
            },
            options: chartOptions
        });
    </script>

    <a href="dashboard.php" class="btn btn-outline-dark mt-4">⬅ Back</a>
</div>

</body>
</html>
