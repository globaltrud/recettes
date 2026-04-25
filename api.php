<?php
require_once 'db_config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

try {
    switch ($action) {

        case 'list_unites':
            $stmt = $db->query("SELECT * FROM unite ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save_unitessss':
            $unit_name = trim($_POST['unit_name']);
            if($unit_name == ''){
                $unit_name = trim($_POST['name']);
            }
            $stmt = $db->prepare("INSERT INTO unite (name, unit_name, negligeable, insecable, prefixe, suffixe, pour1kg, form_step, form_init_value) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                $unit_name,
                isset($_POST['negligeable']) ? 1 : 0,
                isset($_POST['insecable']) ? 1 : 0,
                $_POST['prefixe'] ?? '',
                $_POST['suffixe'] ?? '',
                (float)$_POST['pour1kg'],
                (float)$_POST['form_step'],
                (float)$_POST['form_init_value']
            ]);
            echo json_encode(['status' => 'success']);
            break;

        //case 'update_unite':
        case 'save_unite':
            // On récupère l'ID s'il existe (pour l'update)
            $id = isset($_POST['id']) && !empty($_POST['id']) ? (int)$_POST['id'] : null;

            $negligeable = isset($_POST['negligeable']) ? 1 : 0;
            $insecable = isset($_POST['insecable']) ? 1 : 0;

            if ($id) {
                // --- MODE UPDATE ---
                $sql = "UPDATE unite SET 
            name = ?, unit_name = ?, pour1kg = ?, 
            form_step = ?, form_init_value = ?, 
            prefixe = ?, suffixe = ?, 
            negligeable = ?, insecable = ? 
        WHERE id = ?";

                $params = [
                    $_POST['name'], $_POST['unit_name'], $_POST['pour1kg'],
                    $_POST['form_step'], $_POST['form_init_value'],
                    $_POST['prefixe'], $_POST['suffixe'],
                    $negligeable, $insecable,  $id
                ];
            } else {
                // --- MODE INSERT (Création) ---
                $sql = "INSERT INTO unite (
            name, unit_name, pour1kg, form_step, 
            form_init_value, prefixe, suffixe, 
            negligeable, insecable
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";

                $params = [
                    $_POST['name'], $_POST['unit_name'], $_POST['pour1kg'],
                    $_POST['form_step'], $_POST['form_init_value'],
                    $_POST['prefixe'], $_POST['suffixe'],
                    $negligeable, $insecable
                ];
            }

            $stmt = $db->prepare($sql);
            $stmt->execute($params);

            echo json_encode(['status' => 'success', 'id' => $id ?: $db->lastInsertId()]);
            break;
            $id = (int)$_POST['id'];
            $negligeable = isset($_POST['negligeable']) ? 1 : 0;
            $insecable = isset($_POST['insecable']) ? 1 : 0;

            $sql = "UPDATE unite SET 
                name = ?, unit_name = ?, pour1kg = ?, 
                form_step = ?, form_init_value = ?, 
                prefixe = ?, suffixe = ?, 
                negligeable = ?, insecable = ? 
            WHERE id = ?";

            $stmt = $db->prepare($sql);
            $dataz = [
                $_POST['name'], $_POST['unit_name'], $_POST['pour1kg'],
                $_POST['form_step'], $_POST['form_init_value'],
                $_POST['prefixe'], $_POST['suffixe'],
                $negligeable, $insecable, $id
            ];
            $stmt->execute();

            echo json_encode(['status' => 'success', 'data' => $dataz]);
            break;

        case 'delete_unite':
            $id = isset($_POST['id']) ? (int)$_POST['id'] : 0;

            // Nouvelle requête pour récupérer les NOMS des recettes qui utilisent cette unité
            $stmt = $db->prepare("
        SELECT DISTINCT r.name 
        FROM recette r
        JOIN recette_ingredients ri ON r.id = ri.id_recette
        WHERE ri.id_unite = ?
    ");
            $stmt->execute([$id]);
            $recettes = $stmt->fetchAll(PDO::FETCH_COLUMN); // Récupère une liste simple de noms

            if (count($recettes) > 0) {
                // On crée une chaîne de caractères avec les noms des recettes
                $listeRecettes = implode(' // ', $recettes);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Impossible ! Cette unité est utilisée dans les recettes suivantes : [ " . $listeRecettes . ' ]'
                ]);
            } else {
                $db->prepare("DELETE FROM unite WHERE id = ?")->execute([$id]);
                echo json_encode([
                    'status' => 'success',
                    'message' => "Unité supprimée !"
                ]);
            }
            break;

        case 'list_recettes':
            // On sélectionne les colonnes de la recette + le compte des ingrédients et des étapes
            $sql = "SELECT r.*, 
            (SELECT COUNT(*) FROM recette_ingredients WHERE id_recette = r.id) as nb_ing,
            (SELECT COUNT(*) FROM recette_etape WHERE recette_id = r.id) as nb_steps
            FROM recette r 
            ORDER BY r.id DESC";
            $stmt = $db->query($sql);
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_all_ingredients':
            $stmt = $db->query("SELECT * FROM ingredient ORDER BY name ASC"); // id, name, category_id, insecable
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'get_all_unites':
            $stmt = $db->query("SELECT * FROM unite ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save_recette':
            // Vérification sommaire des données
            if (empty($_POST['name'])) throw new Exception("Le nom de la recette est obligatoire.");

            $db->beginTransaction();
            $temps = (int)$_POST['temps'];
            $mn_temp = (int)($temps / 5);
            $temp_mod = $temps %5;
            if($mn_temp <= 0){
                $temps = $temp_mod;
            }else{
                $temps = 5 * $mn_temp + ($temp_mod != 0 ? 5 : 0);
            }
            // 1. Insertion de la recette
            $stmt = $db->prepare("INSERT INTO recette (name, nombre_personne, temps_realisation, difficulte, description, theme) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $_POST['name'],
                (int)$_POST['nombre_personne'],
                $temps,
                (float)$_POST['difficulte'],
                $_POST['description'],
                $_POST['theme']

            ]);

            $recetteId = $db->lastInsertId();

            // 2. Insertion des ingrédients (Boucle sur les tableaux envoyés par le formulaire)
            if (!empty($_POST['ing_id'])) {
                $stmtIng = $db->prepare("INSERT INTO recette_ingredients (id_recette, id_ingredient, id_unite, quantite, info_facultative) VALUES (?, ?, ?, ?, ?)");

                foreach ($_POST['ing_id'] as $key => $ingId) {
                    if (empty($ingId)) continue; // On saute si l'ingrédient n'est pas sélectionné

                    $stmtIng->execute([
                        $recetteId,
                        (int)$ingId,
                        (int)$_POST['ing_unit'][$key],
                        (float)$_POST['ing_qty'][$key],
                        $_POST['ing_info'][$key] ?? ''
                    ]);
                }
            }

            $db->commit();
            echo json_encode(['status' => 'success']);
            break;

        case 'get_calcul_recette':
            $id = (int)$_GET['id'];
            $nouveau_pax = (int)$_GET['pax'];

            $stmt = $db->prepare("
                SELECT r.nombre_personne, r.description, ri.quantite, i.name as ing_name, i.insecable, u.name as unit_name, u.negligeable 
                FROM recette r 
                JOIN recette_ingredients ri ON r.id = ri.id_recette
                JOIN ingredient i ON ri.id_ingredient = i.id
                JOIN unite u ON ri.id_unite = u.id
                WHERE r.id = ?
            ");
            $stmt->execute([$id]);
            $lignes = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $resultat = [];
            foreach ($lignes as $l) {
                $ratio = $nouveau_pax / $l['nombre_personne'];
                $q_calculee = $l['quantite'] * $ratio;
                if ($l['insecable'] == 1) $q_calculee = ceil($q_calculee);

                $resultat[] = [
                    'nom' => $l['ing_name'],
                    'quantite' => $q_calculee,
                    'unite' => $l['unit_name'],
                    'negligeable' => $l['negligeable']
                ];
            }
            echo json_encode($resultat);
            break;
// ... (après les autres cases)

        case 'delete_recette':
            $id = (int)$_GET['id'];
            $db->beginTransaction();
            try {
                // On supprime d'abord les liens ingrédients (contrainte FK)
                $stmt1 = $db->prepare("DELETE FROM recette_ingredients WHERE id_recette = ?");
                $stmt1->execute([$id]);

                // Puis la recette
                $stmt2 = $db->prepare("DELETE FROM recette WHERE id = ?");
                $stmt2->execute([$id]);

                $db->commit();
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

        case 'get_recette_complet':
            $id = (int)$_GET['id'];

            // 1. On récupère les infos de base de la recette
            $stmt = $db->prepare("SELECT * FROM recette WHERE id = ?");
            $stmt->execute([$id]);
            $recette = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$recette) {
                echo json_encode(['status' => 'error', 'message' => 'Recette introuvable']);
                break;
            }

            // 2. LA JOINTURE : On récupère les ingrédients AVEC leurs noms et les détails des unités
            // C'est cette requête qui transforme l'id_ingredient 2 en "Oeuf"
            $stmtIng = $db->prepare("
SELECT 
        'get_recette_complete' as req_api,
        ri.quantite, 
        ri.info_facultative, 
        i.id as id_ingredient,
        i.insecable,
        u.id as id_unite,
        i.name as ingredient_name, 
        u.unit_name as unit_symbol, 
        u.prefixe, 
        u.suffixe 
    FROM recette_ingredients ri
    JOIN ingredient i ON ri.id_ingredient = i.id
    JOIN unite u ON ri.id_unite = u.id
    WHERE ri.id_recette = ?
    ");
            $stmtIng->execute([$id]);
            $recette['ingredients'] = $stmtIng->fetchAll(PDO::FETCH_ASSOC);

            // 3. Les étapes
            $stmtSteps = $db->prepare("SELECT id, type_texte, contenu, priorite FROM recette_etape WHERE recette_id = ? ORDER BY priorite ASC");
            $stmtSteps->execute([$id]);
            $recette['etapes'] = $stmtSteps->fetchAll(PDO::FETCH_ASSOC);

            echo json_encode($recette);
            break;

        case 'update_recette':
            // Similaire à save_recette mais avec un UPDATE et un nettoyage préalable
            $id = (int)$_POST['id_recette'];
            $theme = $_POST['theme'] ?? 'theme-plat';
            $db->beginTransaction();
            try {

                $temps = (int)$_POST['temps'];
                $mn_temp = (int)($temps / 5);
                $temp_mod = $temps %5;
                if($mn_temp <= 0){
                    $temps = $temp_mod;
                }else{
                    $temps = 5 * $mn_temp + ($temp_mod != 0 ? 5 : 0);
                }


                $stmt = $db->prepare("UPDATE recette SET name = ?, nombre_personne = ?, temps_realisation = ?, difficulte = ?, description = ?, theme= ? WHERE id = ?");
                $stmt->execute([$_POST['name'], (int)$_POST['nombre_personne'], $temps, (float)$_POST['difficulte'], $_POST['description'], $_POST['theme'], $id]);

                // On nettoie les anciens ingrédients pour réinsérer les nouveaux (plus simple que de checker chaque ligne)
                $db->prepare("DELETE FROM recette_ingredients WHERE id_recette = ?")->execute([$id]);

                if (!empty($_POST['ing_id'])) {
                    $stmtIng = $db->prepare("INSERT INTO recette_ingredients (id_recette, id_ingredient, id_unite, quantite, info_facultative) VALUES (?, ?, ?, ?, ?)");
                    foreach ($_POST['ing_id'] as $k => $ingId) {
                        if (empty($ingId)) continue;
                        $stmtIng->execute([$id, (int)$ingId, (int)$_POST['ing_unit'][$k], (float)$_POST['ing_qty'][$k], $_POST['ing_info'][$k]]);
                    }
                }
// 2. --- NOUVEAU : Gestion des Étapes ---
                $db->prepare("DELETE FROM recette_etape WHERE recette_id = ?")->execute([$id]);

                if (!empty($_POST['etape_contenu'])) {
                    $stmtStep = $db->prepare("INSERT INTO recette_etape (recette_id, type_texte, contenu, priorite) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['etape_contenu'] as $key => $contenu) {
                        // On n'enregistre pas les étapes vides (ex: <p><br></p>)
                        if (empty(strip_tags($contenu)) && strpos($contenu, '<img') === false) continue;

                        $stmtStep->execute([
                            $id,
                            $_POST['etape_type'][$key], // 'étape' ou 'astuce'
                            $contenu,                   // Le HTML de Quill
                            ($key + 1)                  // La priorité basée sur l'ordre du formulaire
                        ]);
                    }
                }
                $db->commit();
                echo json_encode(['status' => 'success']);
            } catch (Exception $e) {
                $db->rollBack();
                throw $e;
            }
            break;

// Dans api.php, ajoute ce cas :
        case 'upload_image':
            if (!isset($_FILES['image'])) {
                throw new Exception("Aucune image reçue.");
            }

            $file = $_FILES['image'];
            $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
            // On génère un nom unique pour éviter les doublons
            $fileName = uniqid('img_') . '.' . $ext;
            $uploadDir = 'uploads/';

            if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);

            $destination = $uploadDir . $fileName;

            if (move_uploaded_file($file['tmp_name'], $destination)) {
                echo json_encode([
                    'status' => 'success',
                    'url' => $destination // On renvoie l'URL relative
                ]);
            } else {
                throw new Exception("Erreur lors de l'enregistrement du fichier.");
            }
            break;
        default:
            throw new Exception("Action non reconnue : " . $action);
    }
} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
exit;