<?php
error_reporting(0);
ini_set('display_errors', 0);

session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../actions/send_email.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS, PUT, DELETE');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');

use Detection\MobileDetect;
$detect = new MobileDetect;

function logVisitor($pdo, $userId, $detect, $success = 1) {
    $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    if ($ipAddress == '::1' || $ipAddress == '127.0.0.1') {
        $ipAddress = '8.8.8.8'; 
    }

    $deviceType = ($detect->isMobile() ? ($detect->isTablet() ? 'tablet' : 'phone') : 'computer');
    $browser = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';

    $url = "https://proxycheck.io/v2/$ipAddress?vpn=1&asn=1";
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $response = curl_exec($ch);
    curl_close($ch);

    $geoData = json_decode($response, true);
    $country = 'Unknown';
    $isp = 'Unknown';
    $city = 'N/A';

    if ($geoData && isset($geoData[$ipAddress])) {
        $info = $geoData[$ipAddress];
        $country = $info['country'] ?? 'Unknown';
        $isp = $info['isp'] ?? 'Unknown';
        $city = $info['city'] ?? 'N/A';
    }

    $stmt = $pdo->prepare("INSERT INTO visitor_logs (user_id, ip_address, device_type, browser, country, city, isp, success, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
    $stmt->execute([$userId, $ipAddress, $deviceType, $browser, $country, $city, $isp, $success]);
}

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$action = $_GET['action'] ?? '';
$data = json_decode(file_get_contents("php://input"), true);

function getAuthenticatedUser($pdo) {
    $headers = getallheaders();
    $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (empty($authHeader) && isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
    }
    if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
        $token = $matches[1];
    } elseif (isset($_GET['api_token'])) {
        $token = $_GET['api_token'];
    } else {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id, role FROM users WHERE api_token = ?");
    $stmt->execute([$token]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

try {
    switch ($action) {
        case 'login':
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? '';
            $success = false;
            $token = null;
            $userData = null;
            $error = null;

            $stmt = $pdo->prepare("SELECT id, email, password, is_active, role, first_name FROM users WHERE email = :email");
            $stmt->execute(['email' => $email]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user || !password_verify($password, $user['password'])) {
                $error = 'Invalid email or password.';
                logVisitor($pdo, $user['id'] ?? null, $detect, 0);
            } elseif (!$user['is_active']) {
                $error = 'Account is not activated yet.';
                logVisitor($pdo, $user['id'], $detect, 0);
            } else {
                $success = true;
                $token = bin2hex(random_bytes(32)); 
                $update = $pdo->prepare("UPDATE users SET api_token = ? WHERE id = ?");
                $update->execute([$token, $user['id']]);
                
                $userData = [
                    'id' => $user['id'],
                    'email' => $user['email'],
                    'role' => $user['role'],
                    'first_name' => $user['first_name']
                ];
                logVisitor($pdo, $user['id'], $detect, 1);
            }

            http_response_code($success ? 200 : 401);
            echo json_encode([
                'success' => $success, 
                'error' => $error, 
                'token' => $token,
                'user' => $userData 
            ]);
            break;

        case 'get_user':
            $authUser = getAuthenticatedUser($pdo);
            if (!$authUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT id, email, first_name, last_name, phone, role FROM users WHERE id = ?");
            $stmt->execute([$authUser['id']]);
            echo json_encode(['success' => true, 'user' => $stmt->fetch(PDO::FETCH_ASSOC)]);
            break;

        case 'change_password':
            $authUser = getAuthenticatedUser($pdo);
            if (!$authUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$authUser['id']]);
            $dbUser = $stmt->fetch(PDO::FETCH_ASSOC);
            if (password_verify($data['current_password'], $dbUser['password'])) {
                $newHashed = password_hash($data['new_password'], PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$newHashed, $authUser['id']]);
                echo json_encode(['success' => true, 'message' => 'Password changed successfully.']);
            } else {
                echo json_encode(['success' => false, 'error' => 'Current password wrong']);
            }
            break;

        case 'update_profile':
            $authUser = getAuthenticatedUser($pdo);
            if (!$authUser) {
                http_response_code(401);
                echo json_encode(['success' => false, 'error' => 'Unauthorized']);
                exit;
            }
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ?");
            $result = $stmt->execute([$data['first_name'], $data['last_name'], $data['email'], $data['phone'], $authUser['id']]);
            echo json_encode(['success' => $result]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Internal server error']);
}