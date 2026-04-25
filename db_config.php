<?php
$db = new PDO('sqlite:cuisine.db');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialisation des tables
$db->exec("CREATE TABLE IF NOT EXISTS categorie (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    parent_id INTEGER NULL,
    name TEXT NOT NULL,
    FOREIGN KEY (parent_id) REFERENCES categorie(id)
);

CREATE TABLE IF NOT EXISTS unite (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    negligeable INTEGER DEFAULT 0,
    prefixe TEXT,
    suffixe TEXT
);

CREATE TABLE IF NOT EXISTS ingredient (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    category_id INTEGER,
    insecable INTEGER DEFAULT 0,
    img_src TEXT NULL,
    FOREIGN KEY (category_id) REFERENCES categorie(id)
);

CREATE TABLE IF NOT EXISTS recette (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    description TEXT,
    nombre_personne INTEGER NOT NULL CHECK(nombre_personne >= 1),
    temps_realisation INTEGER,
    difficulte REAL CHECK(difficulte >= 0.0 AND difficulte <= 1.0)
);

CREATE TABLE IF NOT EXISTS recette_ingredients (
    id_recette INTEGER,
    id_ingredient INTEGER,
    id_unite INTEGER,
    quantite REAL,
    info_facultative TEXT,
    PRIMARY KEY (id_recette, id_ingredient),
    FOREIGN KEY (id_recette) REFERENCES recette(id),
    FOREIGN KEY (id_ingredient) REFERENCES ingredient(id),
    FOREIGN KEY (id_unite) REFERENCES unite(id)
);

CREATE TABLE IF NOT EXISTS historique_achats (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    date_achat DATETIME,
    details_json TEXT
);");
?>