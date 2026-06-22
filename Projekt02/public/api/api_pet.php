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

    $stmt = $pdo->prepare("SELECT id FROM users WHERE api_token = ?");
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

function loadImageFlexible($path) {
    if (!file_exists($path)) return imagecreatetruecolor(100, 100);
    $info = getimagesize($path);
    if ($info[2] === IMAGETYPE_JPEG) return imagecreatefromjpeg($path);
    if ($info[2] === IMAGETYPE_PNG) return imagecreatefrompng($path);
    return imagecreatetruecolor(100, 100);
}

function createLogoWithBorder($path, $diameter, $border) {
    $src = loadImageFlexible($path);
    $w = imagesx($src); $h = imagesy($src);
    $size = min($w, $h);
    $square = imagecreatetruecolor($diameter - 2*$border, $diameter - 2*$border);
    imagesavealpha($square, true);
    imagefill($square, 0, 0, imagecolorallocatealpha($square,0,0,0,127));
    imagecopyresampled($square, $src, 0,0, ($w-$size)/2, ($h-$size)/2, $diameter-2*$border, $diameter-2*$border, $size,$size);
    $circle = imagecreatetruecolor($diameter-2*$border, $diameter-2*$border);
    imagesavealpha($circle,true);
    imagefill($circle,0,0,imagecolorallocatealpha($circle,0,0,0,127));
    $r = ($diameter-2*$border)/2;
    for($x=0;$x<$diameter-2*$border;$x++){
        for($y=0;$y<$diameter-2*$border;$y++){
            $dx=$x-$r;$dy=$y-$r;
            if($dx*$dx+($dy*$dy)<=$r*$r) imagesetpixel($circle,$x,$y,imagecolorat($square,$x,$y));
        }
    }
    $final = imagecreatetruecolor($diameter,$diameter);
    imagesavealpha($final,true);
    imagefill($final,0,0,imagecolorallocatealpha($final,0,0,0,127));
    imagefilledellipse($final,$diameter/2,$diameter/2,$diameter,$diameter,imagecolorallocate($final,255,255,255));
    imagecopy($final,$circle,$border,$border,0,0,$diameter-2*$border,$diameter-2*$border);
    imagedestroy($src); imagedestroy($square); imagedestroy($circle);
    return $final;
}

