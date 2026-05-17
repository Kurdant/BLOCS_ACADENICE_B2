# Suivi des sprints

| Sprint | Objectif | Agent conseillé | Statut |
|---|---|---|---|
| 0 | Socle sécurité MVC | `bmm-dev` | À faire |
| 1 | Authentification + login | `bmm-dev` | À faire |
| 2 | Profil personnel | `bmm-dev` | À faire |
| 3 | Repositories + services transverses | `bmm-dev` | À faire |
| 4 | Gestion utilisateurs | `bmm-dev` | À faire |
| 5 | Catalogue simple | `bmm-dev` puis `fullstack-dev` | À faire |
| 6 | Menus composés | `bmm-dev` | À faire |
| 7 | Noyau commandes | `bmm-dev` | À faire |
| 8 | Back-office commandes | `fullstack-dev` + `bmm-dev` | À faire |
| 9 | API externe | `bmm-dev` | À faire |
| 10 | UI globale + navigation | `fullstack-dev` | À faire |
| 11 | Recette finale | `byan` + reviewer dev | À faire |

## Sprint 0 - Socle sécurité MVC

### Objectif

Compléter le socle commun nécessaire avant les fonctionnalités métier : sessions, revalidation du compte actif, rôles, CSRF, messages flash et helpers de contrôleur.

### En termes simples

> Avant d'implémenter la moindre fonctionnalité, l'application a besoin d'une base sécurisée commune. Ce sprint pose les fondations que tous les autres réutilisent : la gestion sécurisée des sessions (cookies HttpOnly, expiration automatique, protection contre la fixation de session), le mécanisme CSRF qui protège chaque formulaire contre les soumissions frauduleuses depuis un site tiers, le système de vérification des rôles qui bloque l'accès aux pages réservées, les messages flash (les bandons vert/rouge affichés après une action), et la page de connexion. Aucune fonctionnalité visible pour l'utilisateur final — c'est l'infrastructure qui rend tout le reste possible et sécurisé.

### Ce qui a été codé

- `app/Core/BaseController.php` — Méthodes Sprint 0 déjà présentes : `currentUser()`, `requireAuth()`, `requireRole()`, `refreshAuthenticatedUser()`, `csrfToken()`, `requireCsrf()`, `regenerateCsrfToken()`, `flash()`, `getFlash()`, `input()`, `abort()`, `forbidden()`, `destroySession()`.
- `public/index.php` — Session sécurisée déjà configurée : HttpOnly, SameSite=Lax, Secure en prod, timeout 30 min inactivité, autoloader PSR-4, chargement .env.
- `app/Repositories/UtilisateurRepository.php` — `findActiveWithRoleById()` et `findActiveWithRoleByIdentifiant()` avec JOIN `roles`.
- `app/Views/auth/login.php` — Formulaire complet : identifiant, mot de passe, affichage erreur flash, action POST /login, inclusion login.css, sans champ CSRF (exclusion conforme CDC 6.7).
- `app/Views/partials/flash.php` — Affichage flash `success`/`error` avec `htmlspecialchars`, consommé une seule fois via `getFlash()`.

### Fichiers modifiés

| Fichier | Statut |
|---|---|
| `app/Core/BaseController.php` | Existait déjà complet — aucune modification nécessaire |
| `public/index.php` | Existait déjà complet — aucune modification nécessaire |

### Fichiers créés / complétés

| Fichier | Statut |
|---|---|
| `app/Repositories/UtilisateurRepository.php` | Créé et complet |
| `app/Views/auth/login.php` | Complété (était un stub vide) |
| `app/Views/partials/flash.php` | Complété (était un stub vide) |

### Vérification à 3 agents

| Agent | Rôle | Verdict | Détail |
|---|---|---|---|
| bmm-dev (code review) | Conformité code / CDC | ✅ | `abort()` whitelist OK, `UtilisateurRepository` JOIN correct, session config complète |
| Sécurité (OWASP) | Session, CSRF, XSS | ✅ | CSRF `hash_equals` + régénération OK, `session.use_strict_mode=1` présent, `use_only_cookies=1` présent, XSS couvert |
| Fullstack (intégration) | Vues, CSS, layout | ✅ | `$flash` en scope via `extract()`, chemins CSS corrects, `view()` gère `layout => false` pour login |

