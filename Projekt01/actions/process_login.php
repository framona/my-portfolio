<?php
session_start();
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login.php');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

$stmt = $pdo->prepare("SELECT id, email, username, password_hash, is_trainer, is_active, is_blocked, role FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Invalid email or password.";
    header('Location: ../auth/login.php');
    exit;
}

if (!$user['is_active']) {
    $_SESSION['error'] = "Account not activated. Please check your email.";
    header('Location: ../auth/login.php');
    exit;
}

if ($user['is_blocked']) {
    $_SESSION['error'] = "Your account is blocked. Contact administrator.";
    header('Location: ../auth/login.php');
    exit;
}

if (!password_verify($password, $user['password_hash'])) {
    $_SESSION['error'] = "Invalid email or password.";
    header('Location: ../profile/profile.php');
    exit;
}

$_SESSION['user_id'] = $user['id'];
$_SESSION['username'] = $user['username'];
$_SESSION['role'] = $user['role'];
$_SESSION['is_trainer'] = $user['is_trainer'];

header('Location: ../profile/profile.php');
exit;
