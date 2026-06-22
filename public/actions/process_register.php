<?php
session_start();

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/send_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../auth/register.php");
    exit;
}

$email = trim($_POST['email']);
$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$phone = trim($_POST['phone']);
$password = $_POST['password'];
$confirmPassword = $_POST['confirm_password'];
$petName = trim($_POST['pet_name']);
$species = trim($_POST['species']);
$breed = trim($_POST['breed']);
$birthYear = trim($_POST['birth_year']);
$currentYear = date('Y');
$age = $currentYear - $birthYear;
$vetId = trim($_POST['vet_id']);

if (
    empty($email) || empty($firstName) || empty($lastName) ||
    empty($password) || empty($petName) || empty($species) || empty($breed) || empty($birthYear) || empty($vetId)
) {
    $_SESSION['error'] = "All fields are required.";
    header("Location: ../auth/register.php");
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $_SESSION['error'] = "Invalid email address.";
    header("Location: ../auth/register.php");
    exit;
}

if ($password !== $confirmPassword) {
    $_SESSION['error'] = "Passwords do not match.";
    header('Location: ../auth/register.php');
    exit;
}

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
$stmt->execute([$email]);

if ($stmt->fetch()) {
    $_SESSION['error'] = "Email already registered.";
    header("Location: ../auth/register.php");
    exit;
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);
$activationToken = bin2hex(random_bytes(32));

try {
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("
        INSERT INTO users (email, password, first_name, last_name, phone, role, is_active, can_login, activation_token, created_at)
        VALUES (?, ?, ?, ?, ?, 'user', 0, 1, ?, NOW())
    ");
    $stmt->execute([
        $email,
        $hashedPassword,
        $firstName,
        $lastName,
        $phone,
        $activationToken
    ]);

    $userId = $pdo->lastInsertId();

    $stmt = $pdo->prepare("
        INSERT INTO pets (owner_id, vet_id, name, species, breed, age, created_at)
        VALUES (?, ?, ?, ?, ?, ?, NOW())
    ");
    $stmt->execute([
        $userId,
        $vetId,
        $petName,
        $species,
        $breed,
        $age
    ]);

    $pdo->commit();

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Registration failed. Please try again.";
    header("Location: ../auth/register.php");
    exit;
}
$activationLink = "https://nak.stud.vts.su.ac.rs/public/auth/activate.php?token=" . $activationToken;

$subject = "Activate your PetRegistry account";

$body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
    <div style='background-color: #6BBB77; padding: 20px; text-align: center;'>
        <h1 style='color: white; margin: 0;'>PetRegistry</h1>
    </div>
    <div style='padding: 30px; line-height: 1.6; color: #333;'>
        <h2>Hello $firstName!</h2>
        <p>Thank you for joining PetRegistry. You're just one step away from keeping your pet's information safe and smart.</p>
        <p>Please click the button below to verify your email address and activate your account:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='$activationLink' style='background-color: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Activate Account</a>
        </div>
        
        <p style='font-size: 0.9rem; color: #666;'>If the button doesn't work, copy and paste this link into your browser:</p>
        <p style='font-size: 0.8rem; color: #888; word-break: break-all;'>$activationLink</p>
    </div>
    <div style='background-color: #f9f9f9; padding: 15px; text-align: center; font-size: 0.8rem; color: #999;'>
        © 2025 PetRegistry — Keeping pets safe.
    </div>
</div>
";
send_activation_email($email, $subject, $body);

$_SESSION['success'] = "Account created successfully! Please activate your account via email.";
header("Location: ../auth/login.php");
exit;
