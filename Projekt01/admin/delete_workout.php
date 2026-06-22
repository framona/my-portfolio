<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] == 'user') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['id']) || !is_numeric($_POST['id'])) {
        $_SESSION['error'] = "Invalid workout ID.";
        header('Location: panel.php');
        exit;
    }

    $id = (int)$_POST['id'];

    $stmt1 = $pdo->prepare("DELETE FROM user_workout_selections WHERE workout_plan_id = :id");
    $stmt1->execute(['id' => $id]);

    $stmt = $pdo->prepare("DELETE FROM workout_plans WHERE id = :id");
    $stmt->execute(['id' => $id]);

    $_SESSION['success'] = "Workout plan deleted.";
    header('Location: panel.php');
    exit;
} else {
    header('Location: panel.php');
    exit;
}
