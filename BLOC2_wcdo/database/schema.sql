-- =============================================================================
-- WACDO BLOC 2 — SCHÉMA BASE DE DONNÉES PostgreSQL
-- Généré le : 2026-05-10
-- Encodage : UTF8
-- Convention : ON DELETE RESTRICT sur toutes les FK, SERIAL pour toutes les PK
-- =============================================================================

SET client_encoding = 'UTF8';

-- =============================================================================
-- SECTION : ACCÈS (gestion des rôles et utilisateurs)
-- =============================================================================

CREATE TABLE IF NOT EXISTS roles (
    id_role     SERIAL          PRIMARY KEY,
    libelle     VARCHAR(50)     NOT NULL UNIQUE,
    description VARCHAR(255)    NULL,
    CONSTRAINT chk_roles_libelle CHECK (libelle IN ('Administration', 'Preparation', 'Accueil'))
);

CREATE TABLE IF NOT EXISTS utilisateurs (
    id_utilisateur      SERIAL          PRIMARY KEY,
    id_role             INT             NOT NULL,
    identifiant         VARCHAR(100)    NOT NULL UNIQUE,
    mot_de_passe_hash   VARCHAR(255)    NOT NULL,
    nom                 VARCHAR(100)    NOT NULL,
    prenom              VARCHAR(100)    NOT NULL,
    actif               BOOLEAN         NOT NULL DEFAULT true,
    date_creation       TIMESTAMP       NOT NULL DEFAULT NOW(),
    date_modification   TIMESTAMP       NULL,
    CONSTRAINT fk_utilisateurs_role FOREIGN KEY (id_role)
        REFERENCES roles (id_role) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_utilisateurs_id_role ON utilisateurs (id_role);
CREATE INDEX IF NOT EXISTS idx_utilisateurs_actif   ON utilisateurs (actif);

-- =============================================================================
-- SECTION : CATALOGUE (catégories, produits, menus, sections, options)
-- =============================================================================

CREATE TABLE IF NOT EXISTS categories (
    id_categorie    SERIAL          PRIMARY KEY,
    nom             VARCHAR(100)    NOT NULL UNIQUE,
    description     TEXT            NULL,
    actif           BOOLEAN         NOT NULL DEFAULT true
);

CREATE INDEX IF NOT EXISTS idx_categories_actif ON categories (actif);

CREATE TABLE IF NOT EXISTS produits (
    id_produit          SERIAL          PRIMARY KEY,
    id_categorie        INT             NOT NULL,
    nom                 VARCHAR(150)    NOT NULL,
    description         TEXT            NOT NULL,
    prix                NUMERIC(10,2)   NOT NULL CHECK (prix >= 0),
    image               VARCHAR(255)    NOT NULL,
    disponible          BOOLEAN         NOT NULL DEFAULT true,
    actif               BOOLEAN         NOT NULL DEFAULT true,
    date_creation       TIMESTAMP       NOT NULL DEFAULT NOW(),
    date_modification   TIMESTAMP       NULL,
    CONSTRAINT fk_produits_categorie FOREIGN KEY (id_categorie)
        REFERENCES categories (id_categorie) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_produits_id_categorie ON produits (id_categorie);
CREATE INDEX IF NOT EXISTS idx_produits_disponible   ON produits (disponible);
CREATE INDEX IF NOT EXISTS idx_produits_actif        ON produits (actif);

CREATE TABLE IF NOT EXISTS menus (
    id_menu             SERIAL          PRIMARY KEY,
    nom                 VARCHAR(150)    NOT NULL,
    description         TEXT            NOT NULL,
    prix                NUMERIC(10,2)   NOT NULL CHECK (prix >= 0),
    image               VARCHAR(255)    NOT NULL,
    disponible          BOOLEAN         NOT NULL DEFAULT true,
    actif               BOOLEAN         NOT NULL DEFAULT true,
    date_creation       TIMESTAMP       NOT NULL DEFAULT NOW(),
    date_modification   TIMESTAMP       NULL
);

CREATE INDEX IF NOT EXISTS idx_menus_disponible ON menus (disponible);
CREATE INDEX IF NOT EXISTS idx_menus_actif      ON menus (actif);

CREATE TABLE IF NOT EXISTS sections_menu (
    id_section_menu SERIAL          PRIMARY KEY,
    id_menu         INT             NOT NULL,
    nom             VARCHAR(100)    NOT NULL,
    obligatoire     BOOLEAN         NOT NULL DEFAULT true,
    quantite_min    INTEGER         NOT NULL DEFAULT 1 CHECK (quantite_min >= 0),
    quantite_max    INTEGER         NOT NULL DEFAULT 1 CHECK (quantite_max >= 1),
    CONSTRAINT fk_sections_menu_menu FOREIGN KEY (id_menu)
        REFERENCES menus (id_menu) ON DELETE RESTRICT,
    CONSTRAINT chk_sections_menu_quantites CHECK (quantite_max >= quantite_min)
);

CREATE INDEX IF NOT EXISTS idx_sections_menu_id_menu ON sections_menu (id_menu);

CREATE TABLE IF NOT EXISTS options_menu (
    id_option_menu      SERIAL          PRIMARY KEY,
    id_section_menu     INT             NOT NULL,
    id_produit          INT             NOT NULL,
    supplement_prix     NUMERIC(10,2)   NOT NULL DEFAULT 0 CHECK (supplement_prix >= 0),
    actif               BOOLEAN         NOT NULL DEFAULT true,
    CONSTRAINT fk_options_menu_section FOREIGN KEY (id_section_menu)
        REFERENCES sections_menu (id_section_menu) ON DELETE RESTRICT,
    CONSTRAINT fk_options_menu_produit FOREIGN KEY (id_produit)
        REFERENCES produits (id_produit) ON DELETE RESTRICT,
    CONSTRAINT uq_options_menu_section_produit UNIQUE (id_section_menu, id_produit)
);

CREATE INDEX IF NOT EXISTS idx_options_menu_id_section_menu ON options_menu (id_section_menu);
CREATE INDEX IF NOT EXISTS idx_options_menu_id_produit      ON options_menu (id_produit);
CREATE INDEX IF NOT EXISTS idx_options_menu_actif           ON options_menu (actif);

-- =============================================================================
-- SECTION : COMMANDES (commandes, lignes, choix de personnalisation)
-- =============================================================================

CREATE TABLE IF NOT EXISTS commandes (
    id_commande             SERIAL          PRIMARY KEY,
    numero_retrait          VARCHAR(30)     NOT NULL,
    source                  VARCHAR(20)     NOT NULL,
    type_service            VARCHAR(20)     NOT NULL,
    statut                  VARCHAR(20)     NOT NULL DEFAULT 'a_preparer',
    total                   NUMERIC(10,2)   NOT NULL CHECK (total >= 0),
    date_commande           TIMESTAMP       NOT NULL DEFAULT NOW(),
    date_heure_retrait_prevue TIMESTAMP     NULL,
    id_utilisateur_auteur   INT             NULL,
    date_preparation        TIMESTAMP       NULL,
    date_livraison          TIMESTAMP       NULL,
    CONSTRAINT fk_commandes_utilisateur FOREIGN KEY (id_utilisateur_auteur)
        REFERENCES utilisateurs (id_utilisateur) ON DELETE RESTRICT,
    CONSTRAINT chk_commandes_source       CHECK (source IN ('api', 'back_office')),
    CONSTRAINT chk_commandes_type_service CHECK (type_service IN ('sur_place', 'a_emporter')),
    CONSTRAINT chk_commandes_statut       CHECK (statut IN ('a_preparer', 'preparee', 'livree'))
);

CREATE INDEX IF NOT EXISTS idx_commandes_statut               ON commandes (statut);
CREATE INDEX IF NOT EXISTS idx_commandes_id_utilisateur_auteur ON commandes (id_utilisateur_auteur);
CREATE INDEX IF NOT EXISTS idx_commandes_date_commande        ON commandes (date_commande);

CREATE TABLE IF NOT EXISTS lignes_commande (
    id_ligne_commande       SERIAL          PRIMARY KEY,
    id_commande             INT             NOT NULL,
    type_ligne              VARCHAR(20)     NOT NULL,
    id_produit              INT             NULL,
    id_menu                 INT             NULL,
    libelle_article         VARCHAR(150)    NOT NULL,
    quantite                INTEGER         NOT NULL CHECK (quantite > 0),
    prix_unitaire_applique  NUMERIC(10,2)   NOT NULL CHECK (prix_unitaire_applique >= 0),
    sous_total              NUMERIC(10,2)   NOT NULL CHECK (sous_total >= 0),
    CONSTRAINT fk_lignes_commande_commande FOREIGN KEY (id_commande)
        REFERENCES commandes (id_commande) ON DELETE RESTRICT,
    CONSTRAINT fk_lignes_commande_produit FOREIGN KEY (id_produit)
        REFERENCES produits (id_produit) ON DELETE RESTRICT,
    CONSTRAINT fk_lignes_commande_menu FOREIGN KEY (id_menu)
        REFERENCES menus (id_menu) ON DELETE RESTRICT,
    CONSTRAINT chk_lignes_commande_type_ligne CHECK (type_ligne IN ('produit', 'menu')),
    CONSTRAINT chk_lignes_commande_xor_produit_menu CHECK (
        (id_produit IS NOT NULL AND id_menu IS NULL)
        OR
        (id_produit IS NULL AND id_menu IS NOT NULL)
    )
);

CREATE INDEX IF NOT EXISTS idx_lignes_commande_id_commande ON lignes_commande (id_commande);
CREATE INDEX IF NOT EXISTS idx_lignes_commande_id_produit  ON lignes_commande (id_produit);
CREATE INDEX IF NOT EXISTS idx_lignes_commande_id_menu     ON lignes_commande (id_menu);

CREATE TABLE IF NOT EXISTS choix_ligne_commande (
    id_choix_ligne_commande     SERIAL          PRIMARY KEY,
    id_ligne_commande           INT             NOT NULL,
    id_produit                  INT             NOT NULL,
    nom_section                 VARCHAR(100)    NOT NULL,
    libelle_produit             VARCHAR(150)    NOT NULL,
    prix_supplement_applique    NUMERIC(10,2)   NOT NULL DEFAULT 0 CHECK (prix_supplement_applique >= 0),
    CONSTRAINT fk_choix_ligne_commande_ligne FOREIGN KEY (id_ligne_commande)
        REFERENCES lignes_commande (id_ligne_commande) ON DELETE RESTRICT,
    CONSTRAINT fk_choix_ligne_commande_produit FOREIGN KEY (id_produit)
        REFERENCES produits (id_produit) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_choix_ligne_commande_id_ligne_commande ON choix_ligne_commande (id_ligne_commande);
CREATE INDEX IF NOT EXISTS idx_choix_ligne_commande_id_produit        ON choix_ligne_commande (id_produit);

-- =============================================================================
-- SECTION : AUDIT (traçabilité des actions back-office)
-- =============================================================================

CREATE TABLE IF NOT EXISTS traces_actions (
    id_trace        SERIAL          PRIMARY KEY,
    id_utilisateur  INT             NULL,
    action          VARCHAR(100)    NOT NULL,
    table_cible     VARCHAR(100)    NOT NULL,
    id_cible        INT             NULL,
    date_action     TIMESTAMP       NOT NULL DEFAULT NOW(),
    details         TEXT            NULL,
    CONSTRAINT fk_traces_actions_utilisateur FOREIGN KEY (id_utilisateur)
        REFERENCES utilisateurs (id_utilisateur) ON DELETE RESTRICT
);

CREATE INDEX IF NOT EXISTS idx_traces_actions_id_utilisateur ON traces_actions (id_utilisateur);
CREATE INDEX IF NOT EXISTS idx_traces_actions_table_cible    ON traces_actions (table_cible);
CREATE INDEX IF NOT EXISTS idx_traces_actions_date_action    ON traces_actions (date_action);

-- =============================================================================
-- SECTION : TRIGGERS (mise à jour automatique de date_modification)
-- =============================================================================

-- Fonction générique appelée par tous les triggers date_modification
CREATE OR REPLACE FUNCTION fn_set_date_modification()
RETURNS TRIGGER AS $$
BEGIN
    NEW.date_modification = NOW();
    RETURN NEW;
END;
$$ LANGUAGE plpgsql;

-- Trigger sur utilisateurs
CREATE OR REPLACE TRIGGER trg_utilisateurs_date_modification
    BEFORE UPDATE ON utilisateurs
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_date_modification();

-- Trigger sur produits
CREATE OR REPLACE TRIGGER trg_produits_date_modification
    BEFORE UPDATE ON produits
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_date_modification();

-- Trigger sur menus
CREATE OR REPLACE TRIGGER trg_menus_date_modification
    BEFORE UPDATE ON menus
    FOR EACH ROW
    EXECUTE FUNCTION fn_set_date_modification();

