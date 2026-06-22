<?php
session_start();
require_once __DIR__ . '/../../src/config/database.php';
require_once '../auth/auth_check.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../user/pets.php");
    exit;
}

$petId = $_POST['pet_id'] ?? null;
$petName = trim($_POST['pet_name'] ?? '');
$species = trim($_POST['species'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$birthYear = trim($_POST['birth_year'] ?? '');
$age = date('Y') - (int)$birthYear;

if (!$petId || !$petName || !$species || !$breed || !$birthYear) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: ../user/edit_pet.php?id=" . $petId);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM pets WHERE id = :id AND owner_id = :owner_id");
$stmt->execute([
    'id' => $petId,
    'owner_id' => $_SESSION['user_id']
]);
$pet = $stmt->fetch();
if (!$pet) {
    $_SESSION['error'] = "Pet not found or access denied.";
    header("Location: ../user/pets.php");
    exit;
}

try {
    $pdo->beginTransaction();
    $stmt = $pdo->prepare("UPDATE pets SET name = :name, species = :species, breed = :breed, age = :age WHERE id = :id");
    $stmt->execute([
        'name' => $petName,
        'species' => $species,
        'breed' => $breed,
        'age' => $age,
        'id' => $petId
    ]);
    if (isset($_FILES['pet_image']) && $_FILES['pet_image']['error'] === UPLOAD_ERR_OK) {
        $uploadDir = '../uploads/pet_photos/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

        $tmpName = $_FILES['pet_image']['tmp_name'];
        $ext = pathinfo($_FILES['pet_image']['name'], PATHINFO_EXTENSION);
        $filename = uniqid('pet_', true) . "." . $ext;
        $destination = $uploadDir . $filename;

        if (move_uploaded_file($tmpName, $destination)) {
            $stmt = $pdo->prepare("INSERT INTO pet_photos (pet_id, filename, created_at) VALUES (:pet_id, :image_path, NOW())");
            $stmt->execute([
                'pet_id' => $petId,
                'image_path' => 'uploads/pet_photos/' . $filename
            ]);
        } else {
            $_SESSION['error'] = "Failed to upload image.";
            header("Location: ../user/edit_pet.php?id=" . $petId);
            exit;
        }
    }

    $pdo->commit();
    header("Location:../user/pets.php");
    exit;

} catch (Exception $e) {
    $pdo->rollBack();
    $_SESSION['error'] = "Error updating pet: " . $e->getMessage();
    header("Location: ../user/edit_pet.php?id=" . $petId);
    exit;
}
?>
