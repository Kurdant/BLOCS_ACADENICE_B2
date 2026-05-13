# CDC technique Bloc 2

## Sommaire

### MCT
### Dictionnaire des données
### Contexte technique
### Choix techniques retenus
### Architecture applicative
### Persistance et base de données
### Sécurité applicative
   - Authentification et sessions back-office
   - Hachage des mots de passe
   - Contrôle des autorisations
   - Validation et assainissement des données
   - Protection contre la force brute
   - Contrôle des fichiers envoyés
### API externe
   - Principes généraux
   - Séparation entre back-office et endpoints externes
   - Endpoint de consultation du catalogue
   - Endpoint de réception des commandes
   - Validation métier et gestion des erreurs
### Environnement de développement
### Déploiement
### Conventions de développement

---

## 0 MCT

/home/kurdant/Bureau/AcadéNice/Cours/ouii/BLOC2_wcdo/MCT_bloc2_wacdo.drawio


## 1 Dictionnaire des données

Le dictionnaire des données décrit les principales tables nécessaires à la base de données du back-office Wacdo. 

### 1.1 Choix de modélisation retenus

- Le rôle est stocké dans une table `roles` et référencé par `id_role` dans `utilisateurs`. Ce choix est plus propre qu'un simple texte dans la table utilisateur, car il évite les fautes de saisie et facilite le contrôle des droits.
- Le mot de passe n'est jamais stocké en clair. La base conserve uniquement un `mot_de_passe_hash`.
- Une commande ne suffit pas seule : elle doit posséder des lignes de commande pour conserver les produits, menus, quantités et prix réellement appliqués.
- Les prix et libellés commandés sont recopiés dans les lignes de commande afin de conserver l'historique, même si le catalogue change ensuite.
- `actif` sert à désactiver ou archiver une donnée sans la supprimer physiquement. `disponible` sert à indiquer si un produit ou un menu peut être commandé à un instant donné.
- `source` et `statut` peuvent être gérés par des valeurs contrôlées en base de données plutôt que par des tables séparées, afin de garder un modèle simple.

### 1.2 Tables retenues

| Table | Rôle dans l'application | Nécessité |
|---|---|---|
| `roles` | Liste des rôles internes autorisés | Obligatoire |
| `utilisateurs` | Comptes du personnel interne | Obligatoire |
| `categories` | Organisation des produits du catalogue | Obligatoire |
| `produits` | Produits simples commandables | Obligatoire |
| `menus` | Offres composées de plusieurs choix | Obligatoire |
| `sections_menu` | Parties d'un menu, par exemple plat, boisson ou accompagnement | Obligatoire |
| `options_menu` | Produits autorisés comme choix dans une section de menu | Obligatoire |
| `commandes` | Informations générales d'une commande | Obligatoire |
| `lignes_commande` | Détail des produits ou menus commandés | Obligatoire |
| `choix_ligne_commande` | Choix effectués dans un menu commandé | Obligatoire |
| `traces_actions` | Historique des actions sensibles | Obligatoire |

### 1.3 Détail des tables

#### `roles`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_role` | INT | Clé primaire | Identifiant technique du rôle |
| `libelle` | VARCHAR(50) | Obligatoire, unique | Nom du rôle : `Administration`, `Preparation` ou `Accueil` |
| `description` | VARCHAR(255) | Optionnel | Description courte des droits associés au rôle |

#### `utilisateurs`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_utilisateur` | INT | Clé primaire | Identifiant technique du compte interne |
| `id_role` | INT | Clé étrangère vers `roles.id_role`, obligatoire | Rôle attribué à l'utilisateur |
| `identifiant` | VARCHAR(100) | Obligatoire, unique | Identifiant utilisé pour la connexion |
| `mot_de_passe_hash` | VARCHAR(255) | Obligatoire | Mot de passe protégé par hachage |
| `nom` | VARCHAR(100) | Obligatoire | Nom de l'utilisateur interne |
| `prenom` | VARCHAR(100) | Obligatoire | Prénom de l'utilisateur interne |
| `actif` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si le compte peut se connecter |
| `date_creation` | TIMESTAMP | Obligatoire | Date de création du compte |
| `date_modification` | TIMESTAMP | Optionnel | Date de dernière modification du compte |

#### `categories`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_categorie` | INT | Clé primaire | Identifiant technique de la catégorie |
| `nom` | VARCHAR(100) | Obligatoire, unique | Nom affiché de la catégorie |
| `description` | TEXT | Optionnel | Description de la catégorie |
| `actif` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si la catégorie reste utilisée dans le catalogue |

#### `produits`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_produit` | INT | Clé primaire | Identifiant technique du produit |
| `id_categorie` | INT | Clé étrangère vers `categories.id_categorie`, obligatoire | Catégorie à laquelle le produit appartient |
| `nom` | VARCHAR(150) | Obligatoire | Nom du produit |
| `description` | TEXT | Obligatoire | Description du produit |
| `prix` | DECIMAL(10,2) | Obligatoire, supérieur ou égal à 0 | Prix courant du produit |
| `image` | VARCHAR(255) | Obligatoire | Chemin ou nom du fichier image associé |
| `disponible` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si le produit est commandable |
| `actif` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si le produit reste présent dans le catalogue |
| `date_creation` | TIMESTAMP | Obligatoire | Date de création du produit |
| `date_modification` | TIMESTAMP | Optionnel | Date de dernière modification du produit |

#### `menus`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_menu` | INT | Clé primaire | Identifiant technique du menu |
| `nom` | VARCHAR(150) | Obligatoire | Nom du menu |
| `description` | TEXT | Obligatoire | Description du menu |
| `prix` | DECIMAL(10,2) | Obligatoire, supérieur ou égal à 0 | Prix courant du menu |
| `image` | VARCHAR(255) | Obligatoire | Chemin ou nom du fichier image associé |
| `disponible` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si le menu est commandable |
| `actif` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si le menu reste présent dans le catalogue |
| `date_creation` | TIMESTAMP | Obligatoire | Date de création du menu |
| `date_modification` | TIMESTAMP | Optionnel | Date de dernière modification du menu |

#### `sections_menu`

Cette table decoupe un menu en parties logiques de choix. Exemple : un menu peut imposer 1 boisson et 1 accompagnement. Sans cette table, un menu reste une boite noire et on ne peut pas exprimer proprement sa structure.

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_section_menu` | INT | Clé primaire | Identifiant technique de la section de menu |
| `id_menu` | INT | Clé étrangère vers `menus.id_menu`, obligatoire | Menu concerné par la section |
| `nom` | VARCHAR(100) | Obligatoire | Nom de la section, par exemple `Plat`, `Boisson`, `Accompagnement` |
| `obligatoire` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si un choix est obligatoire dans cette section |
| `quantite_min` | INT | Obligatoire, valeur par défaut `1` | Nombre minimal de choix attendus |
| `quantite_max` | INT | Obligatoire, valeur par défaut `1` | Nombre maximal de choix autorisés |

#### `options_menu`

Cette table relie une section de menu aux produits autorises dans cette section. Exemple : dans la section `Boisson` d'un menu, on peut autoriser `Coca`, `Fanta` et `Eau`.

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_option_menu` | INT | Clé primaire | Identifiant technique de l'option de menu |
| `id_section_menu` | INT | Clé étrangère vers `sections_menu.id_section_menu`, obligatoire | Section dans laquelle le produit peut être choisi |
| `id_produit` | INT | Clé étrangère vers `produits.id_produit`, obligatoire | Produit proposé comme option dans la section |
| `supplement_prix` | DECIMAL(10,2) | Obligatoire, valeur par défaut `0` | Supplément éventuel appliqué au choix |
| `actif` | BOOLEAN | Obligatoire, valeur par défaut `true` | Indique si cette option reste proposée dans le menu |

#### `commandes`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_commande` | INT | Clé primaire | Identifiant technique de la commande |
| `numero_retrait` | VARCHAR(30) | Obligatoire | Numéro du chevalet remis au client et renseigné lors de la prise de commande |
| `source` | VARCHAR(20) | Obligatoire, valeur contrôlée | Origine de la commande : `api` ou `back_office` |
| `type_service` | VARCHAR(20) | Obligatoire, valeur contrôlée | Mode de service de la commande : `sur_place` ou `a_emporter` |
| `statut` | VARCHAR(20) | Obligatoire, valeur contrôlée | État courant : `a_preparer`, `preparee` ou `livree` |
| `total` | DECIMAL(10,2) | Obligatoire, supérieur ou égal à 0 | Total recalculé côté serveur |
| `date_commande` | TIMESTAMP | Obligatoire | Date et heure de création de la commande |
| `date_heure_retrait_prevue` | TIMESTAMP | Optionnel | Date et heure prévues de retrait si elles sont connues |
| `id_utilisateur_auteur` | INT | Clé étrangère vers `utilisateurs.id_utilisateur`, optionnel | Utilisateur ayant saisi la commande depuis le back-office ; vide pour une commande API |
| `date_preparation` | TIMESTAMP | Optionnel | Date de passage au statut `preparee` |
| `date_livraison` | TIMESTAMP | Optionnel | Date de passage au statut `livree` |

#### `lignes_commande`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_ligne_commande` | INT | Clé primaire | Identifiant technique de la ligne de commande |
| `id_commande` | INT | Clé étrangère vers `commandes.id_commande`, obligatoire | Commande à laquelle appartient la ligne |
| `type_ligne` | VARCHAR(20) | Obligatoire, valeur contrôlée | Type de ligne : `produit` ou `menu` |
| `id_produit` | INT | Clé étrangère vers `produits.id_produit`, optionnel | Produit commandé si la ligne concerne un produit simple |
| `id_menu` | INT | Clé étrangère vers `menus.id_menu`, optionnel | Menu commandé si la ligne concerne un menu |
| `libelle_article` | VARCHAR(150) | Obligatoire | Libellé figé du produit ou du menu au moment de la commande |
| `quantite` | INT | Obligatoire, supérieur à 0 | Quantité commandée |
| `prix_unitaire_applique` | DECIMAL(10,2) | Obligatoire, supérieur ou égal à 0 | Prix unitaire figé au moment de la commande |
| `sous_total` | DECIMAL(10,2) | Obligatoire, supérieur ou égal à 0 | Montant calculé pour la ligne |

#### `choix_ligne_commande`

Cette table conserve les choix reels faits dans un menu commande. Exemple : si un client prend un menu et choisit `Coca` en boisson et `Frites` en accompagnement, ces choix ne doivent pas etre perdus ; ils sont stockes ici.

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_choix_ligne_commande` | INT | Clé primaire | Identifiant technique du choix effectué dans un menu commandé |
| `id_ligne_commande` | INT | Clé étrangère vers `lignes_commande.id_ligne_commande`, obligatoire | Ligne de commande de type `menu` concernée |
| `id_produit` | INT | Clé étrangère vers `produits.id_produit`, obligatoire | Produit choisi dans le menu |
| `nom_section` | VARCHAR(100) | Obligatoire | Section du menu au moment de la commande, par exemple `Boisson` |
| `libelle_produit` | VARCHAR(150) | Obligatoire | Libellé figé du produit choisi |
| `prix_supplement_applique` | DECIMAL(10,2) | Obligatoire, valeur par défaut `0` | Supplément figé au moment de la commande |

#### `traces_actions`

| Champ | Type indicatif | Contraintes | Définition |
|---|---|---|---|
| `id_trace` | INT | Clé primaire | Identifiant technique de la trace |
| `id_utilisateur` | INT | Clé étrangère vers `utilisateurs.id_utilisateur`, optionnel | Utilisateur ayant réalisé l'action |
| `action` | VARCHAR(100) | Obligatoire | Nature de l'action tracée, par exemple `creation_produit` ou `changement_statut_commande` |
| `table_cible` | VARCHAR(100) | Obligatoire | Table ou domaine concerné par l'action |
| `id_cible` | INT | Optionnel | Identifiant de l'élément concerné par l'action |
| `date_action` | TIMESTAMP | Obligatoire | Date et heure de l'action |
| `details` | TEXT | Optionnel | Informations complémentaires utiles à l'audit |

### 1.4 Contraintes métier principales

- Un utilisateur possède un et un seul rôle.
- Un identifiant utilisateur est unique.
- Un compte inactif ne peut pas ouvrir de session.
- Un produit appartient à une et une seule catégorie.
- Un menu possède au moins une section.
- Une section de menu propose au moins une option active pour être commandable.
- Une commande possède au moins une ligne de commande.
- Une ligne de commande référence soit un produit, soit un menu, mais pas les deux en même temps.
- Une commande possède un type de service : `sur_place` ou `a_emporter`.
- Le total d'une commande correspond à la somme de ses lignes.
- Le prix utilisé dans une commande est le prix appliqué au moment de la commande, pas forcément le prix courant du catalogue.
- Une commande suit le cycle de statut : `a_preparer` -> `preparee` -> `livree`.
- Le numéro de retrait correspond au numéro du chevalet remis au client. Il n'est pas généré par le back-office ; il est reçu ou saisi lors de la prise de commande.
- Une suppression physique est interdite si la donnée est déjà liée à une commande ; la désactivation est privilégiée.

## 2 Contexte technique

Le back-office Wacdo est une application web interne developpee from scratch pour repondre aux attendus du Bloc 2. Il doit centraliser la gestion du catalogue, des comptes utilisateurs internes et du cycle de vie des commandes, tout en exposant une API REST au systeme de prise de commande externe.

Le contexte technique impose plusieurs contraintes structurantes. Le back-end doit etre developpe en programmation objet avec une architecture de type MVC. La persistance des donnees repose sur une base relationnelle. L'application doit rester simple a presenter, maintenable, securisee et suffisamment claire pour etre comprise et defendue devant un jury.

Les principaux enjeux techniques du projet sont les suivants :

- separer clairement les fonctionnalites du back-office interne et les endpoints exposes au systeme externe ;
- garantir l'integrite des donnees du catalogue, des commandes et des comptes utilisateurs ;
- conserver un historique fiable des commandes et des actions sensibles ;
- mettre en place une authentification par session et un controle des autorisations selon le role ;
- proposer une architecture lisible, modulaire et compatible avec une realisation from scratch.

## 3 Choix techniques retenus

Les choix techniques retenus pour cette realisation sont volontairement simples, robustes et coherents avec le referentiel du Bloc 2.

| Domaine | Choix retenu | Justification |
|---|---|---|
| Back-end | PHP en programmation objet | Compatible avec le referentiel, simple a executer et adapte a une architecture MVC from scratch |
| Architecture applicative | MVC | Permet de separer clairement la logique metier, l'acces aux donnees et l'affichage |
| Base de donnees | PostgreSQL | SGBD relationnel robuste, adapte aux contraintes d'integrite, aux cles et aux relations du projet |
| Acces a la base | PDO | Solution native PHP, simple, securisable avec requetes preparees et adaptee a PostgreSQL |
| Front-end back-office | HTML, CSS, JavaScript | Stack legere et suffisante pour un back-office interne sans dependance inutile |
| Format d'echange API | JSON sur HTTP | Format standard, lisible et adapte aux echanges entre le systeme externe et le back-office |
| Authentification interne | Sessions serveur PHP | Choix coherent pour un back-office interne avec utilisateurs authentifies |

Le projet ne retient pas de framework lourd cote front ou cote back afin de rester aligne avec une realisation from scratch, plus lisible a presenter et plus simple a justifier dans le cadre de la soutenance.

## 4 Architecture applicative

L'application est organisée selon une architecture MVC simple. L'objectif est de séparer les responsabilités afin de rendre le code lisible, maintenable et facile à faire évoluer pendant le projet ou lors d'une demande du jury.

### 4.1 Principe général

- Le fichier `public/index.php` sert de point d'entrée unique pour les requêtes HTTP.
- Le routeur associe une URL à un contrôleur et à une méthode précise.
- Les contrôleurs gèrent la requête, vérifient la session et les droits selon le rôle de l'utilisateur, puis appellent les services métier.
- Les services métier appliquent les règles principales de l'application : gestion du catalogue, cycle de vie des commandes, calcul des totaux et traces d'actions sensibles.
- Les repositories centralisent les requêtes SQL exécutées avec PDO et PostgreSQL.
- Les vues affichent les pages HTML du back-office et n'exécutent aucune requête SQL.
- Les endpoints API renvoient des réponses JSON au système externe sans utiliser les vues HTML du back-office.

### 4.2 Séparation back-office et API

Le back-office est réservé aux utilisateurs internes authentifiés. Il permet de gérer les utilisateurs, les rôles, le catalogue et les commandes selon les droits attribués aux profils `Administration`, `Preparation` et `Accueil`.

L'API externe est séparée de l'interface d'administration. Elle sert uniquement à fournir le catalogue au système de commande et à recevoir les commandes créées depuis ce système. Elle n'utilise pas les sessions du back-office et utilise une clé API transmise dans l'en-tête HTTP `X-API-Key`.

Les données reçues par l'API sont validées côté serveur avant toute insertion en base. Les endpoints API retournent des réponses JSON avec des codes HTTP adaptés en cas de succès, d'erreur de validation ou d'accès refusé.

### 4.3 Flux type d'une requête

1. Une requête arrive sur une route de l'application.
2. Le routeur appelle le contrôleur correspondant.
3. Le contrôleur vérifie la session, les autorisations et les données reçues.
4. Le contrôleur appelle le service métier adapté.
5. Le service utilise un repository pour lire ou modifier PostgreSQL via PDO.
6. Le contrôleur renvoie soit une page HTML pour le back-office, soit une réponse JSON pour l'API.
7. Si l'action est sensible, une trace est enregistrée dans la table `traces_actions`.

Pour la création d'une commande, l'enregistrement de la commande, des lignes, des choix de menu et du total doit être réalisé dans une transaction afin d'éviter une commande partiellement enregistrée.

### 4.4 Organisation des dossiers

L'organisation des dossiers du projet est figée comme suit. Toute l'arborescence applicative est placée à la racine du dépôt.

```text
/public            — racine web Apache (DocumentRoot)
   index.php       — point d'entrée unique
   .htaccess       — réécriture vers index.php
/app
   /Controllers    — contrôleurs back-office et API
   /Services       — logique métier (catalogue, commandes, traces)
   /Repositories   — accès PDO PostgreSQL
   /Views          — templates HTML du back-office
   /Core           — routeur, base controller, base repository, helpers
/config            — configuration (PDO, sessions, constantes)
/routes
   web.php         — routes back-office
   api.php         — routes /api/*
/storage
   /uploads        — fichiers téléversés du catalogue
/database
   schema.sql
   seed_dev.sql
.env.example
docker-compose.yml
Dockerfile
```

Cette organisation est la référence du projet. Les contrôleurs ne contiennent pas de SQL, les vues ne contiennent pas de logique métier, les repositories ne contiennent pas de logique de présentation. Les fichiers téléversés pour le catalogue sont stockés dans `/storage/uploads` et contrôlés côté serveur avant enregistrement.

La gestion des fichiers image est centralisée dans `app/Services/UploadService.php`. Ce service est la seule classe autorisée à manipuler les fichiers uploadés. Il est utilisé par `ProduitService` et `MenuService`, jamais directement par les controllers. Les règles détaillées d'upload sont définies en section 6.5.

### 4.4.1 Détail des vues et assets front

```text
/app/Views
   layout.php                    — layout HTML commun à toutes les pages authentifiées
   /auth
      login.php                  — page de connexion (sans layout, pleine page)
   /dashboard
      index.php                  — tableau de bord (tous rôles)
   /utilisateurs
      index.php                  — liste des utilisateurs
      create.php                 — formulaire de création
      edit.php                   — formulaire d'édition
   /categories
      index.php                  — liste des catégories
      create.php                 — formulaire de création
      edit.php                   — formulaire d'édition
   /produits
      index.php                  — liste des produits
      create.php                 — formulaire de création
      edit.php                   — formulaire d'édition
   /menus
      index.php                  — liste des menus
      create.php                 — formulaire de création
      edit.php                   — formulaire d'édition
      sections.php               — gestion des sections et options d'un menu
   /commandes
      index.php                  — liste des commandes (filtrée selon le rôle)
      create.php                 — saisie manuelle d'une commande
      show.php                   — détail d'une commande
   /partials
      header.php                 — barre supérieure (logo, utilisateur, déconnexion)
      sidebar.php                — navigation latérale (liens filtrés selon le rôle)
      flash.php                  — messages de retour succès/erreur
      breadcrumb.php             — fil d'ariane contextuel
      pagination.php             — composant pagination pour les listes
```

```text
/public
   /css
      style.css                  — reset, variables CSS, typographie, couleurs
      layout.css                 — grille : header + sidebar + zone principale
      components.css             — boutons, formulaires, tableaux, badges de statut, alertes
      login.css                  — styles spécifiques à la page de connexion
   /js
      app.js                     — confirmations avant action destructive, fermeture alertes
      commandes.js               — rafraîchissement automatique de la liste commandes (30s)
   /img                          — images statiques (logo, icônes)
```

Les vues sont des fichiers `.php` dont le rôle se limite à l'affichage : elles reçoivent des variables du contrôleur et produisent du HTML. Elles ne contiennent aucune requête SQL, aucune logique métier et aucun appel de service. Toutes les données affichées sont échappées avec `htmlspecialchars`. Les fichiers CSS et JS sont des fichiers statiques purs, sans PHP. Ils sont liés dans `layout.php` via les balises `<link>` et `<script>`.

### 4.5 Routes back-office figées

Les routes internes du back-office sont figées ci-dessous. Toutes les routes back-office sont protégées par session sauf `GET /login` et `POST /login`. Les contrôles d'autorisation par rôle sont rappelés dans la colonne **Rôles autorisés**.

**Authentification :**

| Méthode | Route | Contrôleur::action | Rôles autorisés |
|---|---|---|---|
| GET | `/login` | `AuthController::showLogin` | Public |
| POST | `/login` | `AuthController::login` | Public |
| POST | `/logout` | `AuthController::logout` | Tous authentifiés |

**Tableau de bord :**

| Méthode | Route | Contrôleur::action | Rôles autorisés |
|---|---|---|---|
| GET | `/` | `DashboardController::index` | Tous authentifiés |

**Gestion des utilisateurs (Administration uniquement) :**

| Méthode | Route | Contrôleur::action | Rôles autorisés |
|---|---|---|---|
| GET | `/utilisateurs` | `UtilisateurController::index` | Administration |
| GET | `/utilisateurs/creer` | `UtilisateurController::create` | Administration |
| POST | `/utilisateurs` | `UtilisateurController::store` | Administration |
| GET | `/utilisateurs/{id}/editer` | `UtilisateurController::edit` | Administration |
| POST | `/utilisateurs/{id}` | `UtilisateurController::update` | Administration |
| POST | `/utilisateurs/{id}/desactiver` | `UtilisateurController::desactiver` | Administration |

**Gestion du catalogue — catégories, produits, menus (Administration uniquement) :**

| Méthode | Route | Contrôleur::action | Rôles autorisés |
|---|---|---|---|
| GET | `/categories` | `CategorieController::index` | Administration |
| GET | `/categories/creer` | `CategorieController::create` | Administration |
| POST | `/categories` | `CategorieController::store` | Administration |
| GET | `/categories/{id}/editer` | `CategorieController::edit` | Administration |
| POST | `/categories/{id}` | `CategorieController::update` | Administration |
| POST | `/categories/{id}/desactiver` | `CategorieController::desactiver` | Administration |
| GET | `/produits` | `ProduitController::index` | Administration |
| GET | `/produits/creer` | `ProduitController::create` | Administration |
| POST | `/produits` | `ProduitController::store` | Administration |
| GET | `/produits/{id}/editer` | `ProduitController::edit` | Administration |
| POST | `/produits/{id}` | `ProduitController::update` | Administration |
| POST | `/produits/{id}/desactiver` | `ProduitController::desactiver` | Administration |
| GET | `/menus` | `MenuController::index` | Administration |
| GET | `/menus/creer` | `MenuController::create` | Administration |
| POST | `/menus` | `MenuController::store` | Administration |
| GET | `/menus/{id}/editer` | `MenuController::edit` | Administration |
| POST | `/menus/{id}` | `MenuController::update` | Administration |
| POST | `/menus/{id}/desactiver` | `MenuController::desactiver` | Administration |
| GET | `/menus/{id}/sections` | `SectionMenuController::index` | Administration |
| POST | `/menus/{id}/sections` | `SectionMenuController::store` | Administration |
| POST | `/sections/{id}/options` | `OptionMenuController::store` | Administration |
| POST | `/options/{id}/desactiver` | `OptionMenuController::desactiver` | Administration |

**Gestion des commandes :**

| Méthode | Route | Contrôleur::action | Rôles autorisés |
|---|---|---|---|
| GET | `/commandes` | `CommandeController::index` | Administration, Preparation, Accueil |
| GET | `/commandes/creer` | `CommandeController::create` | Administration, Accueil |
| POST | `/commandes` | `CommandeController::store` | Administration, Accueil |
| GET | `/commandes/{id}` | `CommandeController::show` | Administration, Preparation, Accueil |
| POST | `/commandes/{id}/preparee` | `CommandeController::marquerPreparee` | Administration, Preparation |
| POST | `/commandes/{id}/livree` | `CommandeController::marquerLivree` | Administration, Accueil |

**Endpoints API externes (séparés du back-office, authentification par `X-API-Key`) :**

| Méthode | Route | Contrôleur::action | Authentification |
|---|---|---|---|
| GET | `/api/catalogue` | `Api\\CatalogueController::index` | Clé API |
| POST | `/api/commandes` | `Api\\CommandeController::store` | Clé API |

Toute route absente de ce tableau renvoie une réponse HTTP 404. Toute action soumise par un rôle non autorisé renvoie une réponse HTTP 403.

## 5 Base de données

/home/kurdant/Bureau/AcadéNice/Cours/ouii/BLOC2_wcdo/SCHEMA_BDD_bloc2_wacdo.drawio

### 5.1 Mise à jour automatique de `date_modification`

Les tables `utilisateurs`, `produits` et `menus` possèdent un champ `date_modification` destiné à conserver la date du dernier changement d'un enregistrement. PostgreSQL ne met pas ce champ à jour automatiquement lors d'un `UPDATE` : sans mécanisme dédié, le champ resterait `NULL` à chaque modification sauf si chaque requête SQL le renseignait explicitement.

Pour éviter toute omission dans le code applicatif, un trigger `BEFORE UPDATE` est défini dans `schema.sql` sur chacune de ces trois tables. Ce trigger appelle une fonction commune `fn_set_date_modification()` qui force `date_modification = NOW()` avant que la ligne soit écrite.

Résultat : toute requête `UPDATE` sur `utilisateurs`, `produits` ou `menus` met automatiquement à jour `date_modification`, quelle que soit la requête SQL émise par le code PHP. Le code applicatif n'a pas à gérer ce champ.

## 6 Sécurité applicative

### 6.1 Authentification et sessions

L'authentification du back-office repose sur des sessions serveur PHP. Après vérification des identifiants, l'application crée une session associée à l'utilisateur authentifié et à son rôle. L'identifiant de session est transmis au navigateur par un cookie de session.

Après une connexion réussie, l'application régénère l'identifiant de session afin de limiter le risque de fixation de session. Le cookie de session est configuré avec les attributs `HttpOnly` et `SameSite=Lax`. En production, l'attribut `Secure` est activé afin que le cookie ne soit transmis qu'en HTTPS.

Le stockage des sessions utilise le mécanisme natif de PHP côté serveur. Aucune table `sessions` n'est prévue dans le modèle métier ni dans la base de données applicative. La session n'a pas vocation à être historisée en base : elle sert uniquement au maintien de l'authentification entre les requêtes HTTP.

À chaque requête protégée, l'application contrôle les éléments suivants :

- présence d'une session active ;
- validité de la session côté serveur ;
- existence d'un compte utilisateur actif ;
- correspondance entre le rôle stocké en session et l'action demandée.

Par choix de sécurité, aucune connexion persistante de type `se souvenir de moi` n'est mise en place. La session est détruite en cas de déconnexion explicite, de fermeture du navigateur ou du poste client, ou après 30 minutes d'inactivité côté serveur.

Si une traçabilité des connexions est requise, elle sera assurée par les journaux applicatifs ou par la table `traces_actions`, sans ajouter de gestion métier spécifique des sessions.

### 6.2 Hachage des mots de passe

Les mots de passe des utilisateurs ne sont jamais stockés en clair dans la base de données. L'application enregistre uniquement un hachage du mot de passe dans le champ `mot_de_passe_hash`.

Le mécanisme retenu pour cette réalisation est `Bcrypt`, via les fonctions natives de PHP. Lors de la création d'un mot de passe ou de sa modification, le mot de passe saisi est transformé avec `password_hash()` avant son enregistrement. Lors de l'authentification, la vérification est réalisée avec `password_verify()`.

Ce choix permet de bénéficier d'un algorithme reconnu, d'un salage intégré et d'une vérification adaptée aux usages courants d'une application web interne. Le mot de passe original n'est jamais réversible à partir du hachage stocké.

Si les paramètres de hachage doivent évoluer dans le temps, l'application pourra vérifier la nécessité d'un rehash avec `password_needs_rehash()` lors d'une connexion réussie.

### 6.3 Protection contre la force brute

La protection contre la force brute repose sur une limitation des tentatives d'authentification sur le formulaire de connexion du back-office.

L'application limite le nombre de tentatives échouées pour un même identifiant et pour une même adresse IP. Au-delà d'un seuil défini, par exemple 5 échecs sur une période de 15 minutes, l'authentification est temporairement bloquée pendant une durée limitée de 10 à 15 minutes.

#### 6.3.1 Mécanisme de blocage

Les tentatives d'authentification échouées sont enregistrées et suivies selon le flux ci-dessous :

**Stockage des tentatives :**
- Chaque tentative échouée est enregistrée dans un fichier temporaire côté serveur, identifié par un hash de l'identifiant et l'adresse IP.
- Le fichier contient : le nombre de tentatives, la date de la première tentative et, si applicable, la date du blocage.
- Un fichier par combinaison (identifiant, adresse IP) permet de suivre l'historique rapide sans solliciter la base de données.

**Vérification avant authentification :**
1. À chaque soumission du formulaire de connexion, le contrôleur vérifie d'abord si le compte est bloqué.
2. Si le nombre de tentatives échouées atteint 5 dans une fenêtre de 15 minutes, le blocage est activé et sa date enregistrée.
3. Si le blocage est en cours (activé depuis moins de 15 minutes), la requête est immédiatement refusée avec un code HTTP 429 (`Too Many Requests`).
4. Si la fenêtre de 15 minutes est écoulée depuis le blocage, le compte est automatiquement débloqué et le compteur réinitialisé.

**Enregistrement des tentatives :**
- Si le compte n'est pas bloqué, l'authentification continue normalement (vérification identifiant et mot de passe en base).
- Si l'authentification échoue, la tentative est enregistrée et le compteur est incrémenté.
- À la 5ème tentative échouée, le fichier reçoit la date du blocage ; le compte devient inaccessible immédiatement.
- Si l'authentification réussit, le fichier de tentatives est supprimé (réinitialisation).

Les messages de retour affichés à l'utilisateur restent volontairement génériques, par exemple `identifiants invalides`, afin de ne pas indiquer si l'identifiant saisi existe réellement dans l'application. Lors d'un blocage en cours, le message est aussi générique : `Trop de tentatives. Réessayez dans 15 minutes.`

Les échecs de connexion et les périodes de blocage peuvent être journalisés côté serveur pour audit. Cette journalisation peut être réalisée dans les logs applicatifs ou dans la table `traces_actions` selon le niveau de suivi retenu.

En complément, lorsqu'un mot de passe saisi ne correspond pas, l'application applique un délai minimal de 3 secondes avant d'autoriser une nouvelle tentative. Cette temporisation fixe constitue une protection de repli si la limite de tentatives venait à être défaillante.

Si nécessaire, une temporisation progressive peut être ajoutée après plusieurs échecs successifs afin de ralentir davantage les tentatives automatisées, sans complexifier inutilement le fonctionnement global du back-office.

### 6.4 Validation et assainissement des entrées

Toutes les données reçues par l'application sont validées côté serveur avant traitement métier, avant enregistrement en base et avant rendu dans une vue HTML. Les contrôles côté navigateur ne sont considérés que comme une aide à la saisie. L'accès SQL repose sur des requêtes préparées avec PDO, et les données affichées dans les vues sont échappées à la sortie.

Le tableau ci-dessous distingue les données réellement saisies ou reçues par l'application des champs calculés par le serveur. Les identifiants techniques générés à la création ne sont pas saisis, mais certains identifiants existants sont reçus lors des actions de modification, de désactivation ou de changement d'état.

#### 6.4.1 Tableau concret des données saisies ou reçues

| Surface d'entrée | Champ ou donnée reçue | Contrôles concrets attendus | Traitement côté serveur |
|---|---|---|---|
| Connexion back-office | `identifiant` | Obligatoire, texte, `trim`, longueur <= 100, compte existant et actif | Recherche du compte, journalisation en cas d'échec |
| Connexion back-office | `mot_de_passe` | Obligatoire, non vide, jamais renvoyé dans les réponses ni les logs | Vérification avec `password_verify()` |
| Formulaires back-office modifiants | `csrf_token` | Obligatoire, valide, lié à la session courante | Refus de l'action si le jeton est absent, expiré ou invalide |
| Gestion utilisateurs | `id_utilisateur` | Obligatoire pour modification, désactivation ou changement de mot de passe d'un compte existant, entier positif, compte existant | Refus si le compte est absent ou si l'action n'est pas autorisée |
| Gestion utilisateurs | `id_role` | Obligatoire, entier positif, rôle existant dans `roles` | Refus si rôle inconnu ou non autorisé |
| Gestion utilisateurs | `identifiant` | Obligatoire, `trim`, longueur <= 100, unicité | Contrôle d'unicité avant insertion ou mise à jour |
| Gestion utilisateurs | `nom` | Obligatoire, `trim`, longueur <= 100 | Stockage normalisé |
| Gestion utilisateurs | `prenom` | Obligatoire, `trim`, longueur <= 100 | Stockage normalisé |
| Gestion utilisateurs | `actif` | Booléen attendu | Conversion explicite en booléen |
| Gestion utilisateurs | `mot_de_passe_actuel` | Obligatoire pour un changement de mot de passe personnel, non vide | Vérification avec `password_verify()` avant modification |
| Gestion utilisateurs | `nouveau_mot_de_passe` | Obligatoire à la création ou au changement, longueur minimale de 12 caractères | Hachage Bcrypt avant stockage |
| Gestion utilisateurs | `confirmation_mot_de_passe` | Obligatoire si un nouveau mot de passe est saisi, identique à `nouveau_mot_de_passe` | Refus si la confirmation ne correspond pas |
| Catégories | `id_categorie` | Obligatoire pour modification ou désactivation, entier positif, catégorie existante | Refus si catégorie absente ; désactivation via `actif = false` |
| Catégories | `nom` | Obligatoire, `trim`, longueur <= 100, unique | Contrôle d'unicité |
| Catégories | `description` | Optionnelle, texte, longueur <= 1000 | Échappée à l'affichage |
| Catégories | `actif` | Booléen attendu | Conversion explicite en booléen |
| Produits | `id_produit` | Obligatoire pour modification, disponibilité ou désactivation, entier positif, produit existant | Refus si produit absent ; désactivation via `actif = false` |
| Produits | `id_categorie` | Obligatoire, entier positif, catégorie existante | Refus si catégorie absente |
| Produits | `nom` | Obligatoire, `trim`, longueur <= 150 | Stockage normalisé |
| Produits | `description` | Obligatoire, texte, longueur <= 1000 | Échappée à l'affichage |
| Produits | `prix` | Obligatoire, nombre décimal >= 0 ; strictement > 0 si le produit est actif, disponible et commandable | Conversion décimale avant stockage |
| Produits | `image` | Champ obligatoire, type, taille, extension et nom contrôlés | Contrôle détaillé en 6.5 |
| Produits | `disponible` | Booléen attendu | Conversion explicite en booléen |
| Produits | `actif` | Booléen attendu | Conversion explicite en booléen |
| Menus | `id_menu` | Obligatoire pour modification, disponibilité ou désactivation, entier positif, menu existant | Refus si menu absent ; désactivation via `actif = false` |
| Menus | `nom` | Obligatoire, `trim`, longueur <= 150 | Stockage normalisé |
| Menus | `description` | Obligatoire, texte, longueur <= 1000 | Échappée à l'affichage |
| Menus | `prix` | Obligatoire, nombre décimal >= 0 ; strictement > 0 si le menu est actif, disponible et commandable | Conversion décimale avant stockage |
| Menus | `image` | Champ obligatoire, type, taille, extension et nom contrôlés | Contrôle détaillé en 6.5 |
| Menus | `disponible` | Booléen attendu | Conversion explicite en booléen |
| Menus | `actif` | Booléen attendu | Conversion explicite en booléen |
| Sections de menu | `id_section_menu` | Obligatoire pour modification, entier positif, section existante et rattachée au menu attendu | Refus si section absente ou incohérente avec le menu |
| Sections de menu | `id_menu` | Obligatoire, entier positif, menu existant | Refus si menu absent |
| Sections de menu | `nom` | Obligatoire, `trim`, longueur <= 100 | Stockage normalisé |
| Sections de menu | `obligatoire` | Booléen attendu | Conversion explicite en booléen |
| Sections de menu | `quantite_min` | Obligatoire, entier >= 0 | Refus si valeur incohérente |
| Sections de menu | `quantite_max` | Obligatoire, entier >= 1, >= `quantite_min` | Refus si borne incohérente |
| Options de menu | `id_option_menu` | Obligatoire pour modification ou désactivation, entier positif, option existante | Refus si option absente ; désactivation via `actif = false` |
| Options de menu | `id_section_menu` | Obligatoire, entier positif, section existante | Refus si section absente |
| Options de menu | `id_produit` | Obligatoire, entier positif, produit existant ; actif et disponible si l'option est active | Refus si produit absent, inactif ou indisponible pour une option active |
| Options de menu | `supplement_prix` | Obligatoire, nombre décimal >= 0 | Conversion décimale avant stockage |
| Options de menu | `actif` | Booléen attendu | Conversion explicite en booléen |
| Commande back-office ou API | `numero_retrait` | Obligatoire, `trim`, longueur <= 30, format simple sans contenu HTML | Refus si vide ou format invalide |
| Commande back-office ou API | `type_service` | Obligatoire, valeur autorisée : `sur_place` ou `a_emporter` | Refus si valeur hors liste |
| Commande back-office ou API | `date_heure_retrait_prevue` | Optionnelle, date et heure valides, non antérieures à `date_commande` si renseignées | Refus si format invalide ou incohérent |
| Commande back-office ou API | `lignes_commande` | Tableau obligatoire, au moins une ligne, maximum 50 lignes, chaque ligne validée récursivement | Refus si tableau vide, trop volumineux ou incomplet |
| Ligne de commande | `type_ligne` | Obligatoire, valeur autorisée : `produit` ou `menu` | Refus si valeur hors liste |
| Ligne de commande | `id_produit` | Obligatoire si `type_ligne = produit`, produit existant, actif et disponible ; `id_menu` absent | Refus si incohérent avec `type_ligne` ou non commandable |
| Ligne de commande | `id_menu` | Obligatoire si `type_ligne = menu`, menu existant, actif, disponible et composition commandable ; `id_produit` absent | Refus si incohérent avec `type_ligne`, menu incomplet ou non commandable |
| Ligne de commande | `quantite` | Obligatoire, entier > 0 et <= 99 | Refus si quantité invalide |
| Ligne de menu | `choix_menu` | Tableau obligatoire pour une ligne `menu`, organisé par sections du menu | Validation des sections obligatoires et du nombre de choix attendus |
| Choix de menu | `id_option_menu` | Option existante, active, rattachée à une section du menu choisi, produit associé actif et disponible | Refus si option absente, inactive, hors section ou hors menu |
| Choix de menu | choix par section | Respect de `quantite_min`, `quantite_max`, absence de doublon interdit | Refus si choix manquant, en trop ou non autorisé |
| Déclaration commande préparée | `id_commande` | Obligatoire, entier positif, commande existante au statut `a_preparer`, rôle `Preparation` ou `Administration` | Passage serveur au statut `preparee`, horodatage `date_preparation`, trace d'action |
| Remise commande client | `numero_retrait` | Obligatoire, `trim`, longueur <= 30, commande unique sur la journée, au statut `preparee`, non déjà livrée, rôle `Accueil` ou `Administration` | Passage serveur au statut `livree`, horodatage `date_livraison`, trace d'action |
| API externe | clé API `X-API-Key` | Obligatoire, format attendu, actif, non expiré | Refus de l'appel si absent, invalide ou non autorisé |
| API externe | Méthode, `Content-Type`, taille du corps | Méthode HTTP autorisée, JSON attendu pour les commandes, corps <= 1 Mo | Refus si méthode, type ou taille invalides |
| API externe | Corps JSON reçu | JSON valide, structure attendue, clés prévues uniquement : `numero_retrait`, `type_service`, `date_heure_retrait_prevue`, `lignes_commande` | Refus si JSON invalide, incomplet ou contenant des clés interdites |
| API catalogue | `id_categorie`, `disponible`, filtres catalogue | Paramètres optionnels, types attendus, catégorie existante si fournie, valeurs booléennes contrôlées | Filtrage du catalogue actif sans exposer les données internes |
| Paramètres de liste | `page`, `limit`, `sort`, filtres | `page` >= 1, `limit` entre 1 et 100, colonnes de tri autorisées uniquement, filtres typés | Conversion et refus si format invalide ou tri non autorisé |
| Paramètres de route | `id`, `id_*` cible | Entier positif, ressource existante, action autorisée pour le rôle courant | Refus si identifiant absent, invalide, inexistant ou non autorisé |

#### 6.4.2 Données non fiables, calculées ou refusées en entrée

| Champ stocké ou utilisé | Reçu directement depuis le client ? | Règle appliquée |
|---|---|---|
| `mot_de_passe_hash` | Non | Généré exclusivement côté serveur avec Bcrypt |
| `mot_de_passe`, `nouveau_mot_de_passe`, `confirmation_mot_de_passe` | Oui comme saisie temporaire | Jamais stockés en clair, jamais journalisés, jamais renvoyés dans une réponse |
| `source` de la commande | Non fiable | Déduite du point d'entrée utilisé : `api` ou `back_office` |
| `statut` initial d'une commande | Non | Fixé côté serveur à `a_preparer` lors de la création |
| `statut` cible libre | Non | Déduit de l'action métier ou de la route, par exemple déclarer `preparee` ou déclarer `livree` |
| `id_utilisateur_auteur` | Non | Pris depuis la session pour le back-office, `NULL` pour l'API |
| `date_commande` | Non | Horodatage serveur |
| `date_preparation` | Non | Fixée par le serveur lors du passage à `preparee` |
| `date_livraison` | Non | Fixée par le serveur lors du passage à `livree` |
| `libelle_article` | Non | Repris depuis le catalogue au moment de la commande |
| `prix_unitaire_applique` | Non | Calculé côté serveur à partir du catalogue ou du menu |
| `sous_total` | Non | Calculé côté serveur à partir du prix et de la quantité |
| `nom_section` | Non | Résolu depuis la structure du menu |
| `libelle_produit` | Non | Repris depuis le catalogue |
| `prix_supplement_applique` | Non | Calculé côté serveur selon l'option choisie |
| `total` | Non fiable | Toujours recalculé côté serveur à partir des lignes validées |
| Prix transmis dans un payload de commande | Non fiable | Ignorés ou refusés ; les prix, suppléments, sous-totaux et totaux sont recalculés côté serveur |
| `date_creation` / `date_modification` | Non | Gérées par le serveur |
| Clés primaires des nouveaux enregistrements | Non à la création | Générées par la base de données |
| Identifiants d'entités existantes | Oui comme références | Reçus uniquement pour consulter, modifier, désactiver ou changer l'état d'une donnée existante ; leur existence et l'autorisation sont contrôlées |
| `choix_ligne_commande.id_produit` | Non comme champ libre | Résolu depuis `id_option_menu` validé, puis recopié dans le choix de ligne |
| `traces_actions.action`, `table_cible`, `id_cible`, `date_action`, `details` | Non | Générées par l'application lors des actions sensibles |
| `roles.libelle` / `roles.description` | Non dans le flux courant | Données internes de référence, non ouvertes à une saisie libre standard |
| Champs inconnus ou non prévus dans un formulaire ou un JSON | Non | Refusés ou ignorés explicitement afin d'éviter le mass assignment |
| Clé API `X-API-Key` | Oui comme secret technique | Vérifiée côté serveur, jamais stockée en clair dans les logs applicatifs |

En cas de donnée invalide, l'application refuse le traitement. Le back-office renvoie un message de validation exploitable à l'utilisateur interne, tandis que l'API renvoie une réponse JSON structurée avec un code HTTP adapté, sans divulguer d'information sensible sur l'implémentation interne.

### 6.5 Contrôle des fichiers envoyés

L'envoi de fichiers concerne uniquement les images associées aux produits et aux menus du catalogue. Cette fonctionnalité est réservée aux utilisateurs autorisés à gérer le catalogue, en pratique le rôle `Administration`.

Seuls les formats d'image `JPEG` et `PNG` sont acceptés. La taille maximale autorisée pour un fichier est fixée à 2 Mo.

Avant tout enregistrement, l'application réalise les contrôles suivants côté serveur :

- présence réelle d'un fichier envoyé et absence d'erreur d'upload ;
- taille du fichier inférieure ou égale à 2 Mo ;
- extension autorisée : `.jpg`, `.jpeg` ou `.png` ;
- type MIME réel cohérent avec une image JPEG ou PNG, vérifié côté serveur ;
- rejet des noms de fichiers non fiables, des doubles extensions et des formats non attendus.

Le nom d'origine fourni par l'utilisateur n'est pas conservé comme nom de stockage. L'application génère un nom de fichier interne unique, puis enregistre l'image dans un dossier dédié du serveur.

Les fichiers envoyés ne doivent jamais être exécutables. Ils sont stockés comme ressources statiques du catalogue et ne sont pas traités comme du code. Si un fichier ne respecte pas les règles définies, l'upload est refusé et aucun enregistrement en base n'est réalisé.

En cas de remplacement d'une image existante, l'application met à jour uniquement le chemin ou le nom du nouveau fichier validé. L'action peut être tracée dans `traces_actions` lorsqu'elle concerne une modification sensible du catalogue.

### 6.6 Contrôle des autorisations

Le contrôle des autorisations repose sur une vérification côté serveur du rôle associé au compte utilisateur. Lors de chaque connexion, l'application vérifie en base l'identifiant, le mot de passe, l'état actif du compte et le rôle attribué. Si l'authentification est valide, une session serveur est ouverte avec l'identifiant de l'utilisateur et son rôle.

Une session valide ne suffit pas à autoriser une action. Pour chaque requête protégée du back-office, le serveur vérifie systématiquement les éléments suivants :

- présence d'une session active ;
- existence d'un compte toujours actif ;
- rôle autorisé pour l'action demandée ;
- cohérence de l'action avec l'état de la ressource ciblée, par exemple le statut courant d'une commande.

Par défaut, toute action non explicitement autorisée est refusée. Les droits ne sont jamais déterminés uniquement par l'interface. Le fait de masquer un bouton ou un menu dans le front ne remplace jamais le contrôle effectué côté serveur. Une tentative d'accès interdit peut être journalisée dans les logs applicatifs ou dans `traces_actions`.

L'interface du back-office est également filtrée selon le rôle de l'utilisateur connecté. Les pages, menus, catégories d'écrans et parties du back-office qui ne correspondent pas à son périmètre ne sont pas affichés dans la navigation. Cette restriction d'interface améliore la lisibilité et limite les erreurs de manipulation, mais elle est toujours doublée par un contrôle côté serveur.

Par exemple, un utilisateur `Accueil` n'a pas accès aux écrans d'administration du catalogue, à la composition des menus, aux catégories, ni à la gestion des comptes utilisateurs. De la même manière, un utilisateur `Preparation` n'accède qu'aux écrans utiles à la préparation des commandes et ne peut pas ouvrir les pages de gestion du catalogue, des comptes ou de remise client, même en tentant un accès direct par URL.

Les droits minimaux retenus pour le back-office sont les suivants :

- `Administration` : accès complet au back-office, gestion des comptes utilisateurs, du catalogue, des catégories, des produits, des menus, des sections de menu, des options de menu, des disponibilités et, si nécessaire, des autres actions opérationnelles ;
- `Preparation` : consultation des commandes au statut `a_preparer`, accès à leur détail, et déclaration d'une commande `preparee` ;
- `Accueil` : saisie manuelle d'une commande, consultation utile à la remise client, recherche par `numero_retrait`, et déclaration d'une commande `livree` lorsqu'elle est déjà `preparee`.

Les contrôles d'autorisation doivent donc empêcher notamment les cas suivants :

- un utilisateur non authentifié tente d'accéder à une action interne ;
- un utilisateur `Preparation` tente de modifier le catalogue ou les comptes ;
- un utilisateur `Accueil` tente de déclarer une commande `preparee` ;
- un utilisateur `Preparation` tente de déclarer une commande `livree` ;
- un utilisateur tente d'accéder à une action interdite en modifiant directement l'URL, le formulaire ou la requête envoyée.

En cas de déconnexion ou de fin de session, une nouvelle authentification est obligatoire. Le rôle est alors relu en base au moment de la reconnexion avant de redonner l'accès aux fonctionnalités autorisées.

### 6.7 Protection CSRF

Toutes les actions du back-office qui modifient des données (création, modification, désactivation, changement de statut) sont protégées contre les attaques de type CSRF (Cross-Site Request Forgery).

**Principe :** Un attaquant pourrait forger une page web externe qui soumet un formulaire vers le back-office Wacdo à l'insu d'un utilisateur connecté. Le navigateur enverrait automatiquement le cookie de session avec la requête, ce qui rendrait l'action légitime aux yeux du serveur sans que l'utilisateur ait rien fait volontairement.

**Mécanisme retenu :**
- À la création de la session, l'application génère un jeton CSRF aléatoire et le stocke en session (`$_SESSION['csrf_token']`).
- Ce jeton est inclus dans chaque formulaire HTML modifiant via un champ caché `_csrf`.
- Avant tout traitement d'une requête POST modifiante, le serveur vérifie que le jeton reçu correspond au jeton stocké en session, en utilisant une comparaison résistante aux attaques temporelles (`hash_equals`).
- Si le jeton est absent, expiré ou invalide, la requête est rejetée avec un code HTTP 403 sans traitement.
- Après chaque action POST réussie, le jeton est régénéré afin d'éviter la réutilisation.

**Routes concernées :** toutes les routes `POST` du back-office, soit toutes les routes sauf `GET /login`, `POST /login` (qui précède la session) et les endpoints `/api/*` (qui utilisent la clé API à la place).

**Ce qui n'est pas concerné :** les endpoints `/api/*` n'utilisent pas de formulaires HTML et sont protégés par la clé API `X-API-Key`. Le mécanisme CSRF ne s'applique pas à ces routes.

## 7 API externe

### 7.1 Principes généraux

L'API externe est l'interface technique entre le système de prise de commande externe, par exemple une borne, et le back-office Wacdo. Elle est séparée des routes internes du back-office et n'utilise pas les sessions des utilisateurs internes.

L'API expose uniquement les fonctionnalités nécessaires au système externe : consulter le catalogue commandable et transmettre une commande. Elle ne donne aucun accès aux comptes utilisateurs, aux sessions, aux écrans d'administration, aux traces internes ou aux fonctions de gestion du catalogue.

Les échanges se font en HTTP avec des données au format JSON. Les réponses de l'API sont structurées et utilisent des codes HTTP adaptés. En déploiement, les appels API doivent passer par HTTPS afin de protéger les échanges et la clé d'accès.

Le périmètre minimal retenu est volontairement simple :

| Méthode | Endpoint | Rôle |
|---|---|---|
| `GET` | `/api/catalogue` | Fournir le catalogue commandable au système externe |
| `POST` | `/api/commandes` | Recevoir une commande créée depuis le système externe |

Les autres actions du back-office, comme la gestion des produits, des menus, des utilisateurs, des rôles ou des statuts de commande, restent des fonctionnalités internes protégées par session et ne font pas partie de cette API externe.

### 7.2 Authentification de l'API

Tous les endpoints `/api/*` sont protégés par une clé API transmise dans l'en-tête HTTP `X-API-Key`. Cette clé identifie le système externe autorisé à appeler l'API.

La clé API n'est pas transmise dans l'URL, n'est jamais affichée dans les réponses et n'est jamais journalisée en clair. Elle est considérée comme un secret technique stocké côté serveur, par exemple dans la configuration de l'application ou dans une variable d'environnement, et ne fait pas partie du modèle métier.

En cas de clé absente, invalide, désactivée ou non autorisée, la requête est refusée avant tout traitement métier. L'API n'utilise pas les sessions du back-office et ne crée pas de session utilisateur interne.

Une limitation simple des appels peut être appliquée par adresse IP ou par clé API afin de limiter les abus. En cas de dépassement, l'API peut retourner le code `429 Too Many Requests`.

### 7.3 Catalogue — endpoint externe

Endpoint retenu : `GET /api/catalogue`

Cet endpoint retourne le catalogue actif et commandable utilisé par le système externe. Le terme "externe" signifie que l'endpoint est exposé au système de commande, pas qu'il est ouvert sans contrôle d'accès.

Le catalogue retourné doit contenir uniquement les données nécessaires à l'affichage et à la sélection d'une commande :

- catégories actives ;
- produits actifs et disponibles ;
- menus actifs et disponibles ;
- sections des menus ;
- options disponibles dans chaque section ;
- prix courants, libellés, descriptions et images utiles à l'affichage ;
- suppléments éventuels des options de menu.

Les données internes de gestion ne sont pas exposées. Les comptes utilisateurs, sessions, traces internes, dates techniques de modification et champs d'administration ne sont pas retournés par cet endpoint.

Chaque produit retourné doit au minimum exposer son `id_produit`, son `id_categorie`, son nom, sa description, son prix courant, son image publique et sa disponibilité. Chaque menu retourné doit exposer son `id_menu`, son nom, sa description, son prix courant, son image publique et sa structure de choix.

Chaque menu contient ses sections, et chaque section contient les options disponibles. Les options doivent exposer les identifiants nécessaires au futur envoi de commande, notamment `id_option_menu` et `id_produit`. Une option n'est retournée comme commandable que si l'option est active et si le produit associé est actif et disponible.

Format indicatif de réponse :

```json
{
   "success": true,
   "data": {
      "categories": [
         {
            "id_categorie": 1,
            "nom": "Burgers",
            "description": "Produits principaux"
         }
      ],
      "produits": [
         {
            "id_produit": 12,
            "id_categorie": 1,
            "nom": "Frites",
            "description": "Portion moyenne",
            "prix": "3.50",
            "image": "/uploads/produits/frites.jpg",
            "disponible": true
         }
      ],
      "menus": [
         {
            "id_menu": 3,
            "nom": "Menu Classic",
            "description": "Burger, accompagnement, boisson",
            "prix": "8.90",
            "image": "/uploads/menus/classic.jpg",
            "sections": [
               {
                  "id_section_menu": 7,
                  "nom": "Boisson",
                  "obligatoire": true,
                  "quantite_min": 1,
                  "quantite_max": 1,
                  "options": [
                     {
                        "id_option_menu": 31,
                        "id_produit": 18,
                        "nom": "Coca",
                        "supplement_prix": "0.00"
                     }
                  ]
               }
            ]
         }
      ]
   }
}
```

Les montants numériques peuvent être retournés sous forme de chaînes décimales afin d'éviter les imprécisions liées aux nombres flottants côté client.

### 7.4 Commandes — endpoint de réception

Endpoint retenu : `POST /api/commandes`

Cet endpoint reçoit une commande créée depuis le système externe. La requête doit être envoyée en JSON avec l'en-tête `Content-Type: application/json`.

Données attendues :

- `numero_retrait` ;
- `type_service` : `sur_place` ou `a_emporter` ;
- `date_heure_retrait_prevue`, optionnelle, au format date et heure ;
- `lignes_commande`, tableau contenant au moins une ligne et au maximum 50 lignes ;
- pour chaque ligne : `type_ligne`, `quantite`, puis `id_produit` ou `id_menu` selon le type ;
- pour chaque ligne de type `menu` : `choix_menu` contenant les `id_option_menu` sélectionnés.

Exemple indicatif de payload :

```json
{
   "numero_retrait": "24",
   "type_service": "sur_place",
   "date_heure_retrait_prevue": "2026-05-06T12:30:00+02:00",
   "lignes_commande": [
      {
         "type_ligne": "produit",
         "id_produit": 3,
         "quantite": 2
      },
      {
         "type_ligne": "menu",
         "id_menu": 1,
         "quantite": 1,
         "choix_menu": [
            { "id_option_menu": 8 },
            { "id_option_menu": 12 }
         ]
      }
   ]
}
```

Une ligne de type `produit` doit contenir uniquement `id_produit` et ne doit pas contenir `id_menu`. Une ligne de type `menu` doit contenir uniquement `id_menu` et ne doit pas contenir `id_produit`. Si une ligne de menu a une quantité supérieure à 1, les mêmes choix de menu s'appliquent à chaque exemplaire. Si les choix diffèrent, le système externe doit envoyer plusieurs lignes de menu distinctes.

Les choix de menu sont validés côté serveur. Chaque `id_option_menu` doit exister, être actif, appartenir à une section du menu choisi et référencer un produit actif et disponible. L'ensemble des choix doit respecter les sections obligatoires, `quantite_min`, `quantite_max` et l'absence de choix non autorisé.

Le serveur ne fait pas confiance aux prix, sous-totaux, totaux, statuts, sources ou dates techniques transmis par le client. Les champs inconnus ou interdits sont refusés. Le serveur impose les valeurs suivantes :

- `source = api` ;
- `statut = a_preparer` ;
- `date_commande` = date et heure serveur ;
- `id_utilisateur_auteur = NULL`.

La création d'une commande est réalisée dans une transaction afin d'enregistrer la commande, ses lignes et les choix de menu de manière cohérente. Si une ligne ou un choix est invalide, aucune partie de la commande n'est enregistrée.

Le total est recalculé côté serveur à partir du catalogue actif, des quantités, des menus, des produits et des suppléments d'options validés. Les prix et libellés appliqués au moment de la commande sont figés dans les lignes de commande.

Pour les lignes de menu, l'API reçoit des `id_option_menu`, mais la table `choix_ligne_commande` conserve les choix figés. Le serveur résout donc chaque option, puis enregistre le produit choisi, le nom de la section, le libellé du produit et le supplément appliqué.

Le périmètre minimum ne retient pas de mécanisme d'idempotence dédié. Si le système externe renvoie deux fois la même commande, l'API applique les validations métier habituelles et crée une commande uniquement lorsque la requête est valide. La prévention avancée des doublons côté système externe reste hors périmètre du Bloc 2.

Réponse de succès indicative :

```json
{
   "success": true,
   "data": {
      "id_commande": 125,
      "numero_retrait": "24",
      "source": "api",
      "statut": "a_preparer",
      "total": "18.90",
      "date_commande": "2026-05-06T12:10:00+02:00"
   }
}
```

### 7.5 Codes HTTP et format des erreurs

L'API retourne des réponses JSON structurées. En cas d'erreur, la réponse contient un code fonctionnel, un message général et, si nécessaire, le détail des champs invalides. Les erreurs ne doivent jamais exposer de requête SQL, de trace technique, de secret, de chemin serveur interne ou de détail d'implémentation sensible.

Format indicatif :

```json
{
   "success": false,
   "error": {
      "code": "VALIDATION_ERROR",
      "message": "Données invalides",
      "details": [
         {
            "field": "type_service",
            "message": "Valeur non autorisée"
         }
      ]
   }
}
```

Codes principaux :

| Code HTTP | Utilisation |
|---|---|
| `200` | Catalogue retourné avec succès |
| `201` | Commande créée avec succès |
| `400` | Requête mal formée, JSON invalide ou champ interdit |
| `401` | Clé API absente ou invalide |
| `403` | Clé reconnue mais non autorisée ou désactivée |
| `404` | Endpoint ou ressource demandée introuvable |
| `405` | Méthode HTTP non autorisée |
| `409` | Conflit métier, par exemple doublon ou commande déjà reçue |
| `413` | Corps de requête trop volumineux |
| `415` | Type de contenu non supporté |
| `422` | Données syntaxiquement valides mais refusées par les règles métier |
| `429` | Trop de requêtes en peu de temps |
| `500` | Erreur serveur non détaillée côté client |

Exemples de règles entraînant un refus : produit indisponible, menu incomplet, option de menu inactive, choix de menu hors section, quantité invalide, `type_service` non autorisé, total transmis par le client ou clé API absente.

## 8 Environnement de développement

L'environnement de développement local est standardisé avec Docker afin de pouvoir lancer le projet de manière identique sur plusieurs postes sans imposer une installation manuelle de PHP et de PostgreSQL sur la machine hôte. Docker ne suffit cependant pas, à lui seul, à décrire l'environnement de développement : il faut aussi préciser les services exécutés, la configuration locale, les volumes de travail et la manière de démarrer le projet.

### 8.1 Objectifs

- homogénéiser l'environnement de développement entre les membres du projet ;
- isoler les dépendances techniques du système hôte ;
- simplifier le démarrage local du back-office et de l'API ;
- rendre les tests locaux reproductibles ;
- limiter les écarts entre l'environnement de développement et l'environnement de déploiement.

### 8.2 Composition minimale retenue

L'environnement local est orchestré avec Docker Compose. La version minimum retenue comprend les deux services suivants :

| Service | Rôle |
|---|---|
| `app` | Conteneur applicatif PHP exécutant le back-office MVC et les endpoints API |
| `db` | Conteneur PostgreSQL hébergeant la base de données locale du projet |

Cette composition est suffisante pour le Bloc 2. Aucun autre service n'est retenu dans l'environnement minimal.

Le conteneur `app` embarque PHP 8.2 avec Apache. Il inclut les extensions nécessaires au projet, en particulier `pdo_pgsql` pour l'accès PostgreSQL, `fileinfo` pour les contrôles de type MIME sur les fichiers envoyés et `session` pour l'authentification interne. Les images Docker utilisées sont figées sur une version explicite et n'utilisent pas le tag `latest`.

Le service `app` utilise Apache avec le dossier `public` comme racine web. Le fichier `public/index.php` reste le point d'entrée unique de l'application MVC. Les routes du back-office et les endpoints `/api/*` sont servis par le même conteneur applicatif, mais séparés au niveau du routage applicatif.

Le code source du projet est monté en volume dans le conteneur `app` afin que les modifications locales soient prises en compte sans reconstruction systématique de l'image.

Le conteneur `db` héberge PostgreSQL 16. Les données sont stockées dans un volume dédié afin de conserver la base entre deux redémarrages des conteneurs. Depuis PHP, la base est jointe par le nom du service Docker `db`, et non par `localhost`.

Docker Compose inclut une vérification de disponibilité de PostgreSQL afin d'éviter que l'application ne tente d'accéder à la base avant son démarrage complet.

### 8.3 Configuration locale

La configuration locale doit séparer clairement les éléments versionnés et les secrets propres à chaque machine.

- les fichiers de code source, la configuration Docker et les scripts d'initialisation sont versionnés ;
- les secrets techniques et les valeurs spécifiques à un poste ne sont pas versionnés ;
- un fichier `.env.example` est versionné avec des valeurs factices ;
- un fichier `.env` local, ignoré par Git, fournit les variables d'environnement réelles nécessaires au lancement.

Variables retenues pour l'environnement local :

| Variable | Valeur retenue ou règle locale | Rôle |
|---|---|---|
| `APP_ENV` | `dev` | Mode d'exécution local |
| `APP_URL` | `http://localhost:8080` | URL locale d'accès au back-office |
| `APP_PORT` | `8080` | Port HTTP local exposé par Docker |
| `APP_DEBUG` | `true` | Affichage des erreurs en environnement local |
| `DB_HOST` | `db` | Hôte PostgreSQL utilisé par l'application |
| `DB_PORT` | `5432` | Port PostgreSQL interne au réseau Docker |
| `DB_NAME` | `wacdo_dev` | Nom de la base de données locale |
| `DB_USER` | `wacdo` | Compte PostgreSQL utilisé par l'application |
| `DB_PASSWORD` | secret local non versionné | Mot de passe du compte PostgreSQL |
| `API_KEY` | secret local non versionné | Clé attendue sur les endpoints `/api/*` |
| `UPLOAD_DIR` | `/var/www/html/storage/uploads` | Dossier utilisé pour stocker les fichiers envoyés |

La variable `API_KEY` correspond à la clé attendue par l'API du back-office pour les appels entrants du système externe. Elle ne doit pas être confondue avec une éventuelle clé d'un service tiers.

### 8.4 Volumes, ports et données de travail

Le code source du projet est partagé entre l'hôte et le conteneur `app` par montage de volume. Les données PostgreSQL sont conservées dans un volume nommé dédié au service `db`. Les fichiers téléversés, notamment les images de produits et de menus, sont stockés dans un répertoire persistant afin de pouvoir être testés localement sans être perdus à chaque redémarrage.

Le répertoire des fichiers envoyés est `storage/uploads`. Il n'est pas versionné avec le code source et il est accessible en écriture par le conteneur applicatif. Les fichiers acceptés restent soumis aux règles de sécurité définies dans la section upload : type MIME autorisé, extension contrôlée, taille maximale et nom de fichier généré par le serveur.

L'application web est exposée sur le port HTTP local `8080` afin de permettre l'accès au back-office depuis un navigateur. PostgreSQL n'est pas exposé sur la machine hôte. Il reste accessible uniquement sur le réseau Docker interne.

Les fichiers `.env`, les dumps de base, les fichiers uploadés localement et les éventuels secrets ne doivent pas être suivis par Git. Les données de production ne doivent pas être utilisées dans l'environnement local, sauf anonymisation explicite.

### 8.5 Initialisation et démarrage

Le démarrage local suit la logique suivante :

1. démarrer les services Docker Compose ;
2. initialiser la base de données locale avec le schéma PostgreSQL du projet ;
3. charger le jeu de données minimal de développement : les rôles, un utilisateur administrateur de test et des données catalogue ;
4. vérifier l'accès au back-office dans le navigateur ;
5. vérifier les endpoints API avec un client HTTP ou des requêtes de test.

Procédure de lancement retenue :

```bash
cp .env.example .env
docker compose up -d --build
docker compose logs -f app
```

L'initialisation de la base est réalisée par des scripts SQL versionnés avec le projet. Ces scripts créent le schéma, chargent les rôles de base, créent un compte administrateur de développement et fournissent des données catalogue minimales pour tester le back-office et le endpoint `GET /api/catalogue`.

La réinitialisation locale s'effectue par suppression du volume PostgreSQL local puis par réexécution des scripts SQL d'initialisation.

Le jeu de données de développement doit rester distinct des données de production. L'environnement local n'a pas vocation à utiliser des secrets de production, ni à partager une base distante commune.

### 8.6 Vérifications locales minimales

Après démarrage de l'environnement, les vérifications minimales sont les suivantes :

- le back-office est accessible depuis le navigateur via l'URL locale ;
- la connexion avec un compte administrateur de développement fonctionne ;
- PostgreSQL est accessible depuis le conteneur `app` via PDO ;
- le endpoint `GET /api/catalogue` répond avec une clé API valide ;
- le même endpoint refuse une requête sans clé API ;
- un envoi de fichier autorisé fonctionne dans les conditions prévues ;
- un fichier interdit est refusé.

Ces vérifications ne remplacent pas la stratégie de tests détaillée dans la section 10. Elles servent uniquement à confirmer que l'environnement local est correctement lancé.

### 8.7 Outils complémentaires

En complément de Docker, l'environnement de développement suppose l'usage des outils suivants :

- Docker Engine et Docker Compose ;
- Git pour le versionnement du code source ;
- Visual Studio Code ;
- un navigateur web moderne pour tester le back-office ;
- un terminal shell pour lancer Docker et les scripts du projet ;
- `curl` pour vérifier les endpoints API.

## 9 Déploiement

### 9.1 Prérequis système

Le déploiement du projet nécessite les composants suivants sur la machine hôte. Aucune installation manuelle de PHP ou de PostgreSQL sur l'hôte n'est requise : tous les services s'exécutent dans des conteneurs Docker.

| Composant | Version minimale | Rôle |
|---|---|---|
| Docker Engine | 24.x | Moteur de conteneurisation |
| Docker Compose | 2.x (plugin intégré) | Orchestration des services `app` et `db` |
| Git | ≥ 2.34 (présent nativement sur Ubuntu 22.04 LTS et supérieur) | Récupération du code source |
| `curl` | présent nativement sur toute distribution Linux récente | Vérification des endpoints API après déploiement |

L'installation de PHP, de PostgreSQL ou de leurs extensions sur la machine hôte n'est pas requise. Ces dépendances sont encapsulées dans les images Docker définies dans `docker-compose.yml`.

### 9.2 Contenu des images Docker

Le service `app` repose sur l'image `php:8.2-apache`. Les éléments suivants sont configurés dans le `Dockerfile` du projet :

**Extensions PHP installées :**

| Extension | Type | Rôle |
|---|---|---|
| `pdo` | native | Interface abstraite d'accès aux bases de données depuis PHP |
| `pdo_pgsql` | à installer | Driver PDO pour PostgreSQL |
| `fileinfo` | à installer | Détection du type MIME réel des fichiers téléversés |
| `session` | native | Gestion des sessions PHP — vérifier que le répertoire de session est accessible en écriture par `www-data` |

Les extensions `pdo_pgsql` et `fileinfo` sont installées via `docker-php-ext-install`. L'application ne démarre pas correctement si elles sont absentes.

**Configuration Apache :**

- `mod_rewrite` est activé via `a2enmod rewrite` dans le `Dockerfile`. Sans cette activation, le routeur MVC ne fonctionne pas : toutes les URL renvoient 404.
- Le `DocumentRoot` est configuré sur `/var/www/html/public`. Apache ne sert pas la racine du projet afin d'éviter d'exposer le code source, les fichiers de configuration et le répertoire `database/`.
- La directive `AllowOverride All` est activée sur le répertoire `public` afin que le fichier `.htaccess` du routeur soit pris en compte.
- Le répertoire `storage/uploads` est hors du `DocumentRoot`. Un fichier `.htaccess` dans ce répertoire interdit l'exécution PHP et l'indexage : `php_admin_flag engine off` et `Options -Indexes`.

**Répertoire d'uploads :**

Le répertoire `storage/uploads` (chemin interne : `/var/www/html/storage/uploads`) est créé dans le `Dockerfile` avec les permissions `www-data`. Il est monté via un volume nommé `uploads_data` dans Docker Compose afin que les fichiers téléversés soient conservés entre les redémarrages des conteneurs.

**Service `db` :**

Le service `db` repose sur l'image officielle `postgres:16`. L'image est configurée avec la locale `en_US.UTF-8` par défaut. Le projet n'impose pas de collation spécifique. Les variables d'environnement du conteneur `db` sont déclarées dans `docker-compose.yml` :

| Variable Docker | Valeur | Rôle |
|---|---|---|
| `POSTGRES_DB` | `wacdo_dev` | Base de données créée au premier démarrage |
| `POSTGRES_USER` | `wacdo` | Compte PostgreSQL créé automatiquement |
| `POSTGRES_PASSWORD` | mappé depuis `DB_PASSWORD` | Mot de passe du compte — obligatoire, le conteneur refuse de démarrer sans cette variable |

Ces variables sont distinctes des variables `DB_*` utilisées côté application PHP. Elles sont déclarées dans `docker-compose.yml` et leur valeur est lue depuis le fichier `.env`.

### 9.3 Procédure de déploiement

La procédure complète de premier déploiement est la suivante. Elle s'applique aussi bien en environnement local que sur un serveur Linux.

Les scripts SQL (`schema.sql` et `seed_dev.sql`) sont montés dans le conteneur `db` sous le répertoire `/scripts` via Docker Compose. L'initialisation est déclenchée manuellement avec `docker compose exec` pour rester explicite et reproductible.

**Étape 1 — Récupérer le code source**

```bash
git clone <url-du-depot> wacdo
cd wacdo
```

**Étape 2 — Configurer les variables d'environnement**

```bash
cp .env.example .env
```

Éditer le fichier `.env` et renseigner les valeurs réelles pour `DB_PASSWORD` et `API_KEY`. Toutes les autres variables sont préremplies avec des valeurs fonctionnelles pour un environnement local. Le fichier `.env` est déclaré dans `.gitignore` et ne doit jamais être versionné.

**Étape 3 — Démarrer les conteneurs**

```bash
docker compose up -d --build
```

Cette commande construit l'image `app`, démarre le service `db` (PostgreSQL 16) et expose le back-office sur le port défini par `APP_PORT` dans `.env` (valeur par défaut : `8080`).

**Étape 4 — Attendre que PostgreSQL soit prêt**

```bash
docker compose exec db pg_isready -U wacdo -d wacdo_dev
```

Relancer cette commande jusqu'à obtenir la réponse `accepting connections`. PostgreSQL démarre quelques secondes après le conteneur ; toute commande `exec psql` lancée avant que le moteur soit prêt échoue avec `connection refused`.

**Étape 5 — Initialiser la base de données**

L'initialisation s'effectue en deux étapes dans cet ordre strict :

```bash
# 1. Création du schéma (11 tables, contraintes, index)
docker compose exec db psql -U wacdo -d wacdo_dev -f /scripts/schema.sql

# 2. Chargement des données initiales (rôles, compte administrateur, catalogue de test)
docker compose exec db psql -U wacdo -d wacdo_dev -f /scripts/seed_dev.sql
```

Le script `schema.sql` crée les 11 tables avec toutes leurs contraintes et leurs index. Le script `seed_dev.sql` charge les données minimales nécessaires au fonctionnement du back-office : les trois rôles, un compte administrateur de développement et un jeu de données catalogue pour tester les endpoints. Ces deux fichiers sont versionnés dans `database/` et montés dans `/scripts` du conteneur `db` via Docker Compose.

**Étape 6 — Vérifier le démarrage**

```bash
# Vérifier l'état des conteneurs
docker compose ps

# Consulter les journaux applicatifs (interruptible avec Ctrl+C)
docker compose logs app
```

Le service est opérationnel lorsque `docker compose ps` affiche les services `app` et `db` avec l'état `running`. Aucune erreur PDO ou d'extension manquante ne doit apparaître dans les journaux.

### 9.4 Réinitialisation de la base

Pour repartir d'une base vide (après une modification du schéma ou un jeu de données corrompu) :

```bash
docker compose down -v
docker compose up -d

# Attendre que PostgreSQL soit prêt
docker compose exec db pg_isready -U wacdo -d wacdo_dev

# Réinitialiser le schéma et les données
docker compose exec db psql -U wacdo -d wacdo_dev -f /scripts/schema.sql
docker compose exec db psql -U wacdo -d wacdo_dev -f /scripts/seed_dev.sql
```

L'option `-v` supprime le volume PostgreSQL. Cette opération est irréversible sur les données locales.

### 9.5 Mise à jour du schéma

Toute modification du schéma de base de données en cours de développement est appliquée par la procédure complète de réinitialisation décrite en section 9.4. Aucune migration incrémentale n'est mise en place dans le périmètre du Bloc 2. La base est systématiquement recréée à partir des scripts versionnés.

### 9.6 Variables d'environnement de déploiement

**Variables côté application PHP (service `app`) :**

| Variable | Valeur attendue | Obligatoire |
|---|---|---|
| `APP_ENV` | `dev` (local) ou `prod` (serveur) | Oui |
| `APP_URL` | URL complète d'accès au back-office incluant le port si non-standard (ex. `http://localhost:8080`) | Oui |
| `APP_PORT` | Port HTTP exposé sur la machine hôte par Docker Compose — Apache écoute en interne sur le port `80` ; cette variable pilote uniquement le mapping Docker (`8080:80`) | Oui |
| `APP_DEBUG` | `false` obligatoire en production | Oui |
| `DB_HOST` | `db` (nom du service Docker, pas `localhost`) | Oui |
| `DB_PORT` | `5432` | Oui |
| `DB_NAME` | `wacdo_dev` | Oui |
| `DB_USER` | `wacdo` | Oui |
| `DB_PASSWORD` | secret non versionné | Oui |
| `API_KEY` | clé partagée avec le système externe | Oui |
| `UPLOAD_DIR` | `/var/www/html/storage/uploads` | Oui |

**Variables côté conteneur PostgreSQL (service `db`, déclarées dans `docker-compose.yml`) :**

| Variable Docker | Valeur | Obligatoire |
|---|---|---|
| `POSTGRES_DB` | `wacdo_dev` | Oui — le conteneur ne crée pas de base sans cette variable |
| `POSTGRES_USER` | `wacdo` | Oui |
| `POSTGRES_PASSWORD` | mappé depuis `DB_PASSWORD` du `.env` | Oui — le conteneur refuse de démarrer sans mot de passe |

Le fichier `.env` est déclaré dans `.gitignore` à la racine du projet et ne doit jamais être versionné. Le fichier `.env.example` est versionné avec des valeurs factices et sert de référence pour chaque nouveau poste.

### 9.7 Contrôle post-déploiement

Après chaque déploiement, les vérifications minimales suivantes sont effectuées :

```bash
# Vérifier que les 11 tables existent
docker compose exec db psql -U wacdo -d wacdo_dev -c "\dt"

# Vérifier que les 3 rôles sont chargés
docker compose exec db psql -U wacdo -d wacdo_dev -c "SELECT libelle FROM roles;"

# Vérifier que le back-office répond (adapter le port si APP_PORT != 8080)
curl -s -o /dev/null -w "%{http_code}" http://localhost:${APP_PORT:-8080}

# Vérifier que l'API répond avec une clé valide
curl -s -H "X-API-Key: <api_key>" http://localhost:${APP_PORT:-8080}/api/catalogue
```

Le déploiement est considéré comme valide lorsque les 11 tables sont présentes, les 3 rôles sont chargés et le back-office répond avec un code HTTP 200.

## 10 Tests et validation technique

La validation technique du projet est réalisée avant toute mise à disposition sur le serveur. La stratégie retenue repose sur des scénarios manuels reproductibles, des appels HTTP contrôlés sur l'API et des vérifications ciblées en base de données. Ce choix est cohérent avec le périmètre du Bloc 2, la taille du projet et une réalisation from scratch en PHP MVC.

Les tests sont exécutés sur l'environnement Docker local décrit dans la section 8. Toute anomalie sur un scénario critique bloque la mise à disposition de l'application sur le serveur.

### 10.1 Principes retenus

- la validation couvre le back-office, l'API externe, la sécurité applicative et l'intégrité des données ;
- les parcours critiques sont rejoués après chaque modification importante sur l'authentification, les droits, le catalogue, les commandes ou l'API ;
- les tests sont réalisés avec les outils réellement retenus dans le projet : navigateur, `curl`, journaux applicatifs et requêtes SQL de contrôle ;
- les résultats sont consignés dans une grille de recette simple afin de garder une preuve de validation avant mise à disposition ;
- aucune mise en production n'est retenue tant que les scénarios critiques ne sont pas validés.

### 10.2 Périmètre des tests

| Domaine | Objectif | Contrôles retenus | Résultat attendu |
|---|---|---|---|
| Authentification back-office | Vérifier l'accès sécurisé aux écrans internes | connexion valide, mot de passe invalide, compte inactif, déconnexion, blocage après échecs répétés | seules les connexions autorisées ouvrent une session ; les erreurs restent génériques ; le blocage fonctionne |
| Autorisations par rôle | Vérifier la séparation des droits `Administration`, `Preparation`, `Accueil` | accès normal, accès direct par URL interdite, action métier interdite, remise d'une commande par rôle non autorisé | chaque rôle n'accède qu'à son périmètre ; toute action interdite est refusée côté serveur |
| Gestion du catalogue | Vérifier la gestion des catégories, produits, menus, sections et options | création, modification, désactivation, contrôle des disponibilités, cohérence des menus commandables | le catalogue reste cohérent, les données sont enregistrées correctement et les éléments désactivés ne deviennent pas commandables |
| Commandes back-office | Vérifier les parcours internes liés aux commandes | création manuelle d'une commande, passage à `preparee`, passage à `livree`, contrôle des rôles, contrôle du `numero_retrait` | le cycle `a_preparer` -> `preparee` -> `livree` est respecté et les dates associées sont correctement mises à jour |
| API `GET /api/catalogue` | Vérifier la communication avec le système externe pour la lecture du catalogue | appel avec clé API valide, appel sans clé, contrôle du JSON retourné, présence des IDs nécessaires, absence de données internes | le catalogue JSON est exploitable par la borne, seules les données commandables sont exposées et les accès non autorisés sont refusés |
| API `POST /api/commandes` | Vérifier la réception d'une commande externe | payload valide, JSON invalide, champ interdit, produit indisponible, menu incomplet, clé API invalide | une commande valide est créée ; les données invalides sont refusées avec un code HTTP cohérent |
| Uploads catalogue | Vérifier le contrôle des images envoyées | image JPEG valide, image PNG valide, extension interdite, taille excessive, type MIME incohérent | seuls les fichiers autorisés sont enregistrés ; les autres sont refusés sans écriture en base |
| Intégrité base de données | Vérifier la cohérence des enregistrements | recalcul des totaux, transaction sur création de commande, contrôle des clés étrangères, contrôle des statuts et contraintes métier | aucune commande partielle n'est enregistrée ; les totaux sont cohérents ; les contraintes de données sont respectées |
| Journaux et traces | Vérifier la traçabilité minimale | trace d'action sensible, échec de connexion, changement de statut de commande | les actions sensibles et les erreurs importantes sont observables dans les journaux ou dans `traces_actions` |

### 10.3 Scénarios critiques à valider

Avant toute mise à disposition sur le serveur, les scénarios suivants sont obligatoirement validés :

1. connexion d'un administrateur avec ouverture de session valide ;
2. refus d'une connexion avec mot de passe invalide ;
3. refus d'accès à une page d'administration depuis un compte `Accueil` ou `Preparation` ;
4. création ou modification d'un produit avec envoi d'une image autorisée ;
5. refus d'un fichier non autorisé sur le catalogue ;
6. appel réussi à `GET /api/catalogue` avec clé API valide ;
7. refus de `GET /api/catalogue` sans clé API ;
8. création réussie d'une commande via `POST /api/commandes` avec produit simple ;
9. création réussie d'une commande via `POST /api/commandes` avec menu et choix valides ;
10. refus d'une commande API contenant un produit indisponible ou un menu incomplet ;
11. passage d'une commande au statut `preparee` par un compte autorisé ;
12. passage d'une commande au statut `livree` par un compte autorisé ;
13. vérification en base que le total, les lignes et les choix de menu correspondent aux données validées ;
14. vérification qu'un échec dans la création d'une commande ne laisse aucun enregistrement partiel.

### 10.4 Modalités de validation

La validation technique repose sur les modalités suivantes :

- les écrans du back-office sont testés depuis un navigateur avec des comptes de développement représentatifs des trois rôles ;
- les endpoints API sont testés avec `curl` en envoyant les payloads JSON attendus par la borne ;
- la base PostgreSQL est contrôlée après les scénarios critiques afin de vérifier l'état réel des commandes, des lignes, des choix et des traces ;
- les journaux applicatifs sont consultés après les erreurs volontaires afin de vérifier l'absence de fuite sensible et la présence d'une trace exploitable ;
- chaque correction sur un point critique entraîne une revalidation ciblée du scénario concerné et des parcours voisins.

### 10.5 Critères de validation finale

La validation technique finale est considérée comme acquise lorsque les conditions suivantes sont remplies :

- l'environnement local démarre sans erreur bloquante ;
- les comptes internes fonctionnent selon les rôles définis ;
- le catalogue est gérable depuis le back-office et correctement exposé dans `GET /api/catalogue` ;
- les commandes internes et API respectent les règles métier et le cycle de statut prévu ;
- les protections de base contre les accès non autorisés, les uploads interdits, les données invalides et les tentatives d'appel API non autorisées sont effectives ;
- les données persistées en base sont cohérentes avec les actions réalisées ;
- aucun scénario critique ne reste en échec au moment de la livraison.

## 11 Conventions de code

Le développement du projet suit des conventions simples afin de garder un code lisible, cohérent et facile à maintenir pendant la réalisation et lors de la soutenance.

### 11.1 Principes généraux

- le projet est développé from scratch en PHP orienté objet avec une architecture MVC ;
- l'héritage est utilisé de manière limitée et utile, notamment pour factoriser les comportements communs dans un `BaseController` et un `BaseRepository` ;
- chaque couche conserve une responsabilité claire ;
- le code reste simple, lisible et limité aux besoins du Bloc 2 ;
- toute duplication de logique doit être évitée ou factorisée.

### 11.2 Règles de nommage

- les classes sont nommées en PascalCase ;
- les contrôleurs se terminent par `Controller` ;
- les services se terminent par `Service` ;
- les repositories se terminent par `Repository` ;
- les méthodes et les variables sont nommées en camelCase ;
- les constantes sont nommées en majuscules avec underscore ;
- les vues utilisent des noms explicites en snake_case.

### 11.3 Répartition des responsabilités

- les contrôleurs reçoivent la requête, déclenchent le traitement adapté et préparent la réponse ;
- les services portent la logique métier ;
- les repositories centralisent l'accès à la base PostgreSQL via PDO ;
- les vues se limitent à l'affichage HTML ;
- les endpoints API renvoient du JSON et n'utilisent pas les vues du back-office ;
- aucune requête SQL n'est écrite dans les vues.

### 11.4 Règles de qualité et de sécurité

- toute donnée reçue est validée côté serveur ;
- toute requête SQL passe par des requêtes préparées avec PDO ;
- aucun secret n'est écrit en dur dans le code source ;
- les messages d'erreur techniques ne sont pas affichés à l'utilisateur final ;
- les contrôles d'autorisation sont systématiquement réalisés côté serveur.

### 11.5 Formatage minimal

- l'indentation retenue est de 4 espaces ;
- l'encodage des fichiers texte est UTF-8 ;
- les commentaires sont ajoutés uniquement lorsqu'ils apportent une information utile ;
- les méthodes restent courtes et centrées sur une seule responsabilité ;
- le code inutilisé est supprimé avant livraison.
