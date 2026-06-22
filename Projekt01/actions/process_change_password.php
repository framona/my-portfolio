<?php
require_once '../auth/auth_check.php';
require_once '../includes/db.php';

$userId = $_SESSION['user_id'];

$currentPassword = $_POST['current_password'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$currentPassword || !$newPassword || !$confirmPassword) {
    $_SESSION['error'] = "Please fill in all fields.";
    header('Location: ../profile/change_password.php');
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = "New passwords do not match.";
    header('Location: ../profile/change_password.php');
    exit;
}

$stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = :id");
$stmt->execute(['id' => $userId]);
$user = $stmt->fetch();

if (!$user || !password_verify($currentPassword, $user['password_hash'])) {
    $_SESSION['error'] = "Current password is incorrect.";
    header('Location: ../profile/change_password.php');
    exit;
}

$newHash = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password_hash = :hash WHERE id = :id");
$stmt->execute(['hash' => $newHash, 'id' => $userId]);

$_SESSION['success'] = "Password changed successfully.";
header('Location: ../profile/change_password.php');
exit;
