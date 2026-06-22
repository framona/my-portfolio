<?php
session_start();
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_workout.php');
    exit;
}

$trainerId = $_SESSION['user_id'];
$planName   = trim($_POST['plan_name']);
$categoryId = (int)$_POST['category_id'];
$dayCount   = max(1, min(7, (int)$_POST['day_count']));

$stmt = $pdo->prepare("
    INSERT INTO workout_plans (user_id, name, category_id, created_at)
    VALUES (:trainer_id, :name, :category_id, NOW())
");
$stmt->execute([
    'trainer_id'  => $trainerId,
    'name'        => $planName,
    'category_id' => $categoryId
]);

$planId = $pdo->lastInsertId();

header("Location: add_workout_days.php?plan={$planId}&days={$dayCount}");
exit;
