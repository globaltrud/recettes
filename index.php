<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ChefLogistique</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.snow.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/quill@2.0.0/dist/quill.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <style>
        .ql-editor img {
            max-width: 100%;
            height: auto;
            cursor: pointer;
            display: inline-block;
            resize: both;
            overflow: hidden;
            vertical-align: bottom;
            border: 1px solid transparent;
        }
        .ql-editor img:active, .ql-editor img:focus {
            border: 1px dashed #0d6efd;
            outline: none;
        }
        .ql-container.ql-snow { border: none !important; height: auto !important; }
        .ql-editor { min-height: 100px; height: auto !important; }
        .etape-row { margin-bottom: 20px; }
        /* --- RECIPE VIEWER THEMES --- */
        :root {
            --theme-main: #fd7e14; /* Orange pour les plats par défaut */
            --theme-light: #fff3cd;
        }

        .theme-entree {
            --theme-main: #20c997; /* Vert d'eau */
            --theme-light: #d1e7dd;
        }

        .theme-apero {
            --theme-main: #23a61a; /* Vert d'eau */
            --theme-light: #99ed92;
        }

        .theme-plat {
            --theme-main: #fd7e14; /* Orange */
            --theme-light: #ffe5d0;
        }

        .theme-dessert {
            --theme-main: #d63384; /* Rose/Framboise */
            --theme-light: #f8d7da;
        }

        .theme-mer {
            --theme-main: #0077be;
            --theme-light: #e0f0ff;
        }
        .recipe-header {
            background: linear-gradient(135deg, var(--theme-main) 0%, rgba(255,255,255,0) 100%), #343a40;
            color: white;
            border-radius: 0.5rem 0.5rem 0 0;
        }

        .recipe-badge {
            background-color: var(--theme-light);
            color: var(--theme-main);
            font-weight: bold;
        }

        .ingredient-item {
            border-bottom: 1px dashed #dee2e6;
            padding: 0.5rem 0;
        }

        .step-number {
            display: inline-block;
            width: 35px;
            height: 35px;
            line-height: 30px;
            text-align: center;
            background-color: var(--theme-main);
            color: white;
            border-radius: 50%;
            font-weight: bold;
            margin-right: 10px;
        }
        /* Supprime le fond blanc et les bordures sur le container de lecture */
        .ql-container.ql-snow.view-mode {
            border: none !important;
            background-color: transparent !important;
        }

        /* Assure que le contenu lui-même est transparent */
        .ql-container.ql-snow.view-mode .ql-editor {
            background-color: transparent !important;
            padding: 0; /* Optionnel : pour aligner parfaitement avec tes titres */
            color: inherit; /* Pour qu'il prenne la couleur du texte de ta modale/div */
            color: #333; /* Forcer une couleur de texte sombre en lecture */
            font-size: 1.3em;
        }




    </style>
</head>
<body class="bg-light">
<div class="toast-container position-fixed bottom-0 end-0 p-3" style="z-index: 1100">
    <div id="liveToast" class="toast align-items-center text-white border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
            <div class="toast-body" id="toastMessage">
            </div>
            <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
    </div>
</div>
<nav class="navbar navbar-dark bg-primary mb-4">
    <div class="container-fluid"><span class="navbar-brand">🍳 ChefLogistique</span></div>
</nav>

<div class="container">
    <div id="main-menu" class="row g-3">
        <div class="col-4"><button class="btn btn-lg btn-outline-primary w-100 p-5" onclick="loadRecettes()">Gérer Recettes</button></div>
        <div class="col-4"><button class="btn btn-lg btn-outline-success w-100 p-5" onclick="initCourses()">Faire Liste Courses</button></div>
        <div class="col-4"><button class="btn btn-lg btn-outline-info w-100 p-4" onclick="loadUnites()">Gérer Unités</button></div>
    </div>
    <hr>
    <div id="app-content" class="mt-4"></div>
</div>

<div class="modal fade" id="modalSearchIng" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Rechercher un ingrédient</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="text" id="inputSearchIng" class="form-control form-control-lg mb-3" placeholder="Tapez votre recherche...">
                <div id="resultsSearchIng" class="list-group" style="max-height: 300px; overflow-y: auto;"></div>
            </div>
        </div>
    </div>
</div>

