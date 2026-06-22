<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';

if (!isset($_GET['token'])) {
    $_SESSION['error'] = "Activation token is missing.";
    header('Location: ../auth/login.php');
    exit;
}

$token = $_GET['token'];

$stmt = $pdo->prepare("SELECT id, is_active FROM users WHERE activation_token = :token");
$stmt->execute(['token' => $token]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Invalid activation token.";
    header('Location: ../auth/login.php');
    exit;
}

if ($user['is_active']) {
    $_SESSION['success'] = "Account already activated.";
    header('Location: ../auth/login.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE id = :id");
$stmt->execute(['id' => $user['id']]);

$_SESSION['success'] = "Account activated successfully! You can now log in.";
header('Location: ../auth/login.php');
exit;
?>
