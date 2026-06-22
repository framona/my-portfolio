<?php
header('Content-Type: application/json');
ini_set('display_errors', 0);
error_reporting(E_ALL);

try {
    require_once __DIR__ . '/../../src/config/database.php';
    require_once __DIR__ . '/../actions/send_email.php'; 

    $input = json_decode(file_get_contents('php://input'), true);

    if ($input && isset($input['pet_id'])) {
        $raw_id = $input['pet_id'];
        $pet_id = null;

        if (strpos($raw_id, 'scanned_pet_id=') !== false) {
            $parts = explode('scanned_pet_id=', $raw_id);
            $pet_id = trim($parts[1]);
        } 
        elseif (strpos($raw_id, 'ID:') !== false) {
            $parts = explode("\n", $raw_id);
            $pet_id = trim(str_replace('ID:', '', $parts[0]));
        } 
        else {
            $pet_id = trim($raw_id);
        }

        $stmt = $pdo->prepare("
            SELECT p.name as pet_name, u.email as vet_email, u.last_name as vet_name, u.id as vet_id, ns.notify_email 
            FROM pets p
            JOIN users u ON p.vet_id = u.id
            LEFT JOIN notification_settings ns ON u.id = ns.user_id
            WHERE p.id = ?
        ");
        $stmt->execute([$pet_id]);
        $data = $stmt->fetch();

        if ($data) {
            $success_email = false;
            $lat = $input['latitude'] ?? 'Unknown';
            $lng = $input['longitude'] ?? 'Unknown';
            
            $mapsLink = ($lat !== 'Unknown') 
                ? "https://www.google.com/maps/search/?api=1&query={$lat},{$lng}" 
                : "Location not shared.";

            if ($data['notify_email'] == 1 || $data['notify_email'] === null) {
                $subject = "EMERGENCY: Pet Found - " . $data['pet_name'];
                $body = "
                    <div style='font-family: Arial, sans-serif; border: 1px solid #d9534f; padding: 25px; border-radius: 10px; max-width: 600px;'>
                        <h2 style='color: #d9534f; margin-top: 0;'>Emergency Alert: Pet Scanned</h2>
                        <p>This is an automated notification from <b>PetRegistry</b>.</p>
                        <p>The QR code for <b>{$data['pet_name']}</b> (ID: {$pet_id}) has just been scanned via the Mobile App.</p>
                        
                        <div style='background-color: #f9f9f9; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                            <p style='margin: 0;'><b>Status:</b> Found / Scanned</p>
                            <p style='margin: 5px 0 0 0;'><b>Location:</b> " . ($lat !== 'Unknown' ? "GPS coordinates available" : "Location not shared by the finder") . "</p>
                        </div>

                        <div style='text-align: center; margin-top: 25px;'>
                            <a href='{$mapsLink}' style='background-color: #28a745; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold; display: inline-block;'>View Location on Google Maps</a>
                        </div>
                    </div>
                ";
                $success_email = send_activation_email($data['vet_email'], $subject, $body);
            }

            echo json_encode(['success' => true, 'email_sent' => $success_email]);
        } else {
            echo json_encode(['success' => false, 'error' => 'Pet ID not found: ' . $pet_id]);
        }
    } else {
        echo json_encode(['success' => false, 'error' => 'Invalid input']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}