<script>
    let currentSort = { col: 'name', asc: true };

    function sortData(data, column) {
        // Si on clique sur la même colonne, on inverse l'ordre
        if (currentSort.col === column) {
            currentSort.asc = !currentSort.asc;
        } else {
            currentSort.col = column;
            currentSort.asc = true;
        }

        return data.sort((a, b) => {
            let valA = a[column];
            let valB = b[column];

            // Gestion des nombres (pour temps, nb_ing, etc.)
            if (!isNaN(valA) && !isNaN(valB)) {
                return currentSort.asc ? valA - valB : valB - valA;
            }

            // Gestion du texte (pour le nom)
            valA = (valA || "").toString().toLowerCase();
            valB = (valB || "").toString().toLowerCase();

            if (valA < valB) return currentSort.asc ? -1 : 1;
            if (valA > valB) return currentSort.asc ? 1 : -1;
            return 0;
        });
    }

    let editors = {};
    let allIngredients = [];
    let currentRowEl = null;

    // --- HELPERS ---
    function formatDifficulte(score, maxval = 5) {
        let note = score * maxval;
        let html = '<span class="text-danger">';
        for (let i = 1; i <= maxval; i++) {
            if (note >= i) html += '<i class="bi bi-star-fill"></i>';
            else if (note >= i - 0.5) html += '<i class="bi bi-star-half"></i>';
            else html += '<i class="bi bi-star"></i>';
        }
        return html + '</span>';
    }

    function formatDuree(minutes) {
        if (minutes < 60) return minutes + " min";
        let h = Math.floor(minutes / 60);
        let m = minutes % 60;
        return m === 0 ? h + "h" : h + "h" + (m < 10 ? "0" + m : m);
    }

    // --- Unités ---

    // --- GESTION DES UNITÉS ---
    function editUnite(u) {
        // 1. On remplit tous les champs du formulaire avec les données de l'unité 'u'
        const form = $('#form-add-unite');
        form.find('input[name="name"]').val(u.name);
        form.find('input[name="unit_name"]').val(u.unit_name);
        form.find('input[name="pour1kg"]').val(u.pour1kg);
        form.find('input[name="form_step"]').val(u.form_step);
        form.find('input[name="form_init_value"]').val(u.form_init_value);
        form.find('input[name="prefixe"]').val(u.prefixe);
        form.find('input[name="suffixe"]').val(u.suffixe);

        // Checkbox (booléens)
        form.find('input[name="negligeable"]').prop('checked', u.negligeable == 1);
        form.find('input[name="insecable"]').prop('checked', u.insecable == 1);

        // 2. On transforme le formulaire pour le mode "Edition"
        // On ajoute un champ caché pour l'ID s'il n'existe pas
        if (form.find('input[name="id"]').length === 0) {
            form.append('<input type="hidden" name="id">');
        }
        form.find('input[name="id"]').val(u.id);

        // 3. On change le look du bouton et le titre de la carte
        form.find('button[type="submit"]').text('Modifier').removeClass('btn-success').addClass('btn-primary');
        $('.card-header.bg-info h4').html('<i class="bi bi-pencil"></i> Modifier l\'unité #' + u.id);

        // Optionnel : scroll vers le formulaire
        window.scrollTo(0, 0);
    }

    function loadUnites() {
        $('#app-content').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        $.get('api.php?action=get_all_unites', function (data) {
            let html = `
            <div class="card shadow mb-4">
                <div class="card-header bg-info text-white">
                    <h4 class="mb-0"><i class="bi bi-plus-circle"></i> Configurer une unité</h4>
                </div>
                <div class="card-body">
                    <form id="form-add-unite" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nom (affichage)</label>
                            <input type="text" name="name" class="form-control" placeholder="ex: Grammes" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Unit Name (code)</label>
                            <input type="text" name="unit_name" class="form-control" placeholder="ex: g" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Pour 1kg/L</label>
                            <input type="number" name="pour1kg" class="form-control" step="0.000001" value="1000" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Pas (Step)</label>
                            <input type="number" name="form_step" class="form-control" step="0.001" value="1">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Valeur Init.</label>
                            <input type="number" name="form_init_value" class="form-control" step="0.001" value="0">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Préfixe</label>
                            <input type="text" name="prefixe" class="form-control" placeholder="ex: (">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Suffixe</label>
                            <input type="text" name="suffixe" class="form-control" placeholder="ex: )">
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="negligeable" value="1" id="checkNeg">
                                <label class="form-check-label" for="checkNeg">Négligeable</label>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-check mt-4">
                                <input class="form-check-input" type="checkbox" name="insecable" value="1" id="checkIns">
                                <label class="form-check-label" for="checkIns">Insécable</label>
                            </div>
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-success w-100">Enregistrer</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover bg-white shadow-sm rounded">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom complet</th>
                            <th>Abrégé</th>
                            <th>Préfixe</th>
                            <th>Suffixe</th>
                            <th>Ratio / 1Kg</th>
                            <th>Step formulaire</th>
                            <th>Valeur initiale formulaire</th>

                            <th>Propriétés</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.forEach(u => {
                html += `<tr>
                <td class="fw-bold">${u.name}</td>
                <td><span class="badge bg-secondary">${u.unit_name}</span></td>
                <td><small class="text-muted">${u.prefixe || ''}</small></td>
                <td><small class="text-muted">${u.suffixe || ''}</small></td>
                <td>${u.pour1kg}</td>
                <td>${u.form_step}</td>
                <td>${u.form_init_value}</td>

                <td>
                    ${u.negligeable == 1 ? '<span class="badge bg-warning text-dark">Néglig.</span>' : ''}
                    ${u.insecable == 1 ? '<span class="badge bg-danger">Insécable</span>' : ''}
                </td>
                <td class="text-end">

                    <button class="btn btn-sm btn-outline-primary me-1" onclick='editUnite(${JSON.stringify(u)})'><i class="bi bi-pencil"></i></button>
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUnite(${u.id}, '${u.name.replace(/'/g, "\\'")}')"><i class="bi bi-trash"></i></button>
                </td>
            </tr>`;
            });

            html += '</tbody></table></div>';
            $('#app-content').html(html);



            $('#form-add-unite').on('submit', function(e) {
                e.preventDefault();
                // Si on a un ID, c'est une update, sinon c'est un ajout
                const isEdit = $(this).find('input[name="id"]').val() !== "";
                const action = 'save_unite'; //isEdit ? 'update_unite' : 'add_unite';


                $.post('api.php?action=' + action, $(this).serialize(), function(res) {
                    if (res.status === 'success') {
                        showNotify(isEdit ? "Unité mise à jour !" : "Unité ajoutée avec succès");
                        loadUnites();
                    } else {
                        showNotify("Erreur : " + res.message, 'error');
                    }
                }, 'json');
            });
            /*
            $('#form-add-unite').on('submit', function(e) {
                e.preventDefault();
                $.post('api.php?action=save_unite', $(this).serialize(), function() {
                    loadUnites();
                });
            });*/
        });
    }

    function old_loadUnites() {
        $('#app-content').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        $.get('api.php?action=get_all_unites', function (data) {
            let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3><i class="bi bi-measuring-cup"></i> Gestion des Unités</h3>
                <button class="btn btn-primary" onclick="showAddUniteForm()">+ Nouvelle Unité</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover bg-white shadow-sm rounded">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Symbole</th>
                            <th>Pas (Step)</th>
                            <th>Type</th>
                            <th>Conv. Base (g/ml)</th>
                            <th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.forEach(u => {
                html += `<tr>
                <td class="fw-bold">${u.name}</td>
                <td><span class="badge bg-info text-dark">${u.symbole || '-'}</span></td>
                <td><code>${u.form_step}</code></td>
                <td><span class="badge bg-light text-dark border">${u.type_unite || 'autre'}</span></td>
                <td>${u.conversion_base ? u.conversion_base : '-'}</td>
                <td class="text-end">
                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUnite(${u.id}, '${u.name.replace(/'/g, "\\'")}')">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`;
            });

            html += '</tbody></table></div>';
            $('#app-content').html(html);
        });
    }

    function showAddUniteForm() {
        // On récupère les infos par étapes (ou tu peux créer une Modal Bootstrap si tu préfères)
        const name = prompt("Nom complet (ex: Grammes) :");
        if (!name) return;

        const symbole = prompt("Symbole (ex: g) :");
        const step = prompt("Pas de mesure (ex: 1 ou 0.1) :", "1");
        const type = prompt("Type (poids / volume / autre) :", "poids");
        const conv = prompt("Valeur de conversion en base (ex: 1000 pour 1kg car base=gramme) :", "1");

        $.post('api.php?action=save_unite', {
            name: name,
            symbole: symbole,
            form_step: step,
            type_unite: type,
            conversion_base: conv
        }, function() {
            loadUnites();
        });
    }

    function deleteUnite(id, name) {
        // On utilise maintenant le paramètre 'name' dans le confirm
        if (confirm("Supprimer l'unité \"" + name + "\" ?\nCette action est irréversible.")) {
            $.post('api.php?action=delete_unite', { id: id }, function(res) {
                if (res.status === 'success') {
                    showNotify("L'unité \"" + name + "\" a été supprimée.");
                    loadUnites();
                } else {
                    showNotify(res.message, 'error');
                }
            }, 'json');
        }
    }

    // --- RECETTES ---
    let lastRecetteData = []; // Pour stocker les données reçues

    function loadRecettes(dataToUse = null) {
        if (!dataToUse) {
            $('#app-content').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
            $.get('api.php?action=list_recettes', function (data) {
                lastRecetteData = data;
                renderRecetteTable(data);
            });
        } else {
            renderRecetteTable(dataToUse);
        }
    }

    function renderRecetteTable(data) {
        const getIcon = (col) => {
            if (currentSort.col !== col) return '<i class="bi bi-arrow-down-up ms-1 text-muted small"></i>';
            return currentSort.asc ? '<i class="bi bi-sort-alpha-down ms-1"></i>' : '<i class="bi bi-sort-alpha-up-alt ms-1"></i>';
        };

        let html = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h3>Gestion des Recettes</h3>
            <button class="btn btn-primary" onclick="showFormRecette(false)">+ Nouvelle Recette</button>
        </div>
        <div class="table-responsive">
            <table class="table table-hover bg-white shadow-sm rounded align-middle">
                <thead class="table-dark">
                    <tr>
                        <th style="cursor:pointer" onclick="resort('name')">Nom ${getIcon('name')}</th>
                        <th style="cursor:pointer" onclick="resort('nombre_personne')">Pers. ${getIcon('nombre_personne')}</th>
                        <th style="cursor:pointer" onclick="resort('temps_realisation')">Temps ${getIcon('temps_realisation')}</th>
                        <th style="cursor:pointer" onclick="resort('difficulte')">Diff. ${getIcon('difficulte')}</th>
                        <th style="cursor:pointer" onclick="resort('nb_ing')">Contenu ${getIcon('nb_ing')}</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>`;

        data.forEach(r => {
            html += `<tr>
            <td class="fw-bold">${r.name}</td>
            <td>${r.nombre_personne}</td>
            <td>${formatDuree(r.temps_realisation)}</td>
            <td>${formatDifficulte(r.difficulte)}</td>
            <td>
                <span class="badge bg-light text-dark border" style="cursor:pointer" onclick="editRecette(${r.id}, 'ing')">
                    <i class="bi bi-cart-fill text-success"></i> ${r.nb_ing || 0}
                </span>
                <span class="badge bg-light text-dark border" style="cursor:pointer" onclick="editRecette(${r.id}, 'steps')">
                    <i class="bi bi-list-ol text-primary"></i> ${r.nb_steps || 0}
                </span>
            </td>
            <td class="text-end">
                <button class="btn btn-sm btn-success" onclick="viewRecette(${r.id})"><i class="bi bi-eye"></i></button>
                <button class="btn btn-sm btn-info" onclick="editRecette(${r.id})"><i class="bi bi-pencil"></i></button>
                <button class="btn btn-sm btn-danger" onclick="deleteRecette(${r.id})"><i class="bi bi-trash"></i></button>
            </td>
        </tr>`;
        });

        html += '</tbody></table></div>';
        $('#app-content').html(html);
    }

    // Petite fonction helper pour déclencher le tri
    function resort(column) {
        const sorted = sortData(lastRecetteData, column);
        loadRecettes(sorted);
    }
    function old_loadRecettes() {
        $('#app-content').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        $.get('api.php?action=list_recettes', function (data) {
            let html = `
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h3>Gestion des Recettes</h3>
                <button class="btn btn-primary" onclick="showFormRecette(false)">+ Nouvelle Recette</button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover bg-white shadow-sm rounded align-middle">
                    <thead class="table-dark">
                        <tr>
                            <th>Nom</th>
                            <th>Pers.</th>
                            <th>Temps</th>
                            <th>Difficulté</th>
                            <th>Ingrédients</th>
                            <th>Ingrédients / Étapes</th>
<th class="text-end">Actions</th>
                        </tr>
                    </thead>
                    <tbody>`;

            data.forEach(r => {
                html += `<tr>
                <td class="fw-bold">${r.name}</td>
                <td><i class="bi bi-person-fill"></i> <strong>x ${r.nombre_personne}</strong></td>
                <td>${formatDuree(r.temps_realisation)}</td>
                <td>${formatDifficulte(r.difficulte)}</td>
                <td>
                    <span class="badge bg-${r.nb_ing < 3 ? 'warning' : 'light'} text-dark border" title="Modifier les ingrédients"
                          style="cursor:pointer" onclick="editRecette(${r.id}, 'ing')">
                        <i class="bi bi-cart-fill text-success"></i> ${r.nb_ing || 0}
                    </span>
                    <span class="badge bg-${r.nb_steps == 0 ? 'danger' : 'light'} text-dark border" title="Modifier la préparation"
                          style="cursor:pointer" onclick="editRecette(${r.id}, 'steps')">
                        <i class="bi bi-list-ol text-primary"></i> ${r.nb_steps || 0}
                    </span>

                </td>
                <td class="text-end">
                    <button class="btn btn-sm btn-success" onclick="viewRecette(${r.id})">
                        <i class="bi bi-eye"></i>
                    </button>
                    <button class="btn btn-sm btn-info" onclick="editRecette(${r.id})">
                        <i class="bi bi-pencil"></i>
                    </button>
                    <button class="btn btn-sm btn-danger" onclick="deleteRecette(${r.id})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            </tr>`;
            });

            html += '</tbody></table></div>';
            $('#app-content').html(html);
        });
    }
    function old_loadRecettes() {
        $('#app-content').html('<div class="text-center"><div class="spinner-border text-primary"></div></div>');
        $.get('api.php?action=list_recettes', function (data) {
            let html = `<div class="d-flex justify-content-between align-items-center mb-3">
                    <h3>Gestion des Recettes</h3>
                    <button class="btn btn-primary" onclick="showFormRecette(false)">+ Nouvelle Recette</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover bg-white shadow-sm rounded">
                        <thead class="table-dark">
                            <tr><th>Nom</th><th>Pers.</th><th>Temps</th><th>Difficulté</th><th>Actions</th></tr>
                        </thead>
                        <tbody>`;
            data.forEach(r => {
                html += `<tr>
                    <td>${r.name}</td>
                    <td>${r.nombre_personne}</td>
                    <td>${formatDuree(r.temps_realisation)}</td>
                    <td>${formatDifficulte(r.difficulte)}</td>
                    <td>

                        <button class="btn btn-sm btn-info" onclick="editRecette(${r.id})">Éditer</button>
                        <button class="btn btn-sm btn-danger" onclick="deleteRecette(${r.id})">Suppr.</button>
                    </td>
                </tr>`;
            });
            html += '</tbody></table></div>';
            $('#app-content').html(html);
        });
    }

    function showFormRecette(isEdit = false) {
        editors = {};
        let html = `
        <div class="card shadow">
            <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                <h4 class="mb-0">${isEdit ? 'Modifier la Recette' : 'Nouvelle Recette'}</h4>
                <button class="btn btn-sm btn-light" onclick="loadRecettes()">Retour</button>
            </div>
            <div class="card-body">
                <form id="form-recette">
<div class="mb-3">
    <label class="form-label">Thème visuel</label>
    <select class="form-select" name="theme" id="recette_theme">
        <option value="theme-plat">Plat</option>
        <option value="theme-entree">Entrée</option>
        <option value="theme-apero">Apéro</option>
        <option value="theme-mer">Poisson</option>
        <option value="theme-dessert">Dessert</option>
    </select>
</div>
                    <div class="row mb-4">
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Nom</label>
                            <input type="text" name="name" class="form-control" required>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Pers.</label>
                            <input type="number" name="nombre_personne" class="form-control" value="2">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label fw-bold">Temps (min)</label>
                            <input type="number" name="temps" class="form-control" value="30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Difficulté (0 à 1)</label>
                            <input type="number" name="difficulte" class="form-control" step="0.01" max="1" value="0.5">
                        </div>
                    </div>
                    <div class="row mb-4">
                        <div class="col-md-12">
                             <label class="form-label fw-bold" for="desc-field">Description</label>
                             <textarea class="form-control" placeholder="Description succinte..." name="description" id="desc-field"></textarea>
                        </div>
                    </div>
                    <ul class="nav nav-tabs mb-3" role="tablist">
                        <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-ing" type="button">Ingrédients</button></li>
                        <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-steps" type="button">Préparation</button></li>
                    </ul>

                    <div class="tab-content border p-3 bg-white rounded">
                        <div class="tab-pane fade show active" id="tab-ing">
                            <div id="ingredients-container"></div>
                            <button type="button" class="btn btn-outline-secondary btn-sm mt-2" onclick="addIngredientRow()">+ Ajouter Ingrédient</button>
                        </div>
                        <div class="tab-pane fade" id="tab-steps">
                            <div id="etapes-container"></div>
                            <button type="button" class="btn btn-outline-primary btn-sm mt-2" onclick="addEtapeRow()">+ Ajouter Étape / Astuce</button>
                        </div>
                    </div>
                    <div class="text-end mt-4"><button type="submit" class="btn btn-success btn-lg px-5">Enregistrer</button></div>
                </form>
            </div>
        </div>`;

        $('#app-content').html(html);

        const container = document.getElementById('etapes-container');
        if (container) {
            Sortable.create(container, {
                animation: 150, handle: '.card-header', ghostClass: 'bg-light',
                onEnd: function() {
                    $('.etape-row').each(function(index) { $(this).find('.badge').text('# ' + (index + 1)); });
                }
            });
        }

        if (!isEdit) addIngredientRow();

        $('#form-recette').on('submit', function (e) {
            e.preventDefault();
            Object.keys(editors).forEach(id => {
                if(editors[id]) $(`#input-${id}`).val(editors[id].root.innerHTML);
            });
            saveRecette($(this).serialize());
        });
    }

    function addIngredientRow(callback = null) {

        const idRow = Date.now();
        const row = `<div class="row g-2 mb-2 align-items-end ingredient-row" id="row-${idRow}">
            <div class="col-md-4"><select name="ing_id[]" class="form-select select-ing" required><option>Chargement...</option></select></div>
            <div class="col-md-2"><select name="ing_unit[]" class="form-select select-unit" required><option>Chargement...</option></select></div>
            <div class="col-md-2"><input type="number" name="ing_qty[]" class="form-control" step="0.1" required value="1"></div>
            <div class="col-md-3"><input type="text" name="ing_info[]" class="form-control" placeholder="Info sup."></div>
            <div class="col-md-1"><button type="button" class="btn btn-danger w-100" onclick="$('#row-${idRow}').remove()">X</button></div>


        </div>`;

        $('#ingredients-container').append(row);
        let rowEl = $(`#row-${idRow}`);

        $.when($.get('api.php?action=get_all_ingredients'), $.get('api.php?action=get_all_unites')).done(function(resIng, resUnit) {
            let optIng = '<option value="">Choisir...</option>';
            resIng[0].forEach(i => optIng += `<option value="${i.id}">${i.name}</option>`);
            rowEl.find('.select-ing').html(optIng);

            let optUnit = '<option value="">Choisir...</option>';
            resUnit[0].forEach(u => optUnit += `<option data-step="${u.form_step}" value="${u.id}">${u.name}</option>`);
            rowEl.find('.select-unit').html(optUnit).on('change', function() {
                const step = $(this).find('option:selected').data('step');
                if (step) rowEl.find('input[name="ing_qty[]"]').attr('step', step).attr('min', step);
            });


            if (callback) { callback(rowEl); rowEl.find('.select-unit').trigger('change'); }
            else { currentRowEl = rowEl; $('#modalSearchIng').modal('show'); }
        });
    }

    function addEtapeRow(data = null) {
        const idEtape = data ? data.id : 'new-' + Date.now();
        const contenuInit = data ? data.contenu : '';
        const typeInit = data ? data.type_texte : 'étape';
        const num = $('.etape-row').length + 1;

        const html = `
        <div class="card mb-3 etape-row border-start border-4 ${typeInit === 'astuce' ? 'border-info' : 'border-primary'}" id="etape-${idEtape}">
            <div class="card-header bg-light d-flex justify-content-between align-items-center" style="cursor: move;">
                <span><i class="bi bi-grip-vertical"></i> <span class="badge bg-secondary"># ${num}</span></span>
                <div class="d-flex gap-2">
                    <select class="form-select form-select-sm w-auto" name="etape_type[]">
                        <option value="étape" ${typeInit === 'étape' ? 'selected' : ''}>Étape</option>
                        <option value="astuce" ${typeInit === 'astuce' ? 'selected' : ''}>Astuce</option>
                    </select>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeEtape('${idEtape}')"><i class="bi bi-trash"></i></button>
                </div>
            </div>
            <div class="card-body">
                <div id="editor-${idEtape}" style="min-height: 100px;" class="bg-white"></div>
                <input type="hidden" name="etape_contenu[]" id="input-${idEtape}">
            </div>
        </div>`;

        $('#etapes-container').append(html);

        setTimeout(() => {
            const editorId = `editor-${idEtape}`; // On définit editorId ici pour qu'il soit accessible
            const codeId = `code-${idEtape}`;
            const container = document.getElementById(editorId);
            if (!container) return;

            // 1. CRÉATION DU TEXTAREA POUR LE CODE (S'il n'existe pas déjà)
            if (!document.getElementById(codeId)) {
                $(`#${editorId}`).after(`<textarea id="${codeId}" class="ql-editor-code form-control" style="display:none;"></textarea>`);
            }



            // 3. INITIALISATION DE QUILL
            const quill = new Quill(`#${editorId}`, {
                theme: 'snow',
                modules: {
                    toolbar: {
                        container: [
                            [{ 'header': [1, 2, 3, false] }],
                            ['bold', 'italic', 'underline'],
                            [{ 'color': [] }, { 'background': [] }],
                            [{ 'align': [] }],
                            [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                            ['link', 'image'],
                            ['code-block'], // Notre bouton source
                            ['clean']
                        ],
                        handlers: {
                            'code-block': function() {
                                const editorContainer = document.getElementById(editorId);
                                const codeContainer = document.getElementById(codeId);
                                const qlEditor = editorContainer.querySelector('.ql-editor');

                                if (codeContainer.style.display === 'none') {
                                    // Passage en mode CODE
                                    codeContainer.value = quill.root.innerHTML;
                                    codeContainer.style.display = 'block';
                                    qlEditor.style.display = 'none';
                                    codeContainer.style.minHeight = qlEditor.clientHeight + 'px';
                                } else {
                                    // Revenir en mode VISUEL
                                    quill.root.innerHTML = codeContainer.value;
                                    codeContainer.style.display = 'none';
                                    qlEditor.style.display = 'block';
                                }
                            },
                            image: function() {
                                const input = document.createElement('input');
                                input.setAttribute('type', 'file');
                                input.setAttribute('accept', 'image/*');
                                input.click();

                                input.onchange = () => {
                                    const file = input.files[0];
                                    const formData = new FormData();
                                    formData.append('image', file);

                                    $.ajax({
                                        url: 'api.php?action=upload_image',
                                        type: 'POST',
                                        data: formData,
                                        processData: false,
                                        contentType: false,
                                        success: (res) => {
                                            if (res.status === 'success') {
                                                const range = quill.getSelection();
                                                quill.insertEmbed(range.index, 'image', res.url);
                                            } else {
                                                alert(res.message);
                                            }
                                        }
                                    });
                                };
                            }
                        }
                    }
                }
            });

            // 4. MISE EN PLACE DE L'ICÔNE ET RÉCUPÉRATION DATA
            editors[idEtape] = quill;

            if (contenuInit) {
                quill.clipboard.dangerouslyPasteHTML(contenuInit);
            }

            // On remplace visuellement l'icône du bouton code-block par une icône plus parlante
            setTimeout(() => {
                $(`#etape-${idEtape} .ql-code-block`).html('<i class="bi bi-code-slash"></i>');
            }, 50);

            // Double clic pour supprimer image
            quill.root.addEventListener('dblclick', (e) => {
                const img = e.target.closest('img');
                if (img && confirm("Supprimer cette image ?")) {
                    const blot = Quill.find(img);
                    if (blot) blot.deleteAt(0);
                }
            });
        }, 100);
    }

    function editRecette(id, targetTab = null) {
        $.get('api.php?action=get_recette_complet&id=' + id, function(data) {
            showFormRecette(true);

            $('#recette_theme').val(data.theme || 'theme-plat');

            $('#form-recette').append(`<input type="hidden" name="id_recette" value="${data.id}">`);
            $('input[name="name"]').val(data.name);
            $('input[name="nombre_personne"]').val(data.nombre_personne);
            $('input[name="temps"]').val(data.temps_realisation);
            $('input[name="difficulte"]').val(data.difficulte);
            $('textarea[name="description"]').val(data.description);



            data.ingredients.forEach(ing => {
                addIngredientRow(function(rowEl) {
                    rowEl.find('.select-ing').val(ing.id_ingredient);
                    rowEl.find('.select-unit').val(ing.id_unite);
                    rowEl.find('input[name="ing_qty[]"]').val(ing.quantite);
                    rowEl.find('input[name="ing_info[]"]').val(ing.info_facultative);
                });
            });
            // Gestion du switch d'onglet
            if (targetTab === 'ing') {
                const trigger = document.querySelector('[data-bs-target="#tab-ing"]');
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            } else if (targetTab === 'steps') {
                const trigger = document.querySelector('[data-bs-target="#tab-steps"]');
                bootstrap.Tab.getOrCreateInstance(trigger).show();
            }

            if (data.etapes) data.etapes.forEach(e => addEtapeRow(e));
        });
    }

    function deleteRecette(id) {
        if (confirm("Êtes-vous sûr de vouloir supprimer cette recette ? Cette action est irréversible.")) {
            $.ajax({
                url: 'api.php?action=delete_recette&id=' + id,
                type: 'GET',
                success: function(res) {
                    if (res.status === 'success') {
                        showNotify('Recette supprimée avec succès', 'success');
                        loadRecettes(); // On rafraîchit la liste
                    } else {
                        showNotify('Erreur : ' + res.message, 'error');
                    }
                },
                error: function() {
                    showNotify('Erreur lors de la communication avec le serveur', 'error');
                }
            });
        }
    }

    function removeEtape(id) { $(`#etape-${id}`).remove(); delete editors[id]; }

    function saveRecette(data) {
        let action = $('input[name="id_recette"]').length > 0 ? 'update_recette' : 'save_recette';
        $.post('api.php?action=' + action, data, function () { loadRecettes(); }, 'json');
    }

    function initIngredientList() { $.get('api.php?action=get_all_ingredients', d => allIngredients = d); }

    $('#modalSearchIng').on('shown.bs.modal', () => $('#inputSearchIng').val('').focus());
    $('#inputSearchIng').on('input', function() {
        let search = $(this).val().toLowerCase();
        let pattern = search.split('').join('.*');
        let filtered = allIngredients.filter(i => i.name.toLowerCase().match(new RegExp(pattern))).slice(0, 10);
        let html = filtered.map(i => `<button class="list-group-item list-group-item-action" onclick="selectThisIng(${i.id}, '${i.name.replace(/'/g, "\\'")}')">${i.name}</button>`).join('');
        $('#resultsSearchIng').html(html || 'Aucun résultat');
    });

    function selectThisIng(id, name) {
        if (!currentRowEl) return;

        // 1. Trouver le select de l'ingrédient dans la ligne actuelle
        // On utilise .select-ing (la classe que tu dois avoir sur ton <select name="ing_id[]">)
        let selectIng = currentRowEl.find('.select-ing');

        // 2. Si l'ingrédient n'est pas dans la liste pré-chargée, on l'ajoute dynamiquement
        if (selectIng.find(`option[value="${id}"]`).length === 0) {
            selectIng.append(`<option value="${id}">${name}</option>`);
        }

        // 3. On sélectionne la valeur
        selectIng.val(id);

        // 4. Fermer la modale
        $('#modalSearchIng').modal('hide');

        // 5. Focus automatique sur le champ suivant (l'unité ou la quantité)
        currentRowEl.find('.select-unit').focus();
    }

    function showNotify(message, status = 'success') {
        const toastEl = document.getElementById('liveToast');
        const toastBody = document.getElementById('toastMessage');

        // On change la couleur selon le statut
        toastEl.classList.remove('bg-success', 'bg-danger', 'bg-warning');
        if (status === 'success') toastEl.classList.add('bg-success');
        else if (status === 'error') toastEl.classList.add('bg-danger');
        else toastEl.classList.add('bg-warning');

        toastBody.textContent = message;

        const toast = new bootstrap.Toast(toastEl, { delay: 3000 }); // Disparaît après 3s
        toast.show();
    }

    let currentRecipeView = null;

    function viewRecette(id) {
        $('#app-content').html('<div class="text-center mt-5"><div class="spinner-border text-primary"></div></div>');
        $.get('api.php?action=get_recette_complet&id=' + id, function(data) {
            currentRecipeView = data;

            // Simuler le choix du thème (tu pourras ajouter ce champ en BDD plus tard)
            const themeClass = data.theme || 'theme-plat';

            let html = `
       <div class="card shadow-lg border-0 mb-5 ${themeClass}" id="recipe-viewer-container">
    <div class="recipe-header p-5 position-relative">
        <div class="position-absolute top-0 end-0 m-3 d-flex gap-2">
            <button class="btn btn-primary btn-sm" onclick="editRecette(${data.id})" title="Modifier toute la recette">
                <i class="bi bi-pencil"></i> Éditer la recette
            </button>
            <button class="btn btn-light btn-sm" onclick="loadRecettes()">
                <i class="bi bi-x-lg"></i> Fermer
            </button>
        </div>

        <h1 class="display-4 fw-bold">${data.name}</h1>
        <p class="mb-5">${data.description}</p>
                <div class="d-flex gap-3 mt-3">
                    <span class="badge recipe-badge p-2 fs-6"><i class="bi bi-clock"></i> ${formatDuree(data.temps_realisation)}</span>
                    <span class="badge recipe-badge p-2 fs-6"><i class="bi bi-bar-chart"></i> Diff: ${formatDifficulte(data.difficulte)}</span>
                </div>
            </div>

            <div class="card-body p-4 bg-white">
                <div class="row g-5">
                    <div class="col-md-4">
                        <div class="bg-light p-4 rounded shadow-sm sticky-top" style="top: 20px;">
                            <h4 class="mb-4 text-dark border-bottom pb-2">Ingrédients</h4>

                            <div class="d-flex align-items-center justify-content-between mb-4 bg-white p-2 rounded border">
                                <button class="btn btn-outline-secondary btn-sm" onclick="updateServings(-1)">-</button>
                                <span class="fw-bold fs-5" id="display-servings">${data.nombre_personne} personnes</span>
                                <button class="btn btn-outline-secondary btn-sm" onclick="updateServings(1)">+</button>
                            </div>

                            <ul class="list-unstyled" id="ingredient-list">`;

// Remplissage des ingrédients
            console.log(data)

            data.ingredients.forEach(ing => {
                console.log(data)
                let pre = ing.prefixe ? ing.prefixe + ' ' : '';
                let suf = ing.suffixe ? ' ' + ing.suffixe : '';
                let symbol = ing.unit_symbol ? ing.unit_symbol : '';
                let info = ing.info_facultative ? ` <small class="text-muted">(${ing.info_facultative})</small>` : '';

                html += `
                <li class="ingredient-item d-flex justify-content-between align-items-center py-2 border-bottom border-light">
                    <span>${ing.ingredient_name || 'Inconnu'}${info}</span>
                    <span class="fw-bold text-dark">
                        ${pre}<span class="qty-val" data-insecable="${ing.insecable}" data-base="${ing.quantite}">${ing.quantite}</span> ${symbol}${suf}
                    </span>
                </li>`;
            });

            html += `           </ul>
                            <button class="btn btn-success w-100 mt-4 py-2 fw-bold shadow-sm" onclick="addToShoppingList()">
                                <i class="bi bi-cart-plus"></i> Ajouter aux courses
                            </button>
                        </div>
                    </div>

                    <div class="col-md-8">
                        <h4 class="mb-4 text-dark border-bottom pb-2">Préparation</h4>
                        <div class="etapes-content fs-5 text-secondary" style="line-height: 1.8;">`;

            // Remplissage des étapes (Quill format)
            if(data.etapes) {
                let stepNum = 1;
                data.etapes.forEach(e => {
                    // On prépare le contenu pour qu'il respecte les styles Quill (listes, gras, etc.)
                    // La classe 'ql-editor' applique les styles, et on force le fond transparent en ligne
                    let safeContenu = `<div class="ql-editor" style="background:transparent; padding:0; height:auto; overflow:visible;">${e.contenu}</div>`;

                    if (e.type_texte === 'étape') {
                        html += `
    <div class="mb-5">
        <div class="d-flex align-items-center justify-content-between mb-3">
            <div class="d-flex align-items-center">
                <h5 class="mb-0 text-dark fw-bold">Étape <span class="step-number">${stepNum}</span></h5>
            </div>
            <button class="btn btn-sm btn-outline-primary border-0"
                    onclick="editAndGoToStep(${data.id}, '${e.id}')" title="Modifier cette étape">
                <i class="bi bi-pencil-square"></i> Modifier
            </button>
        </div>
        <div class="ql-container ql-snow view-mode">
            <div class="ql-editor" style="background:transparent; padding:0; height:auto; overflow:visible;">
                ${e.contenu}
            </div>
        </div>
    </div>`;
                        stepNum++;
                    } else {
                        html += `
            <div class="alert alert-info border-info border-start border-4 py-3">
                <i class="bi bi-lightbulb-fill text-warning"></i> <strong>Astuce du Chef :</strong><br>
                ${safeContenu}
            </div>`;
                    }
                });
            }

            html += `       </div>
                    </div>
                </div>
            </div>
        </div>`;

            $('#app-content').html(html);
        });
    }

    // Fonction pour recalculer les quantités
    function updateServings(delta) {
        if (!currentRecipeView) return;

        let currentServings = parseInt($('#display-servings').text());
        let newServings = currentServings + delta;

        if (newServings < 1) return;

        let baseServings = currentRecipeView.nombre_personne;
        $('#display-servings').text(newServings + (newServings > 1 ? ' personnes' : ' personne'));

        $('.qty-val').each(function() {
            let baseQty = parseFloat($(this).data('base'));

            // Récupère l'info d'insécabilité injectée dans le HTML
            let isInsecable = $(this).data('insecable') == 1;
            console.log(isInsecable)
            let newQty = (baseQty / baseServings) * newServings;

            if (isInsecable) {
                // Règle : Si 1.1 -> 2 (Entier supérieur)
                newQty = Math.ceil(newQty);
            } else {
                // Règle : Arrondi propre pour les liquides/poids (ex: 1.25)
                newQty = Math.round(newQty * 100) / 100;
            }

            $(this).text(newQty);
        });
    }
    // Fonction temporaire pour la liste de courses
    function addToShoppingList() {
        let currentServings = parseInt($('#display-servings').text());
        showNotify(`Les ingrédients pour ${currentServings} pers. ont été ajoutés à la liste !`, 'success');
        // Ici on connectera la vraie logique de la ToDo list plus tard
    }

    function showSearchIng(row) {
        currentRowEl = row; // On mémorise sur quelle ligne on travaille
        $('#modalSearchIng').modal('show');
        $('#inputSearchIng').val('').focus();
        $('#resultsSearchIng').html('');
    }
    function editAndGoToStep(recetteId, etapeId) {
        // 1. On ferme la modale de visualisation (si c'est une modale)
        const modalView = bootstrap.Modal.getInstance(document.getElementById('modalViewRecette'));
        if(modalView) modalView.hide();

        // 2. On lance l'édition classique (on passe 'steps' pour ouvrir le bon onglet)
        editRecette(recetteId, 'steps');

        // 3. On attend que Quill et le DOM soient chargés (300ms à 500ms car Quill est lourd)
        setTimeout(() => {
            const target = $(`#etape-${etapeId}`);
            if (target.length) {
                // Scroll fluide vers l'élément
                $('html, body').animate({
                    scrollTop: target.offset().top - 100
                }, 600);

                // Petit effet visuel (flash) pour montrer quelle étape on édite
                target.addClass('shadow-lg border-warning');
                setTimeout(() => target.removeClass('shadow-lg border-warning'), 2000);
            }
        }, 800);
    }

    // Recherche dynamique dans la modale
    $(document).on('input', '#inputSearchIng', function () {
        let q = $(this).val().toLowerCase();
        if (q.length < 2) return;

        $.get('api.php?action=get_all_ingredients', function (data) {
            let filtered = data.filter(i => i.name.toLowerCase().includes(q)).slice(0, 10);
            let html = filtered.map(i => `
            <button class="list-group-item list-group-item-action"
                    onclick="selectThisIng(${i.id}, '${i.name.replace(/'/g, "\\'")}')">
                ${i.name}
            </button>`).join('');
            $('#resultsSearchIng').html(html || 'Aucun résultat');
        });
    });
    $(document).on('change', '#recette_theme', function() {
        const selectedTheme = $(this).val();
        // Tu peux appliquer une classe temporaire sur ton formulaire
        // pour donner un avant-goût des couleurs à l'utilisateur
        console.log("Thème prévisualisé : " + selectedTheme);
    });

    $(document).ready(() => { initIngredientList(); });

</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>