<?php
require_once '../auth/auth_check.php';
require_once __DIR__ . '/../../src/config/database.php';

$name = $_POST['name'] ?? '';
$species = $_POST['species'] ?? '';
$breed = $_POST['breed'] ?? '';
$age = $_POST['age'] ?? '';
$vet_id = $_POST['vet_id'] ?? null;

if (!$name || !$species || !$breed || !$age || !$vet_id) {
    $_SESSION['error'] = "Please fill in all required fields.";
    header("Location: add_pet.php");
    exit;
}

$stmt = $pdo->prepare("INSERT INTO pets (name, species, breed, age, owner_id, vet_id) 
                       VALUES (:name, :species, :breed, :age, :owner_id, :vet_id)");
$stmt->execute([
    'name' => $name,
    'species' => $species,
    'breed' => $breed,
    'age' => $age,
    'owner_id' => $_SESSION['user_id'],
    'vet_id' => $vet_id
]);
$petId = $pdo->lastInsertId();


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

header("Location: ../user/pets.php");
