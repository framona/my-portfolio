<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] == 'user') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id'])) {
        header('Location: panel.php');
        exit;
    }

    $id = (int)$_POST['id'];

    $stmt1 = $pdo->prepare("DELETE FROM workout_day_exercises WHERE exercise_id = :id");
    $stmt1->execute(['id' => $id]);

    $stmt = $pdo->prepare("DELETE FROM exercises WHERE id = :id");
    $stmt->execute(['id' => $id]);
}

header('Location: panel.php');
exit;
