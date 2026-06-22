<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require __DIR__ . '/../vendor/autoload.php'; 

use Detection\MobileDetect;
$detect = new MobileDetect;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../auth/login.php');
    exit;
}

$email = trim($_POST['email']);
$password = $_POST['password'];

$ip_address = $_SERVER['REMOTE_ADDR'];
if ($ip_address == '::1' || $ip_address == '127.0.0.1') {
    $ip_address = '8.8.8.8'; 
}

$device_type = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
$browser = $_SERVER['HTTP_USER_AGENT'];

$url = "https://proxycheck.io/v2/$ip_address?vpn=1&asn=1";
$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);
curl_close($ch);

$data = json_decode($response, true);
$country = 'Unknown';
$isp = 'Unknown';

if ($data && isset($data[$ip_address])) {
    $info = $data[$ip_address];
    $country = $info['country'] ?? 'Unknown';
    $isp = $info['isp'] ?? 'Unknown';
}

$user_id = null;
$success = 0;

$stmt = $pdo->prepare("SELECT id, email, password, is_active, can_login, role FROM users WHERE email = :email");
$stmt->execute(['email' => $email]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = "Invalid email or password.";
} 
elseif (!$user['is_active']) {
    $user_id = $user['id'];
    $_SESSION['error'] = "Account not activated.";
} 
elseif (!$user['can_login']) {
    $user_id = $user['id'];
    $_SESSION['error'] = "Your account is blocked.";
} 
elseif (!password_verify($password, $user['password'])) {
    $user_id = $user['id'];
    $_SESSION['error'] = "Invalid email or password.";
} 
else {
    $user_id = $user['id'];
    $success = 1;
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['email'] = $user['email'];
    $_SESSION['role'] = $user['role'];
}

$logStmt = $pdo->prepare("INSERT INTO visitor_logs (user_id, ip_address, device_type, browser, country, city, isp, success) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
$logStmt->execute([$user_id, $ip_address, $device_type, $browser, $country, 'N/A', $isp, $success]);

if ($success) {
    if ($_SESSION['role'] === 'user') header("Location: ../user/dashboard.php");
    elseif ($_SESSION['role'] === 'vet') header("Location: ../vet/dashboard.php");
    elseif ($_SESSION['role'] === 'admin') header("Location: ../admin/dashboard.php");
    exit;
} else {
    header('Location: ../auth/login.php');
    exit;
}