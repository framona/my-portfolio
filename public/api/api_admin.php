<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/config/database.php';
    $pdo->exec("set names utf8mb4");

    $token = $_GET['api_token'] ?? $_POST['api_token'] ?? null;
    $action = $_GET['action'] ?? $_POST['action'] ?? null;

    if (!$action) throw new Exception('No action provided');
    if (!$token) throw new Exception('Missing API token');

    $stmtAuth = $pdo->prepare("SELECT id FROM users WHERE api_token = ? AND role = 'admin' LIMIT 1");
    $stmtAuth->execute([$token]);
    $admin = $stmtAuth->fetch();

    if (!$admin) {
        http_response_code(401);
        throw new Exception('Unauthorized: Admin access only');
    }

    switch ($action) {
        case 'get_dashboard_stats':
            $stats = [];
            $stats['users'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='user'")->fetchColumn();
            $stats['vets'] = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role='vet'")->fetchColumn();
            $stats['pets'] = (int)$pdo->query("SELECT COUNT(*) FROM pets")->fetchColumn();
            $stats['appointments'] = (int)$pdo->query("SELECT COUNT(*) FROM vet_appointments")->fetchColumn();

            $recentUsers = $pdo->query("SELECT first_name, last_name, email, created_at FROM users ORDER BY created_at DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'stats' => $stats, 'recentUsers' => $recentUsers]);
            break;

        case 'get_vet_stats':
            $vetId = (int)($_GET['vet_id'] ?? 0);
            if ($vetId <= 0) throw new Exception('Invalid Vet ID');

            $vStats = [];
            $stmt1 = $pdo->prepare("SELECT COUNT(*) FROM vet_appointments WHERE vet_id = ?");
            $stmt1->execute([$vetId]);
            $vStats['total'] = (int)$stmt1->fetchColumn();

            $stmt2 = $pdo->prepare("SELECT COUNT(DISTINCT pet_id) FROM vet_appointments WHERE vet_id = ?");
            $stmt2->execute([$vetId]);
            $vStats['animals'] = (int)$stmt2->fetchColumn();

            $stmt3 = $pdo->prepare("SELECT COUNT(*) FROM vet_appointments WHERE vet_id = ? AND appointment_time >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
            $stmt3->execute([$vetId]);
            $vStats['weekly'] = (int)$stmt3->fetchColumn();

            $stmt4 = $pdo->prepare("SELECT COUNT(*) FROM vet_appointments WHERE vet_id = ? AND appointment_time >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
            $stmt4->execute([$vetId]);
            $vStats['monthly'] = (int)$stmt4->fetchColumn();

            echo json_encode(['success' => true, 'stats' => $vStats]);
            break;

        case 'list_users':
            $stmt = $pdo->query("SELECT id, first_name, last_name, email, role, is_active, phone FROM users WHERE role != 'admin' ORDER BY role, last_name");
            echo json_encode(['success' => true, 'users' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'update_user':
            $data = json_decode(file_get_contents("php://input"), true);
            $id = (int)($data['id'] ?? 0);
            $stmt = $pdo->prepare("UPDATE users SET first_name = ?, last_name = ?, email = ?, phone = ? WHERE id = ? AND role != 'admin'");
            $stmt->execute([trim($data['first_name']), trim($data['last_name']), trim($data['email']), trim($data['phone']), $id]);
            echo json_encode(['success' => true, 'message' => 'User updated']);
            break;

        case 'delete_user':
            $id = (int)($_GET['id'] ?? 0);
            $pdo->beginTransaction();
            $pdo->prepare("DELETE FROM login_logs WHERE user_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM vet_appointments WHERE owner_id = ? OR vet_id = ?")->execute([$id, $id]);
            $pdo->prepare("DELETE FROM messages WHERE sender_id = ? OR receiver_id = ?")->execute([$id, $id]);
            $pdo->prepare("DELETE FROM pets WHERE owner_id = ?")->execute([$id]);
            $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'")->execute([$id]);
            $pdo->commit();
            echo json_encode(['success' => true, 'message' => 'User deleted']);
            break;

        case 'toggle_user':
            $id = (int)($_GET['id'] ?? 0);
            $pdo->prepare("UPDATE users SET is_active = NOT is_active WHERE id = ? AND role != 'admin'")->execute([$id]);
            echo json_encode(['success' => true]);
            break;
        
        case 'add_vet':
            $data = json_decode(file_get_contents("php://input"), true);
            
            $firstName = trim($data['first_name'] ?? '');
            $lastName = trim($data['last_name'] ?? '');
            $email = trim($data['email'] ?? '');
            $password = $data['password'] ?? 'vet123'; 
            $phone = trim($data['phone'] ?? '');

            if (empty($firstName) || empty($lastName) || empty($email)) {
                throw new Exception('Missing required fields (name or email)');
            }

            $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check->execute([$email]);
            if ($check->fetch()) {
                throw new Exception('Email already registered');
            }

            $hashedPassword = password_hash($password, PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("
                INSERT INTO users (first_name, last_name, email, password, role, is_active, phone, created_at) 
                VALUES (?, ?, ?, ?, 'vet', 1, ?, NOW())
            ");
            
            $stmt->execute([$firstName, $lastName, $email, $hashedPassword, $phone]);
            
            echo json_encode(['success' => true, 'message' => 'Veterinarian added successfully']);
            break;

        default:
            throw new Exception("Invalid admin action: " . $action);
    }
} catch (Exception $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}