<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['is_trainer']) || $_SESSION['is_trainer'] != 1) {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: add_exercise.php');
    exit;
}

$title = trim($_POST['title']);
$description = trim($_POST['description']);
$duration = (int) $_POST['duration'];
$videoUrl = trim($_POST['video_link']);
$trainerId = $_SESSION['user_id'];

$imagePath = null;

if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
    $uploadDir = '../uploads/exercises/';
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0777, true);
    }

    $fileTmp = $_FILES['image']['tmp_name'];
    $fileExt = pathinfo($_FILES['image']['name'], PATHINFO_EXTENSION);
    $newFilename = uniqid('ex_') . '.' . $fileExt;
    $destination = $uploadDir . $newFilename;

    move_uploaded_file($fileTmp, $destination);
    $imageUrl  = 'uploads/exercises/' . $newFilename;
}

$categoryId = $_POST['category_id'];

$sql = "INSERT INTO exercises (trainer_id, name, description, duration_minutes, video_url, image_url, category_id, created_at)
        VALUES (:trainer_id, :name, :description, :duration, :video, :image, :category_id, NOW())";

$stmt = $pdo->prepare($sql);
$stmt->execute([
    'trainer_id' => $trainerId,
    'name' => $title,
    'description' => $description,
    'duration' => $duration,
    'video' => $videoUrl,
    'image' => $imageUrl,
    'category_id' => $categoryId
]);


$_SESSION['success'] = "Exercise added successfully!";
header('Location: dashboard.php');
exit;
