<?php
ini_set('display_errors', 0);
error_reporting(E_ALL);
header('Content-Type: application/json; charset=utf-8');

try {
    require_once __DIR__ . '/../../src/config/database.php';
    $pdo->exec("set names utf8mb4");

    $action = $_GET['action'] ?? $_POST['action'] ?? null;
    $token = $_GET['api_token'] ?? null;

    if (!$action) throw new Exception('No action provided');
    if (!$token) throw new Exception('Missing API token');

    $stmtAuth = $pdo->prepare("SELECT id FROM users WHERE api_token = ? AND role = 'vet' LIMIT 1");
    $stmtAuth->execute([$token]);
    $vet = $stmtAuth->fetch(PDO::FETCH_ASSOC);

    if (!$vet) throw new Exception('Invalid token or not a vet');
    $vetId = $vet['id'];

    switch ($action) {
        case 'get_user':
            $stmt = $pdo->prepare("SELECT id, first_name, last_name, email, phone, role FROM users WHERE id = ?");
            $stmt->execute([$vetId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'user' => $user]);
            break;

        case 'get_stats':
            $app_stmt = $pdo->prepare("SELECT COUNT(*) FROM vet_appointments WHERE vet_id = ? AND appointment_time > NOW() AND (cancel_message IS NULL OR cancel_message = '')");
            $app_stmt->execute([$vetId]);
            
            $pet_stmt = $pdo->prepare("SELECT COUNT(DISTINCT pet_id) FROM vet_appointments WHERE vet_id = ?");
            $pet_stmt->execute([$vetId]);
            
            $msg_stmt = $pdo->prepare("SELECT COUNT(*) FROM messages WHERE receiver_id = ? AND is_read = 0");
            $msg_stmt->execute([$vetId]);

            echo json_encode([
                'success' => true,
                'appointments_count' => (int)$app_stmt->fetchColumn(),
                'pets_count' => (int)$pet_stmt->fetchColumn(),
                'messages_count' => (int)$msg_stmt->fetchColumn()
            ]);
            break;

        case 'get_clients':
            $stmt = $pdo->prepare("
                SELECT DISTINCT u.id, u.first_name, u.last_name, 
                GROUP_CONCAT(DISTINCT p.name SEPARATOR ', ') as pet_names
                FROM users u
                JOIN vet_appointments a ON u.id = a.owner_id
                JOIN pets p ON a.pet_id = p.id
                WHERE a.vet_id = ?
                GROUP BY u.id
            ");
            $stmt->execute([$vetId]);
            echo json_encode(['success' => true, 'clients' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_pet_details':
            $pet_id = $_GET['pet_id'] ?? null;
            if (!$pet_id) throw new Exception('Missing pet ID');

            $stmtPet = $pdo->prepare("SELECT * FROM pets WHERE id = ?");
            $stmtPet->execute([$pet_id]);
            $pet = $stmtPet->fetch(PDO::FETCH_ASSOC);

            $stmtTreat = $pdo->prepare("SELECT * FROM pet_medical_records WHERE pet_id = ? ORDER BY created_at DESC");
            $stmtTreat->execute([$pet_id]);
            $treatments = $stmtTreat->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode(['success' => true, 'pet' => $pet, 'treatments' => $treatments]);
            break;

        case 'add_record':
            $data = json_decode(file_get_contents('php://input'), true);
            $petId = $data['pet_id'] ?? null;
            $title = $data['title'] ?? null;
            $description = $data['description'] ?? null;
            $nextDate = !empty($data['next_control_date']) ? $data['next_control_date'] : null;

            if (!$petId || !$title || !$description) throw new Exception('Missing data');

            $stmt = $pdo->prepare("INSERT INTO pet_medical_records (pet_id, vet_id, title, description, next_control_date, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$petId, $vetId, $title, $description, $nextDate]);
            echo json_encode(['success' => true]);
            break;

        default:
            throw new Exception("Invalid action: " . $action);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}