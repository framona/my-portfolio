<?php
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../../src/config/database.php';

$pdo->exec("set names utf8mb4");

$input = json_decode(file_get_contents('php://input'), true);

$headers = getallheaders();
$authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
$token = '';

if (preg_match('/Bearer\s(\S+)/', $authHeader, $matches)) {
    $token = $matches[1];
} elseif (isset($_GET['api_token'])) {
    $token = $_GET['api_token'];
}

if (!$token) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'No token provided']);
    exit;
}

$stmtUser = $pdo->prepare("SELECT id FROM users WHERE api_token = ? LIMIT 1");
$stmtUser->execute([$token]);
$authUser = $stmtUser->fetch(PDO::FETCH_ASSOC);

if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Invalid token']);
    exit;
}

$currentUserId = (int)$authUser['id'];
$action = $_GET['action'] ?? ($input['action'] ?? '');

try {
    if ($action === 'get_messages') {
        $other_id = (int)($_GET['other_id'] ?? ($input['other_id'] ?? 0));

        if ($other_id <= 0) {
            echo json_encode(['success' => false, 'error' => 'Invalid ID']);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT id, sender_id, receiver_id, message, is_read, created_at 
            FROM messages 
            WHERE (sender_id = :me AND receiver_id = :other)
               OR (sender_id = :other AND receiver_id = :me)
            ORDER BY created_at ASC
        ");
        
        $stmt->execute([
            'me' => $currentUserId, 
            'other' => $other_id
        ]);
        
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $update = $pdo->prepare("UPDATE messages SET is_read = 1 WHERE sender_id = ? AND receiver_id = ? AND is_read = 0");
        $update->execute([$other_id, $currentUserId]);

        echo json_encode(['success' => true, 'messages' => $messages]);
        exit;
    }

    if ($action === 'send') {
        $receiver = (int)($input['receiver_id'] ?? 0);
        $msg = isset($input['message']) ? trim($input['message']) : '';

        if ($receiver <= 0 || $msg === '') {
            echo json_encode(['success' => false, 'error' => 'Missing data']);
            exit;
        }

        $stmt = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read, created_at) VALUES (?, ?, ?, 0, NOW())");
        $success = $stmt->execute([$currentUserId, $receiver, $msg]);
        
        echo json_encode(['success' => (bool)$success]);
        exit;
    }

    echo json_encode(['success' => false, 'error' => 'Invalid action']);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Server error']);
}