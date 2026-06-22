<?php
session_start();
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    $_SESSION['error'] = "You must be logged in.";
    header('Location: ../auth/login.php');
    exit;
}

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$currentPassword || !$newPassword || !$confirmPassword) {
    $_SESSION['error'] = "Please fill in all fields.";

    header('Location: ../auth/change_password.php');
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = "New passwords do not match.";
    header('Location: ../auth/change_password.php');
    exit;
}

$stmt = $pdo->prepare("SELECT password FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "User not found.";
    header('Location: ../auth/change_password.php');
    exit;
}

if (!password_verify($currentPassword, $user['password'])) {
    $_SESSION['error'] = "Current password is incorrect.";
    header('Location: ../auth/change_password.php');
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = :hash WHERE id = :id");
$stmt->execute([
    'hash' => $newHash,
    'id' => $userId
]);

$_SESSION['success'] = "Password changed successfully.";
header('Location: ../auth/change_password.php');
exit;
