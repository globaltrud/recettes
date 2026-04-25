<?php
header('Content-Type: application/json');

if ($_FILES['image']) {
    $file = $_FILES['image'];
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $newName = uniqid('img_', true) . '.' . $ext;
    $dest = 'uploads/' . $newName;

    if (move_uploaded_file($file['tmp_name'], $dest)) {
        // Renvoie l'URL de l'image pour que Quill puisse l'afficher
        echo json_encode(['url' => $dest]);
    } else {
        http_response_code(500);
        echo json_encode(['error' => 'Erreur lors du déplacement du fichier']);
    }
}