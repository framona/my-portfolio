<?php
session_start();
require_once '../includes/db.php';
require_once '../auth/send_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/register.php');
    exit;
}

$email = trim($_POST['email']);
$username = trim($_POST['email']);
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];
$isTrainer = isset($_POST['is_trainer']) ? 1 : 0;

if ($password !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match.";
    header('Location: ../auth/register.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email OR username = :username");
$stmt->execute(['email' => $email, 'username' => $username]);

if ($stmt->fetch()) {
    $_SESSION['error'] = "Email or username already exists.";
    header('Location: ../auth/register.php');
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$activationToken = bin2hex(random_bytes(32));

$sql = "INSERT INTO users (email, username, password_hash, first_name, last_name, phone, is_trainer, is_active, is_blocked, role, activation_token, created_at)
        VALUES (:email, :username, :pass, :fname, :lname, :phone, :trainer, 0, 0, 'user', :token, NOW())";
$stmt = $pdo->prepare($sql);
$stmt->execute([
    'email' => $email,
    'username' => $username,
    'pass' => $hashedPassword,
    'fname' => $firstName,
    'lname' => $lastName,
    'phone' => $phone,
    'trainer' => $isTrainer,
    'token' => $activationToken
]);

$activationLink = "https://nak.stud.vts.su.ac.rs/auth/active.php?token=" . $activationToken;
$subject = "Activate your account";
$body = "Hello $firstName,<br><br>Please click the link below to activate your account:<br><a href='$activationLink'>$activationLink</a>";

send_activation_email($email, $subject, $body);

$_SESSION['error'] = "Registration successful! Please check your email to activate your account.";
header('Location: ../auth/register.php');
exit;
