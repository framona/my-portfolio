<?php
session_start();

require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/send_email.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/forgot_password.php');
    exit;
}

$email = trim($_POST['email']);

$stmt = $pdo->prepare("SELECT id FROM users WHERE email = :email");
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

$link = "https://nak.stud.vts.su.ac.rs/public/auth/reset_password.php?token=$resetToken";

$subject = "Password Reset Request - PetRegistry";

$body = "
<div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; border: 1px solid #e0e0e0; border-radius: 10px; overflow: hidden;'>
    <div style='background-color: #6BBB77; padding: 20px; text-align: center;'>
        <h1 style='color: white; margin: 0;'>PetRegistry</h1>
    </div>
    <div style='padding: 30px; line-height: 1.6; color: #333;'>
        <h2>Hello {$user['first_name']}!</h2>
        <p>We received a request to reset the password for your PetRegistry account.</p>
        <p>If you made this request, please click the button below to set a new password:</p>
        
        <div style='text-align: center; margin: 30px 0;'>
            <a href='$link' style='background-color: #4CAF50; color: white; padding: 15px 25px; text-decoration: none; border-radius: 50px; font-weight: bold; display: inline-block;'>Reset My Password</a>
        </div>
        
        <p style='font-size: 0.9rem; color: #666;'>If you did not request a password reset, you can safely ignore this email. Your password will remain unchanged.</p>
        
        <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
        <p style='font-size: 0.8rem; color: #888; word-break: break-all;'>Button not working? Copy this link: <br>$link</p>
    </div>
    <div style='background-color: #f9f9f9; padding: 15px; text-align: center; font-size: 0.8rem; color: #999;'>
        © 2025 PetRegistry — Security Notification
    </div>
</div>
";
send_activation_email($email, $subject, $body);

$_SESSION['success'] = "Reset link sent! Please check your email.";
header('Location: ../auth/forgot_password.php');
exit;
