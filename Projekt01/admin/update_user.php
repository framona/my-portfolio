<?php
require_once '../includes/db.php';
require_once '../auth/auth_check.php';

if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    $email = trim($_POST['email'] ?? '');
    $firstName = trim($_POST['first_name'] ?? '');
    $lastName = trim($_POST['last_name'] ?? '');
    $role = $_POST['role'] ?? 'user';
    $isBlocked = isset($_POST['is_blocked']) ? 1 : 0;

    if ($id <= 0 || empty($email) || empty($firstName) || empty($lastName)) {
        header("Location: edit_user.php?id=$id&error=missing_fields");
        exit;
    }

    $stmt = $pdo->prepare("UPDATE users SET email = :email, first_name = :first_name, last_name = :last_name, role = :role, is_blocked = :is_blocked WHERE id = :id");
    $stmt->execute([
        ':email' => $email,
        ':first_name' => $firstName,
        ':last_name' => $lastName,
        ':role' => $role,
        ':is_blocked' => $isBlocked,
        ':id' => $id
    ]);

    header('Location: panel.php?success=updated');
    exit;
} else {
    header('Location: panel.php');
    exit;
}
