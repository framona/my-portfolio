<?php
session_start();
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $userId = $_POST['user_id'];
    $newStatus = $_POST['new_status'];

    $stmt = $pdo->prepare("UPDATE users SET is_blocked = :status WHERE id = :id");
    $stmt->execute([
        'status' => $newStatus,
        'id' => $userId
    ]);
}

header('Location: panel.php');
exit;