### Résultat

✅ **Sprint 0 validé** — Socle MVC opérationnel. Session sécurisée, CSRF prêt, flash PRG, relecture compte actif. Prêt pour Sprint 1.

## Sprint 1 - Authentification + login

### Objectif

Implémenter l'authentification back-office : formulaire de connexion, vérification identifiant/mot de passe, blocage force brute, création de session et déconnexion.

### En termes simples

> Le CDC impose qu'aucune fonctionnalité du back-office n'est accessible sans authentification. Ce sprint implémente exactement ça : le formulaire de connexion avec affichage des erreurs, la vérification du mot de passe, la protection contre le brute force (5 tentatives → blocage 15 min), la création de session après connexion réussie, et la déconnexion sécurisée via un formulaire POST (et non un simple lien, qui serait contournable). À l'issue de ce sprint, le personnel Wacdo peut se connecter et se déconnecter en toute sécurité.

### Ce qui a été codé

- `app/Services/LoginAttemptService.php` — Brute force : clé sha256(identifiant|ip), fichier JSON /tmp, 5 échecs en 15min → blocage 15min, `flock` atomique, délai 3s après chaque échec, réinitialisation sur succès.
- `app/Services/AuthService.php` — Vérification credentials via `password_verify`, mitigation timing avec dummy hash bcrypt cost 12 valide, retourne tableau session-ready ou null.
- `app/Repositories/UtilisateurRepository.php` — Ajout de `findForAuth()` : retourne `mot_de_passe_hash` + données utilisateur, utilisé uniquement par AuthService.
- `app/Controllers/AuthController.php` — Implémentation de `showLogin()`, `login()`, `logout()` : CSRF sur POST /login, PRG pattern, `session_regenerate_id(true)` après auth, réaffichage identifiant saisi après erreur.
- `app/Core/BaseController.php` — `destroySession()` passé en `protected` + `session_start()` ajouté après destroy pour permettre le flash post-logout.
- `app/Views/layout.php` — Lien déconnexion remplacé par form POST /logout avec token CSRF.

### Fichiers créés

| Fichier | Détail |
|---|---|
| `app/Services/LoginAttemptService.php` | Nouveau |
| `app/Services/AuthService.php` | Nouveau |

### Fichiers modifiés

| Fichier | Modification |
|---|---|
| `app/Repositories/UtilisateurRepository.php` | Ajout `findForAuth()` |
| `app/Controllers/AuthController.php` | 3 stubs → implémentés |
| `app/Core/BaseController.php` | `destroySession()` en protected + session_start post-destroy |
| `app/Views/layout.php` | Lien GET /logout → form POST + CSRF |

### Vérification à 3 agents

| Agent | Rôle | Verdict | Correctifs appliqués |
|---|---|---|---|
| bmm-dev (code review) | Conformité code / CDC | ✅ | CSRF ajouté sur POST /login (Login CSRF attack), dummy hash valide remplacé |
| Sécurité (OWASP) | Session, timing, brute force | ✅ | `flock` atomique sur fichier /tmp (TOCTOU race condition) |
| Fullstack (intégration) | Flux login/logout, layout | ✅ | Lien GET /logout → form POST, `$identifiant` réaffiché via `$_SESSION['last_identifiant']` |

### Résultat

✅ **Sprint 1 validé** — Authentification fonctionnelle. Login CSRF, session fixation, timing attack, brute force tous couverts. Prêt pour Sprint 2.

## Sprint 2 - Profil personnel

### Objectif

Implémenter le changement de mot de passe personnel accessible à tous les utilisateurs authentifiés.

### En termes simples

> Le CDC prévoit que tout utilisateur authentifié, quel que soit son rôle, peut modifier son propre mot de passe. Ce sprint implémente cette fonctionnalité : formulaire accessible depuis la sidebar, vérification de l'ancien mot de passe avant tout changement, contraintes de longueur (8 à 72 caractères), et régénération de la session après le changement (si le mot de passe change, le jeton de session change aussi). Le serveur Apache est également durci : listing des répertoires désactivé et headers de sécurité HTTP ajoutés.

### Ce qui a été codé

- `app/Repositories/UtilisateurRepository.php` — Ajout `findHashById()` (retourne uniquement le hash par ID, filtre actif=true) et `updatePasswordById()` (UPDATE avec filtre actif=true pour cohérence défensive).
- `app/Controllers/UtilisateurController.php` — `editPassword()` : requireAuth + rendu vue avec flash et token CSRF. `updatePassword()` : requireAuth + requireCsrf, validation (champs vides, 8–72 chars, confirmation), brute force via LoginAttemptService, password_verify, password_hash bcrypt cost 12, session_regenerate_id(true) post-changement.
- `app/Views/utilisateurs/edit-password.php` — Formulaire 3 champs (actuel, nouveau, confirmation), minlength=8, maxlength=72, CSRF caché, autocomplete correct.
- `app/Views/layout.php` — Ajout lien "Changer mon mot de passe" dans sidebar.
- `apache.conf` — `Options -Indexes` (suppression directory listing) + headers sécurité HTTP.

### Fichiers créés

| Fichier | Détail |
|---|---|
| `app/Views/utilisateurs/edit-password.php` | Nouveau |

### Fichiers modifiés

| Fichier | Modification |
|---|---|
| `app/Repositories/UtilisateurRepository.php` | Ajout `findHashById()` + `updatePasswordById()` |
| `app/Controllers/UtilisateurController.php` | `editPassword()` + `updatePassword()` implémentés |
| `app/Views/layout.php` | Lien "Mon compte" dans sidebar |
| `apache.conf` | `Options -Indexes`, headers X-Content-Type-Options / X-Frame-Options / Referrer-Policy |

### Vérification à 3 agents

| Agent | Rôle | Verdict | Correctifs appliqués |
|---|---|---|---|
| bmm-dev (code review) | Conformité code / CDC | ✅ | Borne max 72 chars ajoutée (bcrypt truncation silencieuse) |
| Sécurité (OWASP) | A01–A07 | ✅ | `session_regenerate_id` post-changement (A07), `Options -Indexes` + headers HTTP (A05), rate limiting via LoginAttemptService (A04), `actif=true` sur UPDATE (TOCTOU) |
| Fullstack (intégration) | Flux GET/POST, layout, flash | ✅ | Lien sidebar `/mon-compte/mot-de-passe` ajouté |

### Résultat

✅ **Sprint 2 validé** — Changement de mot de passe fonctionnel. IDOR impossible, bcrypt cost 12, rate limiting réutilisé, session régénérée après changement. Prêt pour Sprint 3.

## Sprint 3 - Repositories + services transverses

### Objectif

Créer les repositories et services communs nécessaires aux sprints métier : accès aux données, traces et validation serveur.

### En termes simples

> Avant d'écrire la moindre page métier (catégories, produits, commandes), toutes les classes d'accès à la base de données doivent exister. Ce sprint crée la couche données complète : chaque table du schéma a désormais sa classe Repository qui lit et écrit proprement via des requêtes préparées. C'est aussi ici qu'est créé le TraceService — le système qui enregistre dans `traces_actions` qui a fait quoi et quand, conformément à l'exigence du CDC de traçabilité des actions sensibles. Et le Validator, qui accumule les erreurs de formulaire sans interrompre le flux. Aucune page visible pour l'utilisateur — c'est la couche sur laquelle tous les sprints métier s'appuient.

### Ce qui a été codé

- **CategorieRepository** : `findAll()`, `findAllActive()`, `findById()`, `create()`, `update()`, `desactiver()`
- **ProduitRepository** : `findAll()`, `findAllActive()`, `findAllAvailableActive()`, `findById()`, `findByCategorieId()`, `create()`, `update()` (image optionnelle), `desactiver()`
- **MenuRepository** : `findAll()`, `findAllActive()`, `findAllAvailableActive()`, `findById()`, `create()`, `update()` (image optionnelle), `desactiver()`
- **SectionMenuRepository** : `findByMenuId()` (JOIN sections+options+produits, reconstruction imbriquée PHP), `findById()`, `create()`
- **OptionMenuRepository** : `findBySectionId()` (JOIN produits), `findById()`, `create()`, `desactiver()`
- **CommandeRepository** : `findAll(?statut)`, `findById()`, `findByIdWithLignes()` (3 requêtes imbriquées), `create()` (**transaction atomique** : commandes → lignes_commande → choix_ligne_commande), `marquerPreparee()`, `marquerLivree()`
- **TraceService** : `log(action, tableCible, idCible, details)` — insère dans `traces_actions`, lit `$_SESSION['user']['id']`, guard `session_status()`
- **Validator** (`app/Core/Validator.php`) : `required()`, `maxLength()`, `minLength()`, `positiveNumber()`, `nonNegativeNumber()`, `intBetween()`, `inList()`, `fails()`, `errors()`, `firstError()` — accumulation sans exception, chaînable
- **UtilisateurRepository** — ajouts pour Sprint 4 : `findAll()`, `findAllRoles()`, `existsByIdentifiant()` (avec exclude pour update), `create()`, `update()`, `desactiver()`

