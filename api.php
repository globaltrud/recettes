<?php
/** @var PDO $db */
require_once 'db_config.php';
header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

function loremSentences($nbSentences = 1) {
    $words = explode(' ', 'recette cuisine ingrédient cuisson four poêle casserole mélange préparation découpe assaisonnement sel poivre épices herbes ail oignon tomate beurre huile couille sucre farine pâte levure crème lait fromage viande poisson légumes bouillon marinade griller rôtir mijoter dresser servir dégustation saveur parfum texture croquant fondant saveur délicate dans de de infusée blanche huile trfufe pression. du balsamique caramélisée saveurs plat réductionde le de la les du rehausse noisette légère contraste fruits croustillant avec émulsion traditionnelles artisanale saveurs pâtisserie combine innovantes boulangerie la développe profondeur fermentation dans aromatique lente ancienne');
    $sentences = [];

    for ($i = 0; $i < $nbSentences; $i++) {
        $length = rand(6, 12);
        $sentence = [];

        for ($j = 0; $j < $length; $j++) {
            $sentence[] = $words[array_rand($words)];
        }

        $sentences[] = ucfirst(implode(' ', $sentence)) . '.';
    }

    return implode(' ', $sentences);
}
try {
    switch ($action) {
        // --- Dans api.php ---

        case 'list_categories':
            // On récupère tout, on triera côté client ou via une fonction récursive
            $stmt = $db->query("SELECT * FROM categorie ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save_category':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'];
            $parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

            if ($id) {
                // Empêcher qu'une catégorie soit son propre parent
                if ($id == $parent_id) {
                    echo json_encode(['success' => false, 'error' => "Une catégorie ne peut pas être son propre parent."]);
                    break;
                }
                $stmt = $db->prepare("UPDATE categorie SET name = ?, parent_id = ? WHERE id = ?");
                $stmt->execute([$name, $parent_id, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO categorie (name, parent_id) VALUES (?, ?)");
                $stmt->execute([$name, $parent_id]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_category':
            $id = $_POST['id'];
            // Optionnel : vérifier si la catégorie a des enfants avant de supprimer
            $check = $db->prepare("SELECT COUNT(*) FROM categorie WHERE parent_id = ?");
            $check->execute([$id]);
            if ($check->fetchColumn() > 0) {
                echo json_encode(['success' => false, 'error' => "Cette catégorie contient des sous-catégories. Supprimez-les d'abord."]);
            } else {
                $stmt = $db->prepare("DELETE FROM categorie WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true]);
            }
            break;

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

        case "lorem":

            $count_word = isset($_GET['count_word']) && !empty($_GET['count_word']) ? (int)$_GET['count_word'] : 30;
            $loremText = "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Sed do eiusmod tempor incididunt ut labore et dolore magna aliqua. Ut enim ad minim veniam, quis nostrud exercitation ullamco laboris nisi ut aliquip ex ea commodo consequat.";

            // Tu peux même faire un truc plus sympa avec des balises HTML
            $htmlLorem = "<p>" . loremSentences($count_word) . "</p>";

            echo json_encode(['text' => $htmlLorem]);
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
            /*$id = (int)$_POST['id'];
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
*/
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

        case 'old_save_recette':
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
            $stmt = $db->prepare("INSERT INTO recette (name, nombre_personne, temps_realisation, difficulte, description, theme) VALUES (?, ?, ?, ?, ?, ?)");
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

        case 'old2_save_recette':
            $id = $_POST['id'] ?? null;
            $name = $_POST['name'];
            $description = $_POST['description'];
            $nb = $_POST['nombre_personne'];
            $temps = $_POST['temps'];
            $diff = $_POST['difficulte'];
            $theme = $_POST['theme'] ?? 'theme-plat';
            $koi = '';
            if ($id) {
                // --- MODE ÉDITION ---
                $stmt = $db->prepare("UPDATE recette SET name=?, description=?, nombre_personne=?, temps_realisation=?, difficulte=?, theme=? WHERE id=?");
                $stmt->execute([$name, $description, $nb, $temps, $diff, $theme, $id]);
                $koi .= '// --- MODE ÉDITION ---';
            } else {
                // --- MODE CRÉATION ---
                $stmt = $db->prepare("INSERT INTO recette (name, description, nombre_personne, temps_realisation, difficulte, theme) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$name, $description, $nb, $temps, $diff, $theme]);
                // CRITIQUE : On récupère l'ID qui vient d'être créé pour l'utiliser après
                $id = $db->lastInsertId();
                $koi .= '// --- MODE CRÉATION ---';
            }

            // 1. GESTION DES INGRÉDIENTS (On vide et on remplit)
            $db->prepare("DELETE FROM recette_ingredients WHERE id_recette = ?")->execute([$id]);
            if (isset($_POST['ingredient_id'])) {
                foreach ($_POST['ingredient_id'] as $k => $ingId) {
                    $koi .= " $k ?";
                    if (!$ingId) continue;
                    $koi .= " $k $id, $ingId, " . $_POST['id_unite'][$k] .", " . $_POST['quantite'][$k]. "---------";
                    $stmt = $db->prepare("INSERT INTO recette_ingredients (id_recette, id_ingredient, id_unite, quantite) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $ingId, $_POST['id_unite'][$k], $_POST['quantite'][$k]]);
                }
            }

            // 2. GESTION DES ÉTAPES (C'est ici que ça bloquait !)
            // On supprime les anciennes étapes pour cet ID (propre en création comme en édition)
            $db->prepare("DELETE FROM recette_etape WHERE recette_id = ?")->execute([$id]);

            if (isset($_POST['etape_contenu'])) {
                foreach ($_POST['etape_contenu'] as $k => $contenu) {
                    if (empty(trim($contenu))) continue;

                    $type = $_POST['etape_type'][$k] ?? 'étape';
                    $priorite = $k + 1; // L'ordre du formulaire
                    $koi .= " $id, $contenu, $type, $priorite  !!!!!!!";
                    $stmt = $db->prepare("INSERT INTO recette_etape (recette_id, contenu, type_texte, priorite) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$id, $contenu, $type, $priorite]);
                }
            }

            echo json_encode(['success' => true, 'id' => $id, 'log'=>$koi]);
            break;

        case 'save_recette':
            try {
                $db->beginTransaction();

                $id = !empty($_POST['id']) ? $_POST['id'] : null;
                $name = $_POST['name'];
                $description = $_POST['description'];
                $nb = (int)$_POST['nombre_personne'];
                $diff = (float)$_POST['difficulte'];
                $theme = $_POST['theme'] ?? 'theme-plat';

                // Logique de calcul du temps (extraite de ton ancien code)
                $temps_brut = (int)$_POST['temps'];
                $mn_temp = (int)($temps_brut / 5);
                $temp_mod = $temps_brut % 5;
                $temps = ($mn_temp <= 0) ? $temp_mod : (5 * $mn_temp + ($temp_mod != 0 ? 5 : 0));

                if ($id) {
                    // --- MODE ÉDITION ---
                    $stmt = $db->prepare("UPDATE recette SET name=?, description=?, nombre_personne=?, temps_realisation=?, difficulte=?, theme=? WHERE id=?");
                    $stmt->execute([$name, $description, $nb, $temps, $diff, $theme, $id]);
                } else {
                    // --- MODE CRÉATION ---
                    $stmt = $db->prepare("INSERT INTO recette (name, description, nombre_personne, temps_realisation, difficulte, theme) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$name, $description, $nb, $temps, $diff, $theme]);
                    $id = $db->lastInsertId();
                }

                // 1. GESTION DES INGRÉDIENTS (Noms calqués sur ton index.php)
                $db->prepare("DELETE FROM recette_ingredients WHERE id_recette = ?")->execute([$id]);

                if (isset($_POST['ing_id']) && is_array($_POST['ing_id'])) {
                    $stmtIng = $db->prepare("INSERT INTO recette_ingredients (id_recette, id_ingredient, id_unite, quantite, info_facultative) VALUES (?, ?, ?, ?, ?)");
                    foreach ($_POST['ing_id'] as $k => $ingId) {
                        if (empty($ingId)) continue;

                        $stmtIng->execute([
                            $id,
                            (int)$ingId,
                            (int)$_POST['ing_unit'][$k],
                            (float)$_POST['ing_qty'][$k],
                            $_POST['ing_info'][$k] ?? ''
                        ]);
                    }
                }

                // 2. GESTION DES ÉTAPES
                $db->prepare("DELETE FROM recette_etape WHERE recette_id = ?")->execute([$id]);

                if (isset($_POST['etape_contenu']) && is_array($_POST['etape_contenu'])) {
                    $stmtEtape = $db->prepare("INSERT INTO recette_etape (recette_id, contenu, type_texte, priorite) VALUES (?, ?, ?, ?)");
                    foreach ($_POST['etape_contenu'] as $k => $contenu) {
                        if (empty(trim($contenu))) continue;

                        $type = $_POST['etape_type'][$k] ?? 'étape';
                        $priorite = $k + 1;
                        $stmtEtape->execute([$id, $contenu, $type, $priorite]);
                    }
                }

                $db->commit();
                echo json_encode(['success' => true, 'id' => $id]);

            } catch (Exception $e) {
                if ($db->inTransaction()) $db->rollBack();
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
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
        CASE 
        WHEN i.insecable = 1 OR u.insecable = 1 THEN 1
        ELSE 0
    END AS insecable,
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

        case 'get_all_ingredients_full':
            $stmt = $db->query("SELECT ingredient.*, categorie.name as category_name 
                        FROM ingredient 
                        LEFT JOIN categorie ON ingredient.category_id = categorie.id 
                        ORDER BY ingredient.name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save_ingredient':
            $id = $_POST['id'] ?? '';
            $name = $_POST['name'];
            $category_id = $_POST['category_id'];
            $img_src = $_POST['img_src'];
            $description = $_POST['description'];
            $insecable = isset($_POST['insecable']) ? 1 : 0;

            if ($id) {
                $stmt = $db->prepare("UPDATE ingredient SET name=?, category_id=?, img_src=?, description=?, insecable=? WHERE id=?");
                $stmt->execute([$name, $category_id, $img_src, $description, $insecable, $id]);
            } else {
                $stmt = $db->prepare("INSERT INTO ingredient (name, category_id, img_src, description, insecable) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$name, $category_id, $img_src, $description, $insecable]);
            }
            echo json_encode(['success' => true]);
            break;

        case 'delete_ingredient':
            $id = $_POST['id'];
            $stmt = $db->prepare("DELETE FROM ingredient WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true]);
            break;

        case 'get_categories':
            $stmt = $db->query("SELECT * FROM categorie ORDER BY name ASC");
            echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));
            break;

        case 'save_category_old':
            $name = $_POST['name'] ?? '';
            if ($name) {
                try {
                    $stmt = $db->prepare("INSERT INTO categorie (name) VALUES (?)");
                    $stmt->execute([$name]);
                    echo json_encode(['success' => true]);
                } catch (Exception $e) {
                    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
                }
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