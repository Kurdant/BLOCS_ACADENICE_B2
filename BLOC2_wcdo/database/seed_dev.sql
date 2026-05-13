-- =============================================================================
-- WACDO BLOC 2 — SEED DE DÉVELOPPEMENT PostgreSQL
-- Généré le : 2026-05-10
-- Encodage : UTF8
-- ATTENTION : Données de développement uniquement, ne pas charger en production.
-- Mot de passe commun à tous les comptes de dev : wacdo2026 (bcrypt cost 10)
-- Prérequis : schema.sql déjà exécuté (11 tables créées).
-- Idempotent : TRUNCATE + RESTART IDENTITY CASCADE en tête de fichier.
-- =============================================================================

SET client_encoding = 'UTF8';

-- -----------------------------------------------------------------------------
-- Réinitialisation des tables seedées (ordre inverse des FK)
-- -----------------------------------------------------------------------------
TRUNCATE options_menu, sections_menu, menus, produits, categories, utilisateurs, roles RESTART IDENTITY CASCADE;

-- -----------------------------------------------------------------------------
-- Table : roles
-- -----------------------------------------------------------------------------
INSERT INTO roles (libelle, description) VALUES
    ('Administration', 'Accès complet : utilisateurs, catalogue, commandes'),
    ('Preparation',    'Préparation des commandes (passage à preparee)'),
    ('Accueil',        'Prise de commande et remise au client (passage à livree)');

-- -----------------------------------------------------------------------------
-- Table : utilisateurs (mot de passe : wacdo2026)
-- -----------------------------------------------------------------------------
INSERT INTO utilisateurs (id_role, identifiant, mot_de_passe_hash, nom, prenom, actif) VALUES
    ((SELECT id_role FROM roles WHERE libelle = 'Administration'),
     'admin',
     '$2y$10$dL2OylBoKrMhXi6uUFG/jOBqFqFUJrNm1.sX7MbPl1eAA2JCooKj.',
     'Dupont', 'Jean', true),
    ((SELECT id_role FROM roles WHERE libelle = 'Preparation'),
     'prepa',
     '$2y$10$dL2OylBoKrMhXi6uUFG/jOBqFqFUJrNm1.sX7MbPl1eAA2JCooKj.',
     'Martin', 'Sophie', true),
    ((SELECT id_role FROM roles WHERE libelle = 'Accueil'),
     'accueil',
     '$2y$10$dL2OylBoKrMhXi6uUFG/jOBqFqFUJrNm1.sX7MbPl1eAA2JCooKj.',
     'Bernard', 'Lucas', true);

-- -----------------------------------------------------------------------------
-- Table : categories
-- -----------------------------------------------------------------------------
INSERT INTO categories (nom, description) VALUES
    ('Burgers',          'Burgers signature et classiques'),
    ('Boissons',         'Boissons fraîches et chaudes'),
    ('Accompagnements',  'Frites, salades et autres accompagnements');

-- -----------------------------------------------------------------------------
-- Table : produits
-- -----------------------------------------------------------------------------
INSERT INTO produits (id_categorie, nom, description, prix, image, disponible, actif) VALUES
    ((SELECT id_categorie FROM categories WHERE nom = 'Burgers'),
     'BigWac', 'Double steak, sauce signature, salade, oignons et cornichons', 8.90, 'bigwac.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Burgers'),
     'CheeseWac', 'Steak, double cheddar fondu, oignons et cornichons', 6.50, 'cheesewac.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Burgers'),
     'Wac Classique', 'Steak, salade, tomate et sauce burger', 5.50, 'wac_classique.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Boissons'),
     'Coca', 'Coca-Cola 33cl bien frais', 2.50, 'coca.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Boissons'),
     'Eau plate', 'Bouteille d''eau plate 50cl', 1.80, 'eau_plate.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Boissons'),
     'Café', 'Café expresso fraîchement préparé', 1.50, 'cafe.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Accompagnements'),
     'Frites moyennes', 'Frites croustillantes format moyen', 2.90, 'frites_moyennes.jpg', true, true),
    ((SELECT id_categorie FROM categories WHERE nom = 'Accompagnements'),
     'Salade verte', 'Salade verte fraîche avec vinaigrette', 3.50, 'salade_verte.jpg', true, true);

-- -----------------------------------------------------------------------------
-- Table : menus
-- -----------------------------------------------------------------------------
INSERT INTO menus (nom, description, prix, image, disponible, actif) VALUES
    ('Menu BigWac', 'BigWac + boisson + accompagnement au choix', 11.90, 'menu_bigwac.jpg', true, true);

-- -----------------------------------------------------------------------------
-- Table : sections_menu
-- -----------------------------------------------------------------------------
INSERT INTO sections_menu (id_menu, nom, obligatoire, quantite_min, quantite_max) VALUES
    ((SELECT id_menu FROM menus WHERE nom = 'Menu BigWac'), 'Burger',         true, 1, 1),
    ((SELECT id_menu FROM menus WHERE nom = 'Menu BigWac'), 'Boisson',        true, 1, 1),
    ((SELECT id_menu FROM menus WHERE nom = 'Menu BigWac'), 'Accompagnement', true, 1, 1);

-- -----------------------------------------------------------------------------
-- Table : options_menu
-- -----------------------------------------------------------------------------
INSERT INTO options_menu (id_section_menu, id_produit, supplement_prix, actif) VALUES
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Burger'),
     (SELECT id_produit FROM produits WHERE nom = 'BigWac'),
     0.00, true),
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Boisson'),
     (SELECT id_produit FROM produits WHERE nom = 'Coca'),
     0.00, true),
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Boisson'),
     (SELECT id_produit FROM produits WHERE nom = 'Eau plate'),
     0.00, true),
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Boisson'),
     (SELECT id_produit FROM produits WHERE nom = 'Café'),
     0.00, true),
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Accompagnement'),
     (SELECT id_produit FROM produits WHERE nom = 'Frites moyennes'),
     0.00, true),
    ((SELECT sm.id_section_menu FROM sections_menu sm
        JOIN menus m ON m.id_menu = sm.id_menu
        WHERE m.nom = 'Menu BigWac' AND sm.nom = 'Accompagnement'),
     (SELECT id_produit FROM produits WHERE nom = 'Salade verte'),
     0.50, true);