try {
    switch ($action) {
        case 'list_vets':
            $stmt = $pdo->prepare("SELECT id, first_name, last_name FROM users WHERE role = 'vet' ORDER BY last_name ASC");
            $stmt->execute();
            $vets = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'vets' => $vets]);
            break;

        case 'list_pets':
            $stmt = $pdo->prepare("
                SELECT p.*, u.first_name AS vet_first, u.last_name AS vet_last 
                FROM pets p 
                LEFT JOIN users u ON p.vet_id = u.id 
                WHERE p.owner_id = ?
            ");
            $stmt->execute([$userId]);
            echo json_encode(['success' => true, 'pets' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
            break;

        case 'get_pet_details':
            $petId = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT * FROM pets WHERE id = ? AND owner_id = ?");
            $stmt->execute([$petId, $userId]);
            $pet = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => !!$pet, 'pet' => $pet]);
            break;

        case 'get_reminders':
            $stmt = $pdo->prepare("
                SELECT p.name as pet_name, pmr.title as treatment_type, pmr.next_control_date 
                FROM pet_medical_records pmr
                JOIN pets p ON pmr.pet_id = p.id
                WHERE p.owner_id = ? 
                AND pmr.next_control_date >= CURDATE()
                AND pmr.next_control_date <= DATE_ADD(CURDATE(), INTERVAL 7 DAY)
                ORDER BY pmr.next_control_date ASC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $reminder = $stmt->fetch(PDO::FETCH_ASSOC);
            echo json_encode(['success' => true, 'reminder' => $reminder ? $reminder : null]);
            break;

        case 'add_pet':
            $name = $_POST['name'] ?? ($data['name'] ?? '');
            $species = $_POST['species'] ?? ($data['species'] ?? '');
            $breed = $_POST['breed'] ?? ($data['breed'] ?? '');
            $age = $_POST['age'] ?? ($data['age'] ?? 0);
            $vetId = $_POST['vet_id'] ?? ($data['vet_id'] ?? null);
            $imagePath = 'uploads/pet_photos/default_pet.jpg';

            if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
                $targetDir = "../uploads/pet_photos/";
                if (!file_exists($targetDir)) mkdir($targetDir, 0777, true);
                $fileName = time() . '_' . basename($_FILES['pet_image']['name']);
                if (move_uploaded_file($_FILES['pet_image']['tmp_name'], $targetDir . $fileName)) {
                    $imagePath = 'uploads/pet_photos/' . $fileName;
                }
            }
            $stmt = $pdo->prepare("INSERT INTO pets (owner_id, vet_id, name, species, breed, age) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$userId, $vetId, $name, $species, $breed, $age]);
            $newPetId = $pdo->lastInsertId();
            $stmtPhoto = $pdo->prepare("INSERT INTO pet_photos (pet_id, filename) VALUES (?, ?)");
            $stmtPhoto->execute([$newPetId, $imagePath]);
            echo json_encode(['success' => true, 'pet_id' => $newPetId]);
            break;

        case 'edit_pet':
            $petId = $data['pet_id'] ?? 0;
            $name = $data['pet_name'] ?? '';
            $species = $data['species'] ?? '';
            $breed = $data['breed'] ?? '';
            $age = (isset($data['birth_year'])) ? (date('Y') - (int)$data['birth_year']) : ($data['age'] ?? 0);
            $stmt = $pdo->prepare("UPDATE pets SET name = ?, species = ?, breed = ?, age = ? WHERE id = ? AND owner_id = ?");
            $success = $stmt->execute([$name, $species, $breed, $age, $petId, $userId]);
            echo json_encode(['success' => $success]);
            break;

        case 'delete_pet':
            $petId = $data['pet_id'] ?? 0;
            $stmt = $pdo->prepare("DELETE FROM pets WHERE id = ? AND owner_id = ?");
            $success = $stmt->execute([$petId, $userId]);
            echo json_encode(['success' => $success]);
            break;

        case 'generate_qr':
            $petId = $_GET['id'] ?? 0;
            $stmt = $pdo->prepare("SELECT p.id, p.name, ph.filename FROM pets p LEFT JOIN pet_photos ph ON p.id = ph.pet_id WHERE p.id = ? AND p.owner_id = ? LIMIT 1");
            $stmt->execute([$petId, $userId]);
            $pet = $stmt->fetch();
            if (!$pet) {
                echo json_encode(['success' => false, 'error' => 'Pet not found']);
                exit;
            }
            $photoFile = !empty($pet['filename']) ? __DIR__ . "/../" . $pet['filename'] : __DIR__ . "/../uploads/pet_photos/default_pet.jpg";
            $baseUrl = "https://nak.stud.vts.su.ac.rs/index.php";
            $qrText = $baseUrl . "?scanned_pet_id=" . $pet['id'];
            $qrConfig = ["data" => $qrText, "size" => 500, "download" => true, "format" => "png", "config" => ["body" => "circle", "eye" => "frame13", "eyeBall" => "ball14", "bodyColor" => "#000000", "bgColor" => "#ffffff"]];
            $ch = curl_init("https://api.qrcode-monkey.com/qr/custom");
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($qrConfig));
            curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
            $apiRes = json_decode(curl_exec($ch), true);
            curl_close($ch);
            if (!isset($apiRes["imageUrl"])) {
                echo json_encode(['success' => false, 'error' => 'QR API Error']);
                exit;
            }
            $qr = imagecreatefromstring(file_get_contents("https:" . $apiRes["imageUrl"]));
            $qrW = imagesx($qr);
            $logoD = (int)($qrW * 0.28);
            $logo = createLogoWithBorder($photoFile, $logoD, (int)($logoD * 0.12));
            imagecopy($qr, $logo, ($qrW - $logoD) / 2, ($qrW - $logoD) / 2, 0, 0, $logoD, $logoD);
            $relPath = "uploads/qr/pet_{$petId}_QR.png";
            $fullPath = __DIR__ . "/../" . $relPath;
            if (!file_exists(dirname($fullPath))) mkdir(dirname($fullPath), 0777, true);
            imagepng($qr, $fullPath);
            $pdo->prepare("UPDATE pets SET qr_code = ? WHERE id = ?")->execute([$relPath, $petId]);
            imagedestroy($qr); imagedestroy($logo);
            echo json_encode(['success' => true, 'qr_url' => $relPath]);
            break;

        default:
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}