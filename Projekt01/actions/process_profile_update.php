<?php
require_once '../auth/auth_check.php';
require_once '../includes/db.php';

$user_id = $_SESSION['user_id'];

$firstName = trim($_POST['first_name']);
$lastName = trim($_POST['last_name']);
$phone = trim($_POST['phone']);

if (!$firstName || !$lastName || !$phone) {
    $_SESSION['error'] = "All fields are required.";
    header('Location: ../profile/profile.php');
    exit;
}

$stmt = $pdo->prepare("UPDATE users SET first_name = :fname, last_name = :lname, phone = :phone WHERE id = :id");
$stmt->execute([
    'fname' => $firstName,
    'lname' => $lastName,
    'phone' => $phone,
    'id' => $user_id
]);

$_SESSION['success'] = "Profile updated successfully.";
header('Location: ../profile/profile.php');
exit;
