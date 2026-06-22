<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

$token = $_POST['token'] ?? '';
$newPassword = $_POST['new_password'] ?? '';
$confirmPassword = $_POST['confirm_password'] ?? '';

if (!$token || !$newPassword || !$confirmPassword) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../auth/reset_password.php?token=" . urlencode($token));
    exit;
}

if ($newPassword !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match.";
    header("Location: ../auth/reset_password.php?token=" . urlencode($token));
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE reset_token = :token");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Invalid or expired token.";
    header("Location: ../auth/forgot_password.php");
    exit;
}

$hashed = password_hash($newPassword, PASSWORD_BCRYPT);
$stmt = $pdo->prepare("UPDATE users SET password = :hash, reset_token = NULL WHERE id = :id");
$stmt->execute(['hash' => $hashed, 'id' => $user['id']]);

$_SESSION['success'] = "Password reset successful! You can now log in.";
header('Location: ../auth/login.php');
exit;