### Fichiers modifiés

| Fichier | Modification |
|---|---|
| `app/Repositories/UtilisateurRepository.php` | Ajout de 6 méthodes CRUD + `filter_var` → `=== 't'` pour booléens PostgreSQL |

### Fichiers ajoutés

| Fichier | Rôle |
|---|---|
| `app/Repositories/CategorieRepository.php` | CRUD catégories |
| `app/Repositories/ProduitRepository.php` | CRUD produits + JOIN catégorie |
| `app/Repositories/MenuRepository.php` | CRUD menus |
| `app/Repositories/SectionMenuRepository.php` | Sections de menu + options imbriquées |
| `app/Repositories/OptionMenuRepository.php` | Options d'une section |
| `app/Repositories/CommandeRepository.php` | Commandes + lignes + choix, transaction |
| `app/Services/TraceService.php` | Audit `traces_actions` |
| `app/Core/Validator.php` | Validation serveur accumulatrice |

### Vérification à 3 agents

**Agent 1 — Revue code :**
- Bug corrigé : `filter_var('t', FILTER_VALIDATE_BOOLEAN)` retourne `false` pour les booléens PostgreSQL → remplacé par `=== 't'` dans **tous** les repositories (y compris UtilisateurRepository existant)
- Bug corrigé : `(string) null === ''` dans ProduitRepository::update() pour description nullable → `isset()` + null
- Correction architecturale : TraceService ne doit pas étendre BaseRepository — PDO injecté via constructeur

**Agent 2 — Sécurité OWASP :**
- A09 corrigé : TraceService ajoute `session_status() !== PHP_SESSION_ACTIVE` → return immédiat au lieu de crash ou trace silencieuse
- A04 RAS : transaction `beginTransaction/commit/rollBack` déjà en place dans CommandeRepository::create()
- A03 RAS : SQL dynamique dans findAll() et update() est hardcodé, aucune injection possible
- Fuite de données RAS : `mot_de_passe_hash` absent du SELECT dans findAll()

**Agent 3 — Intégration fullstack :**
- Bug critique corrigé : constructeur TraceService `__construct(private readonly \PDO $pdo)` → TypeError sur `null`. Remplacé par `__construct(?\PDO $pdo = null)` + `Database::connection()` fallback, aligné sur le pattern BaseRepository
- Validator RAS : aucune dépendance, `new Validator()` direct dans les contrôleurs
- CommandeRepository::create() RETURNING en boucle RAS : `pdo_pgsql` bufferise chaque résultat, pattern validé

### Résultat

✅ **Sprint 3 validé** — 8 repositories/services créés. Booléens PostgreSQL correctement normalisés (`=== 't'`). TraceService autonome (no extends, PDO optionnel). Validator prêt pour tous les sprints CRUD. Transaction commande atomique. Prêt pour Sprint 4.

## Sprint 4 - Gestion utilisateurs

### Objectif

Implémenter le CRUD des utilisateurs réservé au rôle `Administration`.

### En termes simples

> Le CDC réserve au rôle Administration la gestion complète des comptes du personnel interne. Ce sprint implémente les pages de gestion des utilisateurs : liste avec statut et rôle, création (avec choix du rôle), modification (dont changement de mot de passe optionnel), et désactivation. Le principe du CDC est respecté : un compte ne peut pas être supprimé physiquement, seulement désactivé, pour préserver l'historique des actions. Deux garde-fous sont en place : on ne peut pas se désactiver soi-même, et on ne peut pas désactiver le dernier administrateur actif (ce qui bloquerait l'accès à l'ensemble du back-office).

### Ce qui a été codé

- **UtilisateurController** — 6 méthodes CRUD Admin : `index`, `create`, `store`, `edit`, `update`, `desactiver`  
  - `requireRole(['Administration'])` + `requireCsrf()` sur tous les POST  
  - Validator pour les champs (identifiant, nom, prénom, mot de passe)  
  - Mot de passe optionnel dans `update` (vide = inchangé)  
  - Protection anti-auto-désactivation (id === currentUser.id)  
  - Protection dernier administrateur actif (countActiveByRole ≤ 1)  
  - PRG pattern partout, TraceService sur toutes les mutations  
- **UtilisateurRepository** — ajout de `findById(int $id): ?array` (sans filtre actif, pour admin) et `countActiveByRole(string $role): int`  
- **Vues** — `utilisateurs/index.php`, `utilisateurs/create.php`, `utilisateurs/edit.php`  
- **layout.php** — sidebar corrigée : lien `Utilisateurs` conditionnel rôle Administration (remplace liens cassés `/employes` et `/restaurants`)

### Fichiers modifiés

| Fichier | Nature |
|---|---|
| `app/Controllers/UtilisateurController.php` | Ajout 6 méthodes CRUD (index, create, store, edit, update, desactiver) |
| `app/Repositories/UtilisateurRepository.php` | Ajout `findById()` + `countActiveByRole()` |
| `app/Views/layout.php` | Sidebar corrigée — lien Utilisateurs conditionnel |

### Fichiers ajoutés

| Fichier | Rôle |
|---|---|
| `app/Views/utilisateurs/index.php` | Liste utilisateurs — table, badges, bouton désactiver CSRF |
| `app/Views/utilisateurs/create.php` | Formulaire création — tous champs, rôle select, mot de passe |
| `app/Views/utilisateurs/edit.php` | Formulaire édition — champs prefilled, mot de passe optionnel |

### Vérification à 3 agents

**Agent 1 — Code review (logique PHP) :**  
3 bugs détectés et corrigés :
1. `in_array` strict false sur rôle PDO string vs int → `array_map('intval', ...)` 
2. Protection auto-désactivation `===` strict avec cast `(int)` explicite  
3. XSS JS dans `onsubmit confirm()` — `htmlspecialchars` encode `'` en `&#039;` (décodé par HTML avant JS) → remplacé par `json_encode(..., JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT)`  

**Agent 2 — Sécurité OWASP :**  
- A07 RAS : `refreshAuthenticatedUser()` relit déjà le rôle depuis DB à chaque requête  
- A03 RAS : `htmlspecialchars()` sur toutes les sorties HTML, confirmé  
- A04 RAS : extraction explicite champ par champ depuis `$_POST`, pas de mass assignment  
- A01 MOYENNE corrigée : ajout protection dernier admin actif dans `desactiver()`  

**Agent 3 — Intégration (routes/redirections/variables) :**  
1 bug critique corrigé : bloc `} {` orphelin dans `store()` (résidu du multi-replace) → `existsByIdentifiant()` jamais appelé, `store` n'aboutissait jamais. Corrigé en séparant les deux `if` en blocs indépendants.

### Résultat

✅ **Sprint 4 validé** — CRUD utilisateurs Admin complet avec 4 bugs corrigés post-agents. Protection dernier admin, XSS JS, intégrité du flux store(). Prêt pour Sprint 5.

## Sprint 5 - Catalogue simple

### Objectif

Implémenter la gestion des catégories et des produits, avec validation serveur et upload d'images via `UploadService`.

### En termes simples

> Le CDC prévoit que le rôle Administration gère l'intégralité du catalogue produit. Ce sprint implémente la gestion des catégories (les regroupements qui organisent l'offre, comme "Burgers" ou "Boissons") et des produits (nom, description, prix, image, catégorie, disponibilité). Pour les produits, une image est obligatoire à la création — elle est stockée hors de la zone web accessible pour empêcher toute exécution directe depuis un navigateur. La disponibilité peut être modifiée indépendamment des autres informations, et un produit peut être désactivé sans être supprimé (exigence CDC : aucune suppression physique d'un élément déjà référencé dans une commande).

### Ce qui a été codé

- **CategorieController** — 6 méthodes CRUD Admin : `index`, `create`, `store`, `edit`, `update`, `desactiver`  
  - Validation : nom required maxLen 100, description optionnelle maxLen 500  
  - Unicité du nom : `existsByNom()` avec exclude sur update (contrainte UNIQUE en BDD)  
  - PRG + TraceService sur toutes les mutations  
- **ProduitController** — 6 méthodes CRUD Admin : `index`, `create`, `store`, `edit`, `update`, `desactiver`  
  - Validation : nom required maxLen 150, description required, prix positiveNumber  
  - Catégorie validée contre `findAllActive()` avec `array_map('intval', ...)`  
  - Image obligatoire à la création, optionnelle à l'édition  
  - `UploadService::stocker()` + `supprimer()` (ancienne image détruite après remplacement)  
  - PRG + TraceService sur toutes les mutations  
- **CategorieRepository** — ajout `existsByNom(string $nom, ?int $excludeId = null): bool`  
- **layout.php** — Catalogue (Produits + Catégories) restreint visuellement au rôle Administration

### Fichiers modifiés

| Fichier | Nature |
|---|---|
| `app/Controllers/CategorieController.php` | Stub TODO → 6 méthodes CRUD complètes |
| `app/Controllers/ProduitController.php` | Stub TODO → 6 méthodes CRUD complètes avec UploadService |
| `app/Repositories/CategorieRepository.php` | Ajout `existsByNom()` |
| `app/Views/layout.php` | Catalogue restreint `Administration` only |

### Fichiers ajoutés

| Fichier | Rôle |
|---|---|
| `app/Views/categories/index.php` | Liste catégories — table, badge, bouton désactiver CSRF |
| `app/Views/categories/create.php` | Formulaire création — nom + description optionnelle |
| `app/Views/categories/edit.php` | Formulaire édition — prefilled |
| `app/Views/produits/index.php` | Liste produits — table (nom/catégorie/prix/disponible/statut), désactiver CSRF |
| `app/Views/produits/create.php` | Formulaire création — enctype multipart, tous champs, upload obligatoire |
| `app/Views/produits/edit.php` | Formulaire édition — prix prefilled, checkbox disponible, upload optionnel |

### Vérification à 3 agents

**Agent 1 — Code review (logique PHP) :**  
1 bug détecté et corrigé :  
- `ProduitController::update()` — `$produit['image'] !== ''` insuffisant si image null → remplacé par `!empty()`

**Agent 2 — Sécurité OWASP :**  
- UploadService déjà sécurisé depuis Sprint 3 : validation `$sousDossier` par regex, garde `str_contains('..')` dans `supprimer()`, MIME via finfo, nom aléatoire `bin2hex(random_bytes(16))`  
- Storage hors DocumentRoot (`/var/www/html/storage/uploads/` vs DocumentRoot `/var/www/html/public/`) — non accessible HTTP  
- CSRF : RAS — hash_equals sur tous les POST  
- A01 : RAS — `requireRole(['Administration'])` systématique  

**Agent 3 — Intégration (routes/redirections/variables) :**  
RAS — form actions, redirections, variables de vue, enctype multipart, clés d'array tous cohérents.

### Résultat

✅ **Sprint 5 validé** — CRUD catégories et produits complets avec upload sécurisé. 1 bug corrigé post-agent. UploadService défenses confirmées. Prêt pour Sprint 6.

## Sprint 6 - Menus composés

### Objectif

Implémenter les menus, leurs sections et leurs options avec les règles de cohérence du CDC.

### En termes simples

> Le CDC demande que l'Administration gère non seulement les produits seuls, mais aussi les menus composés — des offres qui regroupent plusieurs choix structurés. Un menu est organisé en sections (ex : "Votre plat", "Votre accompagnement", "Votre boisson"), chaque section proposant une liste de produits parmi lesquels le client choisira au moment de la commande. Ce sprint implémente la création et l'édition des menus avec image, la gestion de leurs sections et des options disponibles dans chaque section. Comme pour les produits, la disponibilité d'un menu est pilotable indépendamment, et aucune suppression physique n'est autorisée.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.

## Sprint 7 - Noyau commandes

### Objectif

Créer `CommandeService`, source unique des règles de création, validation, calcul de total, lignes, choix de menu et transitions de statut.

### En termes simples

> Avant de créer des pages de saisie de commandes (sprint 8) ou de recevoir des commandes via l'API (sprint 9), les règles métier doivent être centralisées en un seul endroit. Ce sprint crée le `CommandeService` : c'est lui qui calcule le total d'une commande depuis le catalogue actif (les prix viennent toujours de la base de données, pas de ce que le client envoie), qui valide chaque ligne (le produit doit exister, être actif, être disponible), qui vérifie qu'un menu a bien toutes ses sections obligatoires remplies, et qui gère les transitions de statut : `à préparer` → `préparée` → `livrée`. Aucune interface utilisateur dans ce sprint — c'est le moteur métier sur lequel tout le reste s'appuie.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.

## Sprint 8 - Back-office commandes

### Objectif

Brancher les pages commandes sur le noyau métier : liste filtrée par rôle, création manuelle, détail, préparation et livraison.

### En termes simples

> Le CDC définit trois rôles opérationnels autour des commandes, chacun avec ses propres actions. Ce sprint branche les pages sur le `CommandeService` du sprint 7 : le rôle Accueil peut saisir manuellement une commande (sélection de produits, de menus avec leurs options, quantités) et déclarer une commande "livrée" lors de la remise au client via son numéro de retrait ; le rôle Préparation consulte la liste des commandes "à préparer" triées par heure de retrait croissante et les déclare "préparées" une fois confectionnées ; le rôle Administration peut réaliser l'ensemble de ces actions.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.

## Sprint 9 - API externe

### Objectif

Implémenter les endpoints `/api/catalogue` et `/api/commandes` avec clé `X-API-Key`, JSON strict et réutilisation des services métier.

### En termes simples

> Le CDC exige que le back-office expose une API REST permettant au système de commande externe (la borne ou l'application client) de récupérer le catalogue et de soumettre des commandes. Ce sprint implémente deux endpoints : `GET /api/catalogue` (retourne catégories, produits, menus et leurs options disponibles) et `POST /api/commandes` (reçoit une commande, la valide et la calcule via le `CommandeService` du sprint 7 — les prix envoyés par le client externe ne font pas foi). L'accès est contrôlé par une clé API via le header `X-API-Key`. Aucune donnée interne (comptes, sessions, traces) n'est exposée.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.

## Sprint 10 - UI globale + navigation

### Objectif

Finaliser le layout, le header, la sidebar filtrée par rôle, les messages flash, la pagination, les breadcrumbs, les états vides et les confirmations.

### En termes simples

> Chaque sprint précédent a ajouté ses pages et ses liens de façon indépendante. Ce sprint harmonise l'ensemble : la sidebar est filtrée par rôle (le rôle Préparation ne voit pas les liens du catalogue, le rôle Accueil ne voit pas les liens d'administration), les messages flash sont cohérents sur toutes les pages, les listes vides affichent un message clair, les actions de désactivation demandent une confirmation, et la navigation globale est lisible. L'application doit être utilisable et cohérente pour chaque profil d'utilisateur défini dans le CDC.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.

## Sprint 11 - Recette finale

### Objectif

Valider l'ensemble du projet contre le CDC technique : Docker, routes, rôles, CSRF, uploads, API, commandes, traces et conformité de code.

### En termes simples

> Avant livraison, une passe de validation complète contre le CDC et le CDC technique. Chaque rôle est testé avec ses actions autorisées et ses actions interdites. Le projet est démarré depuis zéro via Docker pour vérifier qu'il démarre sans intervention manuelle. Toutes les routes, formulaires, transitions de statut et endpoints API sont parcourus. La table `traces_actions` est vérifiée pour toutes les opérations sensibles. Tout écart avec le CDC est corrigé avant la présentation jury.

### Ce qui a été codé

À compléter après implémentation.

### Fichiers modifiés

À compléter après implémentation.

### Fichiers ajoutés

À compléter après implémentation.

### Vérification à 3 agents

À compléter après revue.

### Résultat

À compléter après validation.