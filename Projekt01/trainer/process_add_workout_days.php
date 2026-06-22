<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

$planId = (int)$_POST['plan_id'];
$days    = $_POST['day'] ?? [];

foreach ($days as $dayOfWeek => $exerciseIds) {
    $dayStmt = $pdo->prepare("
      INSERT INTO workout_days (plan_id, day_name)
      VALUES (:plan_id, :day_of_week)
    ");
    $dayStmt->execute([
        'plan_id'     => $planId,
        'day_of_week' => (int)$dayOfWeek
    ]);
    $dayId = $pdo->lastInsertId();

    $linkStmt = $pdo->prepare("
      INSERT INTO workout_day_exercises (day_id, exercise_id)
      VALUES (:day_id, :exercise_id)
    ");
    foreach ($exerciseIds as $exId) {
        $linkStmt->execute([
            'day_id'      => $dayId,
            'exercise_id'=> (int)$exId
        ]);
    }
}

$_SESSION['success'] = "Workout plan created!";
header('Location: dashboard.php');
exit;
