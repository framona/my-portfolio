<?php
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../src/config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Token alapú azonosítás
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

$authUser = getAuthenticatedUser($pdo);
if (!$authUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$userId = $authUser['id'];
$userRole = $authUser['role'];
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents("php://input"), true);

try {
    if ($method === 'GET') {
        switch ($action) {
            case 'list_owner_appointments':
                $stmt = $pdo->prepare("
                    SELECT a.id, a.appointment_time, a.cancel_message as note, a.pet_id, a.vet_id,
                           p.name AS pet_name, v.first_name AS vet_first, v.last_name AS vet_last
                    FROM vet_appointments a
                    JOIN pets p ON a.pet_id = p.id
                    JOIN users v ON a.vet_id = v.id 
                    WHERE a.owner_id = ? 
                    ORDER BY a.appointment_time DESC
                ");
                $stmt->execute([$userId]);
                $appointments = $stmt->fetchAll(PDO::FETCH_ASSOC);

                $stmtPets = $pdo->prepare("SELECT id, name FROM pets WHERE owner_id = ?");
                $stmtPets->execute([$userId]);
                
                $stmtVets = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'vet'");
                $stmtVets->execute();

                echo json_encode([
                    'success' => true,
                    'appointments' => $appointments,
                    'pets' => $stmtPets->fetchAll(PDO::FETCH_ASSOC),
                    'vets' => $stmtVets->fetchAll(PDO::FETCH_ASSOC)
                ]);
                break;

            case 'list_vet_appointments':
                if ($userRole !== 'vet') { throw new Exception('Only vets can access this.'); }
                
                $stmt = $pdo->prepare("
                    SELECT a.id, a.pet_id, a.appointment_time, a.cancel_message,
                           p.name AS pet_name, u.first_name, u.last_name
                    FROM vet_appointments a
                    JOIN pets p ON a.pet_id = p.id
                    JOIN users u ON a.owner_id = u.id
                    WHERE a.vet_id = ?
                    ORDER BY a.appointment_time ASC
                ");
                $stmt->execute([$userId]);
                echo json_encode(['success' => true, 'appointments' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Invalid GET action']);
        }
    } 
    elseif ($method === 'POST') {
        switch ($action) {
            case 'add':
                $stmt = $pdo->prepare("INSERT INTO vet_appointments (pet_id, vet_id, owner_id, appointment_time, cancel_message) VALUES (?, ?, ?, ?, ?)");
                $success = $stmt->execute([
                    $input['pet_id'], $input['vet_id'], $userId, $input['appointment_date'], $input['note'] ?? ''
                ]);
                echo json_encode(['success' => $success]);
                break;

            case 'update':
            case 'delete':
                $id = $input['appointment_id'];
                $stmtCheck = $pdo->prepare("SELECT appointment_time FROM vet_appointments WHERE id = ? AND owner_id = ?");
                $stmtCheck->execute([$id, $userId]);
                $app = $stmtCheck->fetch();

                if (!$app) {
                    echo json_encode(['success' => false, 'error' => 'Unauthorized or Not Found']);
                    exit;
                }

                if ((strtotime($app['appointment_time']) - time()) < 3600) {
                    echo json_encode(['success' => false, 'error' => 'Action not allowed within 1 hour.']);
                    exit;
                }

                if ($action === 'update') {
                    $stmt = $pdo->prepare("UPDATE vet_appointments SET pet_id = ?, vet_id = ?, appointment_time = ? WHERE id = ?");
                    $success = $stmt->execute([$input['pet_id'], $input['vet_id'], $input['appointment_date'], $id]);
                    echo json_encode(['success' => $success]);
                } else {
                    $stmt = $pdo->prepare("DELETE FROM vet_appointments WHERE id = ?");
                    echo json_encode(['success' => $stmt->execute([$id])]);
                }
                break;

            case 'cancel_by_vet':
                if ($userRole !== 'vet') { throw new Exception('Access denied.'); }
                
                $appointmentId = $input['appointment_id'];
                $reason = $input['reason'] ?? 'No reason provided';

                $stmt = $pdo->prepare("SELECT owner_id, appointment_time FROM vet_appointments WHERE id = ? AND vet_id = ?");
                $stmt->execute([$appointmentId, $userId]);
                $appointment = $stmt->fetch();

                if ($appointment) {
                    $pdo->prepare("UPDATE vet_appointments SET cancel_message = ? WHERE id = ?")->execute([$reason, $appointmentId]);
                    
                    $msg = $pdo->prepare("INSERT INTO messages (sender_id, receiver_id, message, is_read) VALUES (?, ?, ?, 0)");
                    $msgText = "Your appointment on " . $appointment['appointment_time'] . " has been cancelled by the vet. Reason: " . $reason;
                    $msg->execute([$userId, $appointment['owner_id'], $msgText]);

                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'error' => 'Appointment not found']);
                }
                break;

            default:
                echo json_encode(['success' => false, 'error' => 'Invalid POST action']);
        }
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}