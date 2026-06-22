<?php
session_start();
require_once '../includes/db.php';
require_once '../auth/send_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/forgot_password.php');
    exit;
}

$email = trim($_POST['email']);

$stmt = $pdo->prepare("SELECT id, first_name FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "No user found with this email.";
    header('Location: ../auth/forgot_password.php');
    exit;
}

$resetToken = bin2hex(random_bytes(32));
$stmt = $pdo->prepare("UPDATE users SET reset_token = :token WHERE id = :id");
$stmt->execute(['token' => $resetToken, 'id' => $user['id']]);

$link = "https://nak.stud.vts.su.ac.rs/auth/reset_password.php?token=$resetToken";
$subject = "Password reset request";
$body = "Hello {$user['first_name']},<br><br>Click the link below to reset your password:<br><a href=\"$link\">$link</a>";

send_activation_email($email, $subject, $body);

$_SESSION['success'] = "Reset link sent! Please check your email.";
header('Location: ../auth/forgot_password.php');
exit;
