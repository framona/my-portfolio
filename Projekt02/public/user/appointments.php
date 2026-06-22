<?php
require_once '../auth/auth_check.php';
require_once '../actions/send_email.php';
require_once __DIR__ . '/../../src/config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_appointment'])) {
    $petId = $_POST['pet_id'];
    $vetId = $_POST['vet_id'];
    $ownerId = $_POST['owner_id'];
    $datetime = $_POST['appointment_date'];
    $note = $_POST['note'] ?? '';

    $stmt = $pdo->prepare("INSERT INTO vet_appointments (pet_id, vet_id, owner_id, appointment_time, cancel_message) 
                           VALUES (:pet, :vet, :owner, :date, :note)");
    $stmt->execute([
        'pet' => $petId,
        'vet' => $vetId,
        'owner' => $ownerId,
        'date' => $datetime,
        'note' => $note
    ]);

    $stmtUser = $pdo->prepare("SELECT first_name, email FROM users WHERE id = ?");
    $stmtUser->execute([$_SESSION['user_id']]);
    $user = $stmtUser->fetch();

    if ($user && !empty($user['email'])) {
        $userEmail = $user['email'];
        $subject = "Vet appointment booked";
        $body = "Dear {$user['first_name']},<br><br>" .
            "Your appointment on <b>{$datetime}</b> has been booked successfully.<br><br>" .
            "Best regards,<br>PetRegistry";

        send_activation_email($userEmail, $subject, $body);
    }

    header("Location: appointments.php?msg=Appointment+booked+and+notification+sent");
    exit;
}

$stmt = $pdo->prepare("
    SELECT a.id, a.appointment_time, a.cancel_message,
           p.name AS pet_name,
           o.first_name AS owner_first, o.last_name AS owner_last,
           v.first_name AS vet_first, v.last_name AS vet_last
    FROM vet_appointments a
    JOIN pets p ON a.pet_id = p.id
    JOIN users o ON a.owner_id = o.id
    JOIN users v ON a.vet_id = v.id WHERE p.owner_id = ?
    ORDER BY a.appointment_time DESC
");
$stmt->execute([$_SESSION['user_id']]);
$appointments = $stmt->fetchAll();

$stmt3 = $pdo->prepare("SELECT id, name FROM pets WHERE owner_id = ?");
$stmt3->execute([$_SESSION['user_id']]);
$pets = $stmt3->fetchAll();
$stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$owners = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt2 = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = :role ORDER BY first_name");
$stmt2->execute(['role' => 'vet']);
$vets = $stmt2->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Appointments | PetRegistry</title>
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
<h1>Appointments</h1>
<?php if (isset($_GET['msg'])): ?>
    <div class="alert alert-info">
        <?= htmlspecialchars($_GET['msg']) ?>
    </div>
<?php endif; ?>
<h3>Add Appointment</h3>
<form method="post" class="mb-4">
    <input type="hidden" name="add_appointment" value="1">
    <div class="mb-3">
        <label>Pet</label>
        <select name="pet_id" class="form-select" required>
            <?php foreach($pets as $pet): ?>
                <option value="<?= $pet['id'] ?>"><?= htmlspecialchars($pet['name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label>Vet</label>
        <select name="vet_id" class="form-select" required>
            <?php foreach($vets as $vet): ?>
                <option value="<?= $vet['id'] ?>"><?= htmlspecialchars($vet['first_name'] . ' ' . $vet['last_name']) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <input type="hidden" name="owner_id" value="<?= $owners[0]['id'] ?? '' ?>">
    </div>
    <div class="mb-3">
        <label>Date & Time</label>
        <input type="datetime-local" name="appointment_date" class="form-control" required>
    </div>
    <div class="mb-3">
        <label>Note</label>
        <textarea name="note" class="form-control"></textarea>
    </div>
    <button class="btn btn-success">Add Appointment</button>
</form>
<h3>Existing Appointments</h3>
<table class="table table-bordered">
    <thead>
    <tr>
        <th>Pet</th>
        <th>Owner</th>
        <th>Vet</th>
        <th>Date & Time</th>
        <th>Note</th>
        <th>Actions</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($appointments as $a): ?>
        <tr>
            <td><?= htmlspecialchars($a['pet_name']) ?></td>
            <td><?= htmlspecialchars($a['owner_first'] . ' ' . $a['owner_last']) ?></td>
            <td><?= htmlspecialchars($a['vet_first'] . ' ' . $a['vet_last']) ?></td>
            <td><?= htmlspecialchars($a['appointment_time']) ?></td>
            <td><?= htmlspecialchars($a['cancel_message']) ?></td>
            <td>
                <a href="edit_appointment.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-warning">Edit</a>
                <a href="delete_appointment.php?id=<?= $a['id'] ?>" class="btn btn-sm btn-danger">Delete</a>
            </td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
