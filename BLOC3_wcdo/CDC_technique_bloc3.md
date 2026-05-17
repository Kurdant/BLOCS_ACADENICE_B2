# CDC technique Bloc 3

## Sommaire

### 0 Analyse fonctionnelle rapide
### 1 MCD
### 2 Dictionnaire des données
### 3 MCT
### 4 Contexte technique
### 5 Choix techniques retenus
### 6 Architecture applicative
### 7 Persistance et base de données
### 8 Schéma BDD
### 9 Sécurité applicative
### 10 Environnement de développement
### 11 Déploiement
### 12 Conventions de développement

---

## 0 Analyse fonctionnelle rapide

L'application Wacdo est une application web interne réservée aux collaborateurs administrateurs authentifiés. Elle permet de gérer les collaborateurs, les restaurants, les fonctions et les affectations. Le cœur métier est l'affectation d'un collaborateur à une fonction dans un restaurant sur une période donnée. Le système doit distinguer les affectations en cours, conserver l'historique, identifier les collaborateurs non affectés et permettre les recherches filtrées demandées.

## 1 MCD

Le modèle conceptuel de données retient quatre entités principales : `Collaborateur`, `Restaurant`, `Fonction` et `Affectation`. L'entité `Affectation` est l'entité centrale du modèle : elle porte le lien métier entre une personne, un restaurant, un poste et une période.

### 1.1 Entités principales

| Entité | Rôle métier |
|---|---|
| **Collaborateur** | Personne employée par Wacdo, enregistrée dans l'application, pouvant disposer ou non du droit administrateur. |
| **Restaurant** | Établissement Wacdo dans lequel des collaborateurs peuvent être affectés. |
| **Fonction** | Poste de travail existant chez Wacdo, utilisé comme référentiel lors des affectations. |
| **Affectation** | Association datée entre un collaborateur, un restaurant et une fonction. |

### 1.2 Relations et cardinalités

| Relation | Cardinalité | Lecture métier |
|---|---|---|
| Collaborateur — Affectation | Collaborateur `0,n` ; Affectation `1,1` | Un collaborateur peut ne jamais être affecté ou posséder plusieurs affectations. Une affectation concerne obligatoirement un seul collaborateur. |
| Restaurant — Affectation | Restaurant `0,n` ; Affectation `1,1` | Un restaurant peut n'avoir aucune affectation ou accueillir plusieurs affectations. Une affectation concerne obligatoirement un seul restaurant. |
| Fonction — Affectation | Fonction `0,n` ; Affectation `1,1` | Une fonction peut ne jamais être utilisée ou être utilisée dans plusieurs affectations. Une affectation concerne obligatoirement une seule fonction. |

### 1.3 Diagramme textuel

```text
COLLABORATEUR (0,n) ─── concerne ─── (1,1) AFFECTATION
RESTAURANT    (0,n) ─── accueille ─── (1,1) AFFECTATION
FONCTION      (0,n) ─── qualifie  ─── (1,1) AFFECTATION
```

### 1.4 Règles de lecture du MCD

- Une affectation relie obligatoirement un collaborateur, un restaurant et une fonction.
- Une affectation possède une date de début obligatoire.
- Une affectation possède une date de fin facultative.
- Une affectation en cours est une affectation active à la date de consultation : `date_debut <= date du jour` et (`date_fin` vide ou `date_fin >= date du jour`).
- L'historique des affectations est obtenu à partir des affectations enregistrées.
- Un collaborateur non affecté est un collaborateur ne possédant aucune affectation en cours.
- Le droit d'accès à l'application est porté par le collaborateur via l'indicateur `administrateur`.
- Aucune entité séparée `Utilisateur`, `Rôle`, `Permission`, `HistoriqueAffectation`, `Planning`, `Contrat`, `Paie`, `Congé` ou `Commande` n'est retenue dans ce périmètre.

## 2 Dictionnaire des données

Le dictionnaire des données ci-dessous reprend les données nécessaires au MCD et au futur schéma de base de données. Les types indiqués sont des types techniques cibles, adaptés à une base SQL et à une implémentation avec ORM.

| Entité | Attribut | Type de donnée | Contraintes / Notes |
|---|---|---|---|
| Collaborateur | `id_collaborateur` | INT | Clé primaire, auto-incrémentée. |
| Collaborateur | `nom` | VARCHAR(100) | Obligatoire, non vide. Contrôlé avant enregistrement. |
| Collaborateur | `prenom` | VARCHAR(100) | Obligatoire, non vide. Contrôlé avant enregistrement. |
| Collaborateur | `email` | VARCHAR(180) | Obligatoire, format email valide, unique. Sert d'identifiant de connexion. |
| Collaborateur | `telephone` | VARCHAR(20) | Optionnel. Format téléphone contrôlé si renseigné. Retenu pour couvrir l'exigence de validation du référentiel. |
| Collaborateur | `date_premiere_embauche` | DATE | Obligatoire. Date valide. |
| Collaborateur | `administrateur` | BOOLEAN | Obligatoire, valeur par défaut `false`. `true` autorise l'accès applicatif. |
| Collaborateur | `mot_de_passe_hash` | VARCHAR(255) | Obligatoire si `administrateur = true`. Vide pour un collaborateur non administrateur. Le mot de passe n'est jamais stocké en clair. |
| Restaurant | `id_restaurant` | INT | Clé primaire, auto-incrémentée. |
| Restaurant | `nom` | VARCHAR(150) | Obligatoire, non vide. Utilisé pour la recherche. |
| Restaurant | `adresse` | VARCHAR(255) | Obligatoire, non vide. Contrôlée avant enregistrement. |
| Restaurant | `code_postal` | VARCHAR(10) | Obligatoire. Format code postal valide. Stocké en texte pour conserver les zéros initiaux. |
| Restaurant | `ville` | VARCHAR(100) | Obligatoire, non vide. Utilisée pour les filtres restaurant et affectation. |
| Fonction | `id_fonction` | INT | Clé primaire, auto-incrémentée. |
| Fonction | `intitule_poste` | VARCHAR(120) | Obligatoire, non vide, unique. Intitulé du poste existant chez Wacdo. |
| Affectation | `id_affectation` | INT | Clé primaire, auto-incrémentée. |
| Affectation | `id_collaborateur` | INT | Clé étrangère obligatoire vers `Collaborateur.id_collaborateur`. |
| Affectation | `id_restaurant` | INT | Clé étrangère obligatoire vers `Restaurant.id_restaurant`. |
| Affectation | `id_fonction` | INT | Clé étrangère obligatoire vers `Fonction.id_fonction`. |
| Affectation | `date_debut` | DATE | Obligatoire. Date de début de l'affectation. |
| Affectation | `date_fin` | DATE | Facultative. Vide si aucune date de fin n'est connue. Si renseignée, elle doit être supérieure ou égale à `date_debut`. |

### 2.1 Données déduites non stockées

| Donnée | Définition | Mode de détermination |
|---|---|---|
| `affectation_en_cours` | Affectation active à la date de consultation | `date_debut <= date du jour` et (`date_fin` vide ou `date_fin >= date du jour`) |
| `affectation_future` | Affectation déjà planifiée mais pas encore active | `date_debut > date du jour` |
| `affectation_terminee` | Affectation appartenant à l'historique | `date_fin` renseignée et `date_fin < date du jour` |
| `collaborateur_non_affecte` | Collaborateur sans poste actif à la date de consultation | Collaborateur sans affectation en cours |
| `historique_affectations_collaborateur` | Historique d'un collaborateur | Ensemble des affectations liées au collaborateur |
| `historique_affectations_restaurant` | Historique d'un restaurant | Ensemble des affectations liées au restaurant |
| `collaborateurs_en_poste` | Collaborateurs actuellement présents dans un restaurant | Affectations en cours du restaurant à la date de consultation |

### 2.2 Contraintes principales à porter dans le schéma BD

- `email` doit être unique pour identifier sans ambiguïté un collaborateur administrateur lors de la connexion.
- `intitule_poste` doit être unique pour éviter les doublons dans le référentiel des fonctions.
- Une affectation doit toujours référencer un collaborateur, un restaurant et une fonction existants.
- `date_fin` doit être vide ou supérieure ou égale à `date_debut`.
- Un doublon strict d'affectation est interdit : même collaborateur, même restaurant, même fonction, même date de début et même date de fin.
- Le modèle n'interdit pas plusieurs affectations en cours pour un même collaborateur, car le besoin parle de la ou les affectations en cours.

## 3 MCT

Le MCT décrit les traitements métier déclenchés par les actions de l'administrateur dans l'application. Il reste centré sur les opérations prévues par le référentiel : authentification, gestion des restaurants, gestion des collaborateurs, gestion des fonctions et recherche des affectations.

### 3.1 Règle transversale sur les affectations actives

Le calcul d'une affectation en cours repose sur la date de consultation. Une affectation est active si les deux conditions suivantes sont respectées :

```text
date_debut <= date du jour
ET
(date_fin est vide OU date_fin >= date du jour)
```

Conséquences métier :

- une affectation future ne rend pas le collaborateur en poste aujourd'hui ;
- une affectation passée reste consultable dans l'historique ;
- un collaborateur peut être enregistré sans affectation active ;
- un collaborateur avec uniquement des affectations passées ou futures est non affecté actuellement.

### 3.2 Événements déclencheurs

| ID | Événement déclencheur |
|---|---|
| E-01 | Soumission du formulaire de connexion |
| E-02 | Demande d'accès à une fonctionnalité protégée |
| E-03 | Demande de consultation ou de recherche des restaurants |
| E-04 | Sélection d'un restaurant |
| E-05 | Validation d'une création ou modification de restaurant |
| E-06 | Validation d'une affectation depuis la fiche restaurant |
| E-07 | Demande de consultation ou de recherche des collaborateurs |
| E-08 | Demande de recherche des collaborateurs non affectés |
| E-09 | Sélection d'un collaborateur |
| E-10 | Validation d'une création ou modification de collaborateur |
| E-11 | Validation d'une affectation depuis la fiche collaborateur |
| E-12 | Validation d'une modification d'affectation en cours |
| E-13 | Demande de consultation des fonctions |
| E-14 | Validation d'une création ou modification de fonction |
| E-15 | Demande de recherche des affectations |
| E-16 | Demande de déconnexion |

### 3.3 Opérations conceptuelles

| Événement | Opération | Règles / conditions | Résultat |
|---|---|---|---|
| E-01 — Soumission du formulaire de connexion | Authentifier un administrateur | Le collaborateur existe, possède `administrateur = true`, dispose d'un mot de passe et les identifiants sont valides. | Session ouverte et accès au menu principal. En cas d'échec, accès refusé. |
| E-02 — Demande d'accès à une fonctionnalité protégée | Contrôler l'autorisation | L'utilisateur doit être authentifié et administrateur. | Accès autorisé à l'écran demandé ou refus sans traitement métier. |
| E-03 — Demande de consultation ou de recherche des restaurants | Rechercher les restaurants | Les critères utilisables sont le nom, le code postal et la ville. Les critères vides sont ignorés. | Liste filtrée des restaurants. |
| E-05 — Validation d'une création ou modification de restaurant | Enregistrer un restaurant | Nom, adresse, code postal et ville obligatoires. Données non vides et formats valides. | Restaurant créé ou modifié. Si les données sont invalides, enregistrement refusé. |
| E-04 — Sélection d'un restaurant | Afficher la fiche restaurant | Le restaurant existe. Les collaborateurs en poste sont calculés à partir des affectations actives à la date du jour. | Fiche restaurant affichée avec informations, collaborateurs en poste et historique des affectations. |
| E-06 — Validation d'une affectation depuis la fiche restaurant | Créer une affectation | Collaborateur, restaurant, fonction et date de début obligatoires. Date de fin vide ou supérieure ou égale à la date de début. Aucun doublon strict. | Affectation enregistrée. Son statut d'affichage est déduit de ses dates. |
| E-07 — Demande de consultation ou de recherche des collaborateurs | Rechercher les collaborateurs | Les critères utilisables sont le nom, le prénom et l'email. Les critères vides sont ignorés. | Liste filtrée des collaborateurs. |
| E-10 — Validation d'une création ou modification de collaborateur | Enregistrer un collaborateur | Nom, prénom, email, date de première embauche et indicateur administrateur obligatoires. Email valide et unique. Mot de passe obligatoire si administrateur. Téléphone contrôlé si présent. | Collaborateur créé ou modifié. Une affectation n'est pas obligatoire pour enregistrer le collaborateur. |
| E-08 — Demande de recherche des collaborateurs non affectés | Identifier les collaborateurs non affectés actuellement | Un collaborateur est non affecté s'il ne possède aucune affectation active à la date du jour. | Liste des collaborateurs enregistrés sans poste actif aujourd'hui. |
| E-09 — Sélection d'un collaborateur | Afficher la fiche collaborateur | Le collaborateur existe. Les affectations actives sont calculées à la date du jour. L'historique contient toutes ses affectations. | Fiche collaborateur affichée avec informations, affectations en cours, affectations futures ou passées et historique. |
| E-11 — Validation d'une affectation depuis la fiche collaborateur | Créer une affectation | Collaborateur, restaurant, fonction et date de début obligatoires. Date de fin vide ou supérieure ou égale à la date de début. Aucun doublon strict. | Affectation enregistrée. Son statut d'affichage est déduit de ses dates. |
| E-12 — Validation d'une modification d'affectation en cours | Modifier une affectation active | L'affectation existe et est active à la date du jour. Les nouvelles données restent cohérentes. | Affectation mise à jour. Si sa période ne couvre plus la date du jour, elle sort des affectations en cours et reste dans l'historique. |
| E-13 — Demande de consultation des fonctions | Consulter les fonctions | L'utilisateur est authentifié administrateur. | Liste des fonctions affichée. |
| E-14 — Validation d'une création ou modification de fonction | Enregistrer une fonction | L'intitulé du poste est obligatoire, non vide et unique. | Fonction créée ou modifiée. Si l'intitulé est invalide ou déjà existant, enregistrement refusé. |
| E-15 — Demande de recherche des affectations | Rechercher les affectations | Les critères utilisables sont la fonction, la date de début, la date de fin et la ville. La recherche porte sur toutes les affectations. | Liste filtrée des affectations, qu'elles soient passées, actives ou futures. |
| E-16 — Demande de déconnexion | Fermer la session | Une session utilisateur est ouverte. | Session fermée. Les pages protégées redeviennent inaccessibles sans authentification. |

### 3.4 Diagramme textuel du MCT

```text
[Connexion]
	-> Authentifier un administrateur
	-> Session ouverte / Accès refusé

[Accès fonctionnalité protégée]
	-> Contrôler l'autorisation
	-> Écran ouvert / Accès refusé

[Recherche restaurants]
	-> Rechercher les restaurants
	-> Liste filtrée

[Sélection restaurant]
	-> Afficher la fiche restaurant
	-> Détail + collaborateurs en poste aujourd'hui + historique

[Validation restaurant]
	-> Enregistrer un restaurant
	-> Restaurant créé ou modifié

[Recherche collaborateurs]
	-> Rechercher les collaborateurs
	-> Liste filtrée

[Recherche collaborateurs non affectés]
	-> Identifier les collaborateurs sans affectation active aujourd'hui
	-> Liste des collaborateurs non affectés actuellement

[Sélection collaborateur]
	-> Afficher la fiche collaborateur
	-> Détail + affectations actives aujourd'hui + historique

[Validation collaborateur]
	-> Enregistrer un collaborateur
	-> Collaborateur créé ou modifié

[Validation affectation]
	-> Créer une affectation
	-> Affectation enregistrée

[Modification affectation en cours]
	-> Modifier une affectation active
	-> Affectation mise à jour

[Consultation fonctions]
	-> Consulter les fonctions
	-> Liste des fonctions

[Validation fonction]
	-> Enregistrer une fonction
	-> Fonction créée ou modifiée

[Recherche affectations]
	-> Rechercher les affectations
	-> Liste filtrée des affectations

[Déconnexion]
	-> Fermer la session
	-> Session fermée
```

### 3.5 Traitements exclus du MCT

- Aucun traitement de paie, contrat, congé, absence, planning horaire ou recrutement n'est modélisé.
- Aucun traitement de commande, caisse, stock ou production restaurant n'est modélisé.
- Aucun traitement de rôle avancé ou de permission détaillée n'est modélisé.
- Aucun traitement d'historique séparé n'est modélisé : l'historique est obtenu à partir des affectations enregistrées.
- Aucun statut d'affectation n'est stocké : les états active, future et terminée sont déduits des dates.

## 4 Contexte technique

Le projet Wacdo Bloc 3 est réalisé sous la forme d'une application web interne de gestion. Elle est destinée exclusivement aux collaborateurs disposant du droit administrateur et doit être développée avec un framework back, conformément au cadrage du sujet. L'application couvre quatre domaines métier : les collaborateurs, les restaurants, les fonctions et les affectations.

Le contexte technique impose une architecture serveur structurée, orientée objets, capable de produire des écrans HTML pour le back-office et de s'appuyer sur une base de données relationnelle SQL. Le projet doit donc articuler proprement les couches suivantes : présentation, logique métier, accès aux données et sécurité applicative.

### 4.1 Nature de l'application

- L'application est un back-office web accessible après authentification.
- L'interface principale est rendue côté serveur pour répondre rapidement au besoin métier et limiter la complexité du projet.
- Le cœur fonctionnel repose sur la gestion datée des affectations, avec calcul d'états dérivés à la consultation.
- Le projet n'a pas de besoin API public, mobile, temps réel ou microservices dans ce périmètre.

### 4.2 Contraintes imposées par le sujet

- Le développement doit être réalisé avec un framework back.
- Le projet doit utiliser un langage serveur adapté à ce framework.
- Le rendu des vues doit passer par un moteur de templates.
- Les données doivent être persistées dans une base SQL.
- La gestion du modèle de données doit s'appuyer sur un ORM.
- L'application doit intégrer authentification et autorisation.
- Le livrable final doit être déployable et démontrable devant jury.

### 4.3 Enjeux techniques du projet

Les enjeux techniques sont simples mais structurants.

- Garantir un contrôle d'accès strict : aucune fonctionnalité métier ne doit être accessible sans session valide et sans droit administrateur.
- Assurer l'intégrité du modèle de données : une affectation référence toujours un collaborateur, un restaurant et une fonction existants.
- Gérer correctement la dimension temporelle des affectations : l'état actif, futur ou terminé est calculé à partir des dates et non stocké.
- Préserver une base lisible et défendable : quatre entités métier principales, sans sur-modélisation RH ni système de rôles complexe.
- Produire un code maintenable : séparation claire entre contrôleurs, services métier, entités et accès aux données.
- Faciliter les évolutions demandées par le jury : l'application doit supporter une modification ponctuelle sans remise à plat de l'architecture.

### 4.4 Contraintes d'exploitation et de qualité

- L'application est utilisée dans un contexte interne ; elle ne nécessite pas de montée en charge complexe ni de distribution multi-sites sophistiquée.
- La priorité porte sur la fiabilité fonctionnelle, la clarté du code, la sécurité des accès et la cohérence des données.
- Les formulaires doivent contrôler les données attendues par le référentiel avant toute persistance.
- Les mots de passe doivent être protégés par hachage ; ils ne sont jamais stockés en clair.
- Les erreurs métier doivent être bloquantes et compréhensibles pour l'utilisateur administrateur.

### 4.5 Frontières techniques retenues

- Le projet couvre uniquement le back-office de gestion des affectations.
- Il n'inclut ni paie, ni planning, ni gestion des absences, ni recrutement, ni gestion opérationnelle des restaurants.
- Il ne prévoit pas de synchronisation avec un SI externe dans ce périmètre.
- Il ne prévoit pas de mécanisme de workflow complexe, de moteur de règles ou de traitement batch nécessaire au métier.

### 4.6 Exigences de réalisation

- L'environnement de développement doit permettre une installation propre du framework et de ses dépendances.
- Le projet doit pouvoir être exécuté localement de manière reproductible.
- La structure technique choisie ensuite devra rester simple à expliquer : framework, routing, contrôleurs, vues, ORM, sécurité, base de données et déploiement.
- Les tests attendus par le sujet concernent au minimum les parcours fonctionnels critiques, les validations de formulaires et les contrôles d'accès.

## 5 Choix techniques retenus

Les choix techniques retenus sont volontairement simples, cohérents entre eux et directement défendables devant jury. L'objectif n'est pas de multiplier les outils, mais de s'appuyer sur une stack back standard, moderne et maintenable.

### 5.1 Framework back

Le framework retenu est **Laravel**.

Ce choix est retenu pour quatre raisons :

- Laravel répond exactement au cadrage du sujet Bloc 3 orienté framework back.
- Il fournit nativement le routage, les contrôleurs, le moteur de templates, l'ORM, la validation, les middlewares et la gestion de session.
- Il permet de produire rapidement un back-office propre sans surcouche front inutile.
- Il reste simple à expliquer à l'oral : cycle requête, route, contrôleur, vue, modèle, validation, sécurité.

### 5.2 Langage serveur et dépendances

- Langage serveur retenu : **PHP 8.3**.
- Gestionnaire de dépendances retenu : **Composer**.

PHP 8.3 est retenu pour disposer d'une version récente, stable et performante, compatible avec Laravel et adaptée à un projet d'examen traité comme un vrai projet. Composer est retenu car il est l'outil standard de l'écosystème PHP pour installer Laravel et gérer proprement les dépendances applicatives.

### 5.3 Moteur de templates

Le moteur de templates retenu est **Blade**.

Blade est le meilleur choix dans ce contexte car il est natif dans Laravel, léger, lisible et parfaitement adapté à un back-office rendu côté serveur. Il évite d'ajouter Twig dans un projet Laravel, ce qui créerait une complexité inutile sans gain métier.

### 5.4 ORM et accès aux données

L'ORM retenu est **Eloquent ORM**.

Eloquent est retenu car il est natif dans Laravel, bien intégré aux modèles, aux relations et aux requêtes courantes. Il permet de manipuler proprement les entités `Collaborateur`, `Restaurant`, `Fonction` et `Affectation` sans écrire une couche d'abstraction supplémentaire.

Pour les cas de recherche plus spécifiques, notamment la recherche multi-critères sur les affectations, l'application pourra s'appuyer sur le Query Builder Laravel lorsque cela est plus lisible qu'une succession de relations Eloquent.

### 5.5 Migrations et structure de base

Le projet utilise **les migrations Laravel**.

Ce choix n'est pas accessoire. Il permet de versionner la structure de base de données, de reconstruire l'environnement local ou serveur de manière reproductible et de démontrer une démarche propre de gestion du schéma. Dans un projet conteneurisé et déployé sur VPS, ignorer les migrations compliquerait inutilement l'installation et la maintenance.

### 5.6 Base de données

Le SGBD retenu est **PostgreSQL**.

PostgreSQL est choisi pour sa robustesse, sa fiabilité transactionnelle, sa bonne gestion des contraintes et sa très bonne compatibilité avec Laravel. Il convient parfaitement à un modèle relationnel centré sur les affectations, les clés étrangères, les recherches filtrées et l'intégrité des données.

### 5.7 Serveur web et exécution applicative

Le serveur HTTP retenu est **Nginx**, avec exécution PHP via **PHP-FPM**.

Ce choix est retenu pour garder une architecture web standard, claire et proche d'un contexte de production réel. Il est plus cohérent de travailler dès le départ avec `Nginx + PHP-FPM` que de baser le projet sur le serveur de développement intégré si la cible finale est un déploiement Docker sur VPS.

### 5.8 Rendu front

Le rendu front repose sur :

- **Blade** pour les vues HTML ;
- **CSS maison** pour la mise en forme ;
- **JavaScript natif minimal** uniquement si un besoin d'interaction légère apparaît.

Le projet ne retient ni SPA, ni framework front, ni bibliothèque CSS lourde. Cette décision est volontaire : le besoin porte sur un back-office de gestion, pas sur une interface applicative complexe côté client. Un rendu serveur avec CSS classique est le meilleur compromis entre lisibilité, rapidité de développement et soutenabilité devant jury.

### 5.9 Authentification et autorisation

Le projet retient une **authentification par formulaire et session serveur Laravel**.

L'autorisation repose sur une règle simple : seul un collaborateur dont l'attribut `administrateur` vaut `true` peut accéder à l'application. Cette vérification est centralisée dans les middlewares et dans les contrôleurs protégés. Aucun système de rôles avancé ni table de permissions n'est retenu.

### 5.10 Conteneurisation et déploiement

Le déploiement cible repose sur :

- un **VPS Linux** ;
- une exécution via **Docker** ;
- une orchestration locale et serveur avec **Docker Compose**.

Ce choix permet d'aligner le développement local et la production sur une même logique d'exécution. Il simplifie l'installation, la reproductibilité de l'environnement, la configuration de PHP, Nginx et PostgreSQL, ainsi que la démonstration du déploiement.

### 5.11 Stack technique retenue


Symfony = plus explicite et plus composable.
Laravel = plus guidé et plus rapide à mettre en œuvre. blade intégré dedans, pas besoin d'intégrer un autre moteur de template
Blade = intégrer dans laravel 

La stack retenue est donc la suivante :

| Domaine | Choix retenu |
|---|---|
| Framework back | Laravel |
| Langage serveur | PHP 8.3 |
| Gestionnaire de dépendances | Composer |
| Moteur de templates | Blade |
| ORM | Eloquent ORM |
| Migrations | Laravel Migrations |
| Base de données | PostgreSQL |
| Serveur web | Nginx |
| Exécution PHP | PHP-FPM |
| Rendu front | Blade + CSS maison + JavaScript natif minimal |
| Authentification | Formulaire + session serveur Laravel |
| Autorisation | Contrôle sur l'attribut `administrateur` |
| Conteneurisation | Docker |
| Orchestration | Docker Compose |
| Hébergement cible | VPS Linux |

### 5.12 Choix volontairement non retenus

Les technologies suivantes ne sont pas retenues, car elles surdimensionnent le projet par rapport au besoin réel :

- aucun framework front de type React, Vue ou Angular ;
- aucun moteur de templates externe à Laravel ;
- aucun ORM externe à Laravel ;
- aucune base MySQL retenue pour ce projet ;
- aucun système de rôles complexe ;
- aucune architecture microservices ;
- aucun orchestrateur complexe de type Kubernetes.

## 6 Architecture applicative

L'application Wacdo Bloc 3 est réalisée sous la forme d'un monolithe web Laravel. Toute la logique de gestion est portée par une seule application, avec rendu serveur via Blade et persistance relationnelle via Eloquent sur PostgreSQL. L'architecture retenue reste MVC, mais elle s'appuie exclusivement sur les briques natives du framework. Aucune couche technique redondante n'est ajoutée.

### 6.1 Principe général

Le cycle standard d'une requête suit l'enchaînement suivant :

1. l'utilisateur envoie une requête HTTP vers une route déclarée dans `routes/web.php` ;
2. Laravel applique les middlewares de session, d'authentification et d'autorisation ;
3. le contrôleur concerné reçoit la requête ;
4. un Form Request valide les données d'entrée lorsqu'il s'agit d'une création ou d'une modification ;
5. le contrôleur exécute directement le CRUD simple ou délègue au service métier lorsqu'une règle transverse doit être appliquée ;
6. les modèles Eloquent lisent ou écrivent les données dans PostgreSQL ;
7. le contrôleur renvoie soit une vue Blade, soit une redirection avec message flash.

Cette séquence est la référence de l'application. Elle implique les décisions suivantes :

- aucun routeur maison ;
- aucune couche repository dédiée ;
- aucune API REST séparée ;
- aucun framework front de type SPA ;
- aucun stockage d'un statut d'affectation en base.

L'état d'une affectation est calculé à la consultation à partir des dates. Une affectation est en cours lorsque `date_debut <= date du jour` et que `date_fin` est vide ou `>= date du jour`. Un collaborateur est non affecté lorsqu'il ne possède aucune affectation active à la date de consultation.

### 6.2 Flux type d'une requête

Le flux de création d'une affectation illustre l'architecture retenue :

1. l'administrateur ouvre la fiche d'un collaborateur ou d'un restaurant et déclenche l'action d'affectation ;
2. l'application affiche le formulaire standard de création d'affectation, éventuellement prérempli selon le contexte ;
3. la soumission du formulaire cible la route `POST /affectations` ;
4. les middlewares vérifient qu'une session authentifiée existe et que le collaborateur connecté possède `administrateur = true` ;
5. `StoreAffectationRequest` contrôle la présence du collaborateur, du restaurant, de la fonction, de la date de début, la validité des identifiants référencés et la cohérence chronologique des dates ;
6. `AffectationController` transmet les données validées à `AffectationService` ;
7. `AffectationService` applique les règles métier d'affectation : construction cohérente de la période, refus d'un doublon strictement identique et préparation de l'enregistrement ;
8. le modèle `Affectation` enregistre la ligne en base via Eloquent ;
9. le contrôleur redirige vers la fiche d'origine ou vers la liste des affectations avec un message de confirmation ;
10. la vue Blade recalcule ensuite l'affichage `en cours`, `terminée` ou `non affecté` à partir des dates disponibles.

Le flux d'une création ou modification simple de restaurant, de fonction ou de collaborateur est plus court : route, middleware, Form Request, contrôleur, modèle Eloquent, redirection. Aucun service spécifique n'est introduit pour ces cas, car Laravel couvre déjà toute la mécanique nécessaire.

### 6.3 Répartition des responsabilités

La répartition des responsabilités est figée comme suit. Pour chaque couche, il faut distinguer ce que Laravel prend déjà en charge et ce que le projet doit réellement coder.

| Couche | Géré par Laravel | À coder dans le projet |
|---|---|---|
| Routage | moteur de routage, résolution des verbes HTTP, paramètres dynamiques, groupement de middlewares, redirections | déclaration des routes du back-office dans `routes/web.php`, nommage des routes, choix des URLs et rattachement aux contrôleurs |
| Session et authentification | démarrage de session, persistance de l'utilisateur connecté, protection CSRF, helpers d'authentification, hachage des mots de passe | formulaire de connexion, logique de tentative de connexion sur `Collaborateur`, règle d'accès réservée aux administrateurs, déconnexion |
| Middlewares | pipeline d'exécution avant contrôleur, enchaînement des contrôles, refus d'accès et redirection | middleware vérifiant `administrateur = true` et rattachement des routes protégées à ce middleware |
| Contrôleurs | injection de la requête, injection des dépendances, aide au rendu des vues et aux redirections | actions `index`, `create`, `store`, `show`, `edit`, `update`, orchestration des filtres, chargement des données de fiche, choix de la vue de retour |
| Validation | cycle de validation des Form Requests, redirection automatique en cas d'erreur, stockage des messages d'erreur en session | règles métier et techniques des formulaires : champs obligatoires, unicité de l'email, cohérence des dates, existence des identifiants référencés |
| ORM et accès aux données | Eloquent, Query Builder, génération SQL courante, hydratation des objets, gestion des relations, pagination, timestamps | définition des modèles `Collaborateur`, `Restaurant`, `Fonction`, `Affectation`, relations métier, attributs autorisés, filtres de recherche, requêtes spécifiques pour détails, historiques et non affectés |
| Logique métier | aucune logique métier Wacdo n'est fournie par le framework | `AffectationService`, calcul et contrôle des règles d'affectation, refus des doublons stricts, centralisation des traitements métiers transverses |
| Vues | moteur Blade, héritage de layout, composants, échappement HTML par défaut, réaffichage des anciennes valeurs de formulaire | gabarit du back-office, écrans de liste, formulaires, fiches détail, tableaux d'historique, messages métier affichés à l'utilisateur |
| Base de données | moteur de migrations, exécution ordonnée des migrations, rollback, seeders, intégration PostgreSQL via configuration Laravel | écriture des migrations du schéma Wacdo, clés étrangères, contraintes d'unicité, index, seeders de données initiales |
| Réponse HTTP | gestion des réponses HTML, redirections, messages flash, codes de retour usuels | choix de la réponse adaptée à chaque cas métier : retour formulaire, redirection après succès, refus d'accès, message d'erreur compréhensible |

La conséquence directe est la suivante : Laravel fournit l'infrastructure technique générique, tandis que le projet code uniquement la logique propre à Wacdo.

Concrètement, cela signifie :

- Laravel gère déjà le routeur, la session, le cycle requête-réponse, la validation technique, le moteur de vues et l'accès standard à la base ;
- le projet doit coder les écrans, les règles métier, les critères de recherche, les relations entre entités et les contrôles spécifiques sur les affectations ;
- les CRUD simples de restaurants, fonctions et collaborateurs restent majoritairement dans les contrôleurs, les Form Requests et les modèles ;
- seule la gestion des affectations justifie une couche de service dédiée, car elle concentre plusieurs règles métier transverses.

Cette organisation évite deux erreurs classiques :

- déplacer artificiellement tout le CRUD simple dans des services sans valeur ajoutée ;
- reconstruire en code maison des briques déjà gérées par Laravel.

### 6.4 Organisation des dossiers

L'organisation cible du projet est la suivante :

```text
app/
	Http/
		Controllers/
			AuthController.php
			DashboardController.php
			RestaurantController.php
			FonctionController.php
			CollaborateurController.php
			AffectationController.php
		Middleware/
			EnsureUserIsAdmin.php
		Requests/
			LoginRequest.php
			StoreRestaurantRequest.php
			UpdateRestaurantRequest.php
			StoreFonctionRequest.php
			UpdateFonctionRequest.php
			StoreCollaborateurRequest.php
			UpdateCollaborateurRequest.php
			StoreAffectationRequest.php
			UpdateAffectationRequest.php
	Models/
		Restaurant.php
		Fonction.php
		Collaborateur.php
		Affectation.php
	Services/
		AffectationService.php
bootstrap/
config/
database/
	migrations/
	seeders/
public/
	index.php
	css/
		app.css
	js/
		app.js
resources/
	views/
		layouts/
		auth/
		dashboard/
		restaurants/
		fonctions/
		collaborateurs/
		affectations/
		components/
routes/
	web.php
storage/
tests/
```

Cette arborescence reprend les conventions Laravel sans les détourner. Le projet ne crée ni dossier `Repositories`, ni routeur custom, ni moteur de templates additionnel.

### 6.5 Détail des vues et des assets front

Le rendu HTML repose exclusivement sur Blade. La structure des vues est figée comme suit :

```text
resources/views/
	layouts/
		app.blade.php
	auth/
		login.blade.php
	dashboard/
		index.blade.php
	restaurants/
		index.blade.php
		create.blade.php
		show.blade.php
		edit.blade.php
	fonctions/
		index.blade.php
		create.blade.php
		edit.blade.php
	collaborateurs/
		index.blade.php
		create.blade.php
		show.blade.php
		edit.blade.php
	affectations/
		index.blade.php
		create.blade.php
		edit.blade.php
	components/
		flash.blade.php
		filters.blade.php
		pagination.blade.php
```

Les principes front sont les suivants :

- `layouts/app.blade.php` constitue le gabarit commun de toutes les pages authentifiées ;
- `auth/login.blade.php` est la seule vue publique ;
- `dashboard/index.blade.php` constitue une page d'orientation après connexion, avec des boutons d'accès direct vers les modules principaux ;
- les vues de liste intègrent systématiquement les formulaires de recherche et de filtrage ;
- les vues `restaurants/show.blade.php` et `collaborateurs/show.blade.php` affichent les données principales, les affectations en cours et l'historique ;
- la création d'une affectation utilise un formulaire unique `affectations/create.blade.php`, accessible depuis la fiche d'un restaurant ou d'un collaborateur, avec préremplissage éventuel par paramètre ;
- les composants partagés regroupent les messages flash, les zones de filtres et la pagination.

Les assets front sont volontairement limités à :

- `public/css/app.css` pour la mise en forme globale ;
- `public/js/app.js` pour les interactions légères strictement nécessaires ;
- aucun framework JavaScript ;
- aucun composant front externe lourd.

### 6.6 Pages de l'interface graphique

L'interface graphique retenue comporte les pages suivantes :

| Domaine | Page | Finalité |
|---|---|---|
| Authentification | Connexion | Permettre l'accès sécurisé à l'application |
| Tableau de bord | Accueil back-office | Fournir une page d'orientation avec des boutons d'accès direct vers les modules principaux |
| Restaurants | Liste des restaurants | Rechercher et filtrer par nom, code postal et ville |
| Restaurants | Création d'un restaurant | Enregistrer un nouveau restaurant |
| Restaurants | Détail d'un restaurant | Consulter ses informations, les affectations en cours et l'historique |
| Restaurants | Modification d'un restaurant | Mettre à jour les données du restaurant |
| Fonctions | Liste des fonctions | Consulter le référentiel des postes |
| Fonctions | Création d'une fonction | Ajouter une fonction au référentiel |
| Fonctions | Modification d'une fonction | Mettre à jour une fonction existante |
| Collaborateurs | Liste des collaborateurs | Rechercher et filtrer par nom, prénom et email |
| Collaborateurs | Vue `non affectés` | Afficher uniquement les collaborateurs sans affectation active |
| Collaborateurs | Création d'un collaborateur | Enregistrer un nouveau collaborateur |
| Collaborateurs | Détail d'un collaborateur | Consulter ses données, ses affectations en cours et son historique |
| Collaborateurs | Modification d'un collaborateur | Mettre à jour ses informations |
| Affectations | Liste transversale des affectations | Rechercher et filtrer par fonction, dates et ville |
| Affectations | Création d'une affectation | Créer une nouvelle affectation depuis une fiche ou depuis la recherche |
| Affectations | Modification d'une affectation en cours | Mettre à jour une affectation encore active |

Le tableau de bord n'est pas conçu comme un écran d'indicateurs complexes. Il sert de point d'entrée pratique après authentification et propose des boutons de navigation vers les écrans principaux : restaurants, collaborateurs, fonctions et affectations.

La vue `non affectés` n'introduit pas un écran technique distinct. Elle correspond au même écran de liste avec un filtre métier activé.

### 6.7 Routes back-office figées

Les routes back-office sont figées ci-dessous. Sauf mention contraire, elles sont protégées par les middlewares d'authentification et d'autorisation administrateur.

| Méthode | Route | Contrôleur | Finalité |
|---|---|---|---|
| GET | `/login` | `AuthController@create` | Afficher le formulaire de connexion |
| POST | `/login` | `AuthController@store` | Authentifier l'utilisateur |
| POST | `/logout` | `AuthController@destroy` | Déconnecter l'utilisateur |
| GET | `/` | `DashboardController@index` | Afficher le tableau de bord de navigation |
| GET | `/restaurants` | `RestaurantController@index` | Lister et filtrer les restaurants |
| GET | `/restaurants/creer` | `RestaurantController@create` | Afficher le formulaire de création |
| POST | `/restaurants` | `RestaurantController@store` | Enregistrer un restaurant |
| GET | `/restaurants/{restaurant}` | `RestaurantController@show` | Afficher la fiche restaurant |
| GET | `/restaurants/{restaurant}/modifier` | `RestaurantController@edit` | Afficher le formulaire de modification |
| PUT | `/restaurants/{restaurant}` | `RestaurantController@update` | Mettre à jour un restaurant |
| GET | `/fonctions` | `FonctionController@index` | Lister les fonctions |
| GET | `/fonctions/creer` | `FonctionController@create` | Afficher le formulaire de création |
| POST | `/fonctions` | `FonctionController@store` | Enregistrer une fonction |
| GET | `/fonctions/{fonction}/modifier` | `FonctionController@edit` | Afficher le formulaire de modification |
| PUT | `/fonctions/{fonction}` | `FonctionController@update` | Mettre à jour une fonction |
| GET | `/collaborateurs` | `CollaborateurController@index` | Lister, rechercher et filtrer les collaborateurs |
| GET | `/collaborateurs/creer` | `CollaborateurController@create` | Afficher le formulaire de création |
| POST | `/collaborateurs` | `CollaborateurController@store` | Enregistrer un collaborateur |
| GET | `/collaborateurs/{collaborateur}` | `CollaborateurController@show` | Afficher la fiche collaborateur |
| GET | `/collaborateurs/{collaborateur}/modifier` | `CollaborateurController@edit` | Afficher le formulaire de modification |
| PUT | `/collaborateurs/{collaborateur}` | `CollaborateurController@update` | Mettre à jour un collaborateur |
| GET | `/affectations` | `AffectationController@index` | Lister et filtrer les affectations |
| GET | `/affectations/creer` | `AffectationController@create` | Afficher le formulaire de création |
| POST | `/affectations` | `AffectationController@store` | Enregistrer une affectation |
| GET | `/affectations/{affectation}/modifier` | `AffectationController@edit` | Afficher le formulaire de modification |
| PUT | `/affectations/{affectation}` | `AffectationController@update` | Mettre à jour une affectation en cours |

Les filtres métier sont portés par les paramètres de requête sur les routes de liste :

- `/restaurants?nom=&code_postal=&ville=`
- `/collaborateurs?nom=&prenom=&email=`
- `/collaborateurs?non_affectes=1`
- `/affectations?fonction=&date_debut=&date_fin=&ville=`

### 6.8 Contrôle d'accès

Le contrôle d'accès est volontairement simple et strict.

- la route de connexion est publique ;
- toutes les autres routes nécessitent une session authentifiée ;
- toutes les fonctionnalités de gestion nécessitent `administrateur = true` sur le collaborateur connecté ;
- un utilisateur authentifié mais non administrateur reçoit un refus d'accès côté serveur ;
- aucune autorisation n'est déléguée au front ;
- aucune table de rôles ni table de permissions n'est introduite.

Le contrôle d'accès est donc assuré à deux niveaux complémentaires :

- authentification par formulaire et session Laravel ;
- autorisation métier par middleware dédié fondé sur l'attribut `administrateur`.

Cette approche répond exactement au besoin métier du projet sans complexité artificielle.

## 7 Persistance et base de données

La persistance du projet repose sur PostgreSQL et sur Eloquent ORM. La base de données stocke uniquement les données durables nécessaires au périmètre Wacdo Bloc 3 : les collaborateurs, les restaurants, les fonctions et les affectations. Elle ne stocke ni rôles avancés, ni permissions, ni planning RH, ni table d'historique séparée.

L'objectif de cette section est de fixer les décisions de stockage : quelles tables existent, quelles relations sont garanties, quelles contraintes protègent les données et comment l'historique des affectations est conservé.

### 7.1 Principes de persistance

PostgreSQL est la base relationnelle de référence du projet. Les accès aux données sont réalisés avec Eloquent ORM à travers les modèles Laravel `Collaborateur`, `Restaurant`, `Fonction` et `Affectation`.

La base conserve les faits métier. Elle ne conserve pas les états calculés. Les états suivants ne sont donc pas stockés dans des colonnes dédiées :

- affectation en cours ;
- affectation future ;
- affectation terminée ;
- collaborateur non affecté.

Ces états sont calculés à la consultation à partir de `date_debut`, `date_fin` et de la date du jour.

```text
Affectation en cours :
date_debut <= date du jour
ET
(date_fin est vide OU date_fin >= date du jour)
```

Cette décision évite les incohérences entre un statut stocké et les dates réelles. Une affectation dont la date de fin change n'a pas besoin de mettre à jour un champ `statut` : son état d'affichage est recalculé automatiquement par la requête ou par le modèle applicatif.

### 7.2 Traduction du modèle métier en tables

Le MCD est traduit en quatre tables métier principales.

| Entité MCD | Table PostgreSQL | Rôle de persistance |
|---|---|---|
| Collaborateur | `collaborateurs` | Stocke les personnes enregistrées, leurs coordonnées, leur droit administrateur et leur mot de passe haché lorsqu'elles disposent d'un accès applicatif. |
| Restaurant | `restaurants` | Stocke les établissements Wacdo dans lesquels les collaborateurs sont affectés. |
| Fonction | `fonctions` | Stocke le référentiel des postes disponibles. |
| Affectation | `affectations` | Stocke le lien daté entre un collaborateur, un restaurant et une fonction. |

La table `affectations` est la table centrale du modèle relationnel. Chaque ligne représente une période d'affectation. Elle répond à la question métier : quel collaborateur travaille dans quel restaurant, sur quelle fonction, et sur quelle période.

Aucune table `utilisateurs` séparée n'est créée. Le collaborateur porte lui-même les informations nécessaires à la connexion : `email`, `mot_de_passe_hash` et `administrateur`.

Aucune table `roles`, `permissions`, `historique_affectations` ou `statuts_affectation` n'est retenue. Ces tables ajouteraient une complexité non demandée par le besoin.

### 7.3 Clés, relations et intégrité référentielle

Chaque table possède une clé primaire technique conforme au dictionnaire des données :

| Table | Clé primaire |
|---|---|
| `collaborateurs` | `id_collaborateur` |
| `restaurants` | `id_restaurant` |
| `fonctions` | `id_fonction` |
| `affectations` | `id_affectation` |

La table `affectations` porte trois clés étrangères obligatoires :

| Colonne | Référence | Règle |
|---|---|---|
| `id_collaborateur` | `collaborateurs.id_collaborateur` | Une affectation concerne un collaborateur existant. |
| `id_restaurant` | `restaurants.id_restaurant` | Une affectation concerne un restaurant existant. |
| `id_fonction` | `fonctions.id_fonction` | Une affectation concerne une fonction existante. |

Ces clés étrangères traduisent les cardinalités du MCD : un collaborateur, un restaurant ou une fonction possède zéro, une ou plusieurs affectations ; une affectation référence obligatoirement un seul collaborateur, un seul restaurant et une seule fonction.

Les clés étrangères utilisent une règle de suppression restrictive. Une donnée déjà utilisée dans une affectation ne doit pas être supprimée physiquement, car elle participe à l'historique métier.

```text
ON DELETE RESTRICT
```

Cette règle empêche la suppression en cascade d'un collaborateur, d'un restaurant ou d'une fonction déjà lié à une affectation.

### 7.4 Contraintes de validité des données

La base de données protège les invariants structurels. Les Form Requests Laravel protègent les formats et les erreurs de saisie avant l'enregistrement.

Les contraintes principales retenues sont les suivantes :

| Table | Contrainte retenue |
|---|---|
| `collaborateurs` | `nom`, `prenom`, `email`, `date_premiere_embauche` et `administrateur` obligatoires. |
| `collaborateurs` | `email` unique pour tous les collaborateurs. |
| `collaborateurs` | `administrateur` vaut `false` par défaut. |
| `collaborateurs` | `mot_de_passe_hash` obligatoire lorsque `administrateur = true`. |
| `restaurants` | `nom`, `adresse`, `code_postal` et `ville` obligatoires. |
| `fonctions` | `intitule_poste` obligatoire et unique. |
| `affectations` | `id_collaborateur`, `id_restaurant`, `id_fonction` et `date_debut` obligatoires. |
| `affectations` | `date_fin` facultative. |
| `affectations` | `date_fin` supérieure ou égale à `date_debut` lorsqu'elle est renseignée. |
| `affectations` | Doublon strict interdit sur collaborateur, restaurant, fonction, date de début et date de fin. |

La cohérence chronologique d'une affectation est protégée par une contrainte de type `CHECK`.

```sql
CHECK (date_fin IS NULL OR date_fin >= date_debut)
```

Le mot de passe d'un administrateur est également protégé par une contrainte de cohérence.

```sql
CHECK (administrateur = false OR mot_de_passe_hash IS NOT NULL)
```

Le doublon strict d'affectation doit couvrir le cas où `date_fin` est vide. En PostgreSQL, cette règle est portée par un index unique utilisant `NULLS NOT DISTINCT` afin que deux valeurs `NULL` soient considérées comme identiques pour cette contrainte métier.

```sql
CREATE UNIQUE INDEX affectations_unique_strict
ON affectations (
	id_collaborateur,
	id_restaurant,
	id_fonction,
	date_debut,
	date_fin
)
NULLS NOT DISTINCT;
```

Le modèle n'interdit pas plusieurs affectations en cours pour un même collaborateur. Cette décision respecte le besoin fonctionnel qui mentionne les affectations en cours au pluriel. Aucune contrainte d'unicité temporelle par collaborateur n'est ajoutée.

### 7.5 Conservation de l'historique

L'historique des affectations est conservé directement dans la table `affectations`. Une affectation terminée reste une ligne de cette table avec une date de fin renseignée et passée.

Cette approche assure l'affichage de l'historique depuis :

- la fiche d'un collaborateur ;
- la fiche d'un restaurant ;
- la recherche transversale des affectations.

Aucune table `historique_affectations` n'est créée. L'historique demandé par le besoin correspond aux périodes d'affectation enregistrées, pas à un journal technique de toutes les modifications de formulaire.

Les colonnes techniques Laravel de création et de mise à jour d'une ligne ne remplacent pas l'historique métier. L'historique métier reste porté par `date_debut` et `date_fin`.

### 7.6 Règles de modification et de suppression

Les collaborateurs, restaurants et fonctions sont modifiables sans recréer leurs affectations. Une modification met à jour les informations de référence tout en conservant les liens existants avec les affectations.

Les affectations en cours sont modifiables conformément au besoin fonctionnel. Après modification, si la période ne couvre plus la date de consultation, l'affectation sort des affectations en cours et reste consultable dans l'historique.

La suppression métier n'est pas exposée dans le périmètre de l'application. Aucune route `DELETE`, aucun bouton de suppression et aucun mécanisme de suppression logique `deleted_at` ne sont retenus.

Une affectation ne se supprime pas pour signaler la fin d'un poste. Elle se termine par renseignement de `date_fin`.

Les clés étrangères restrictives bloquent la suppression physique d'une donnée référencée par une affectation. Cette règle protège la cohérence de l'historique sans ajouter de mécanisme d'audit ou de table supplémentaire.

### 7.7 Index et performances de recherche

Les index retenus servent les besoins de recherche, de jointure et d'intégrité identifiés dans le CDC fonctionnel. Le projet ne retient pas de moteur de recherche externe, de vue matérialisée ou d'index avancé hors périmètre.

| Index | Justification |
|---|---|
| Clé primaire de chaque table | Accès direct aux lignes et relations Eloquent. |
| Index unique sur `collaborateurs.email` | Authentification et unicité métier. |
| Index unique sur `fonctions.intitule_poste` | Référentiel de postes sans doublon. |
| Index unique strict sur `affectations` | Refus des doublons stricts d'affectation. |
| Index sur `affectations.id_collaborateur` | Affichage des affectations d'un collaborateur. |
| Index sur `affectations.id_restaurant` | Affichage des affectations d'un restaurant. |
| Index sur `affectations.id_fonction` | Filtrage des affectations par fonction. |
| Index sur `affectations.date_debut` et `affectations.date_fin` | Recherche des affectations en cours, futures ou terminées. |
| Index sur `restaurants.ville` et `restaurants.code_postal` | Recherche des restaurants et recherche des affectations par ville. |
| Index sur `collaborateurs.nom`, `collaborateurs.prenom` et `restaurants.nom` | Recherche simple dans les listes de gestion. |

Les index non retenus sont également fixés pour éviter la sur-optimisation :

- aucun index sur `mot_de_passe_hash`, car ce champ n'est jamais recherché directement ;
- aucun index sur `telephone`, car aucun filtre métier ne l'utilise ;
- aucun index sur un statut d'affectation, car aucun statut n'est stocké ;
- aucun index full-text ou trigram, car les recherches attendues restent simples.

### 7.8 Migrations et reproductibilité

La structure de la base est créée et maintenue par les migrations Laravel. Les migrations décrivent les tables, les clés primaires, les clés étrangères, les contraintes d'unicité, les contraintes de dates et les index.

L'ordre de création des tables est figé :

1. `collaborateurs` ;
2. `restaurants` ;
3. `fonctions` ;
4. `affectations`.

Cet ordre garantit que les tables référencées existent avant la création des clés étrangères de la table `affectations`.

Les migrations constituent la référence technique du schéma. Aucune modification manuelle de la base n'est retenue comme procédure normale. Le même schéma est reconstruit localement, en environnement Docker et sur le serveur de déploiement.

Les seeders Laravel servent uniquement à alimenter des données de démonstration nécessaires au développement et à la soutenance. Ils ne remplacent pas les migrations et ne définissent pas la structure de la base.

## 8 Schéma BDD

Le schéma BDD du projet est matérialisé dans le fichier `SCHEMA_BDD_bloc3_wacdo.drawio`. Il représente la traduction relationnelle du modèle métier retenu pour Bloc 3. Son objectif est de montrer de manière lisible les tables réellement stockées, leurs relations et les contraintes métier essentielles visibles à l'échelle du schéma.

Le diagramme ne remplace pas la section 7. La section 7 décrit les décisions techniques de persistance, les contraintes PostgreSQL, les index et les migrations. La section 8 présente la structure relationnelle finale de façon synthétique et soutenable devant jury.

### 8.1 Structure générale du schéma

Le schéma BDD retient quatre tables métier :

- `collaborateurs` ;
- `restaurants` ;
- `fonctions` ;
- `affectations`.

La table `affectations` est placée au centre du schéma. Elle constitue la table pivot du modèle relationnel. Les tables `collaborateurs`, `restaurants` et `fonctions` sont des tables de référence reliées à `affectations` par des clés étrangères obligatoires.

Cette organisation correspond exactement au besoin métier : une affectation relie un collaborateur, un restaurant et une fonction sur une période donnée.

### 8.2 Tables représentées

Le diagramme affiche les attributs métier réellement stockés dans la base, sans surcharger la lecture avec les détails d'implémentation.

| Table | Attributs visibles dans le schéma |
|---|---|
| `collaborateurs` | `id_collaborateur`, `nom`, `prenom`, `email`, `telephone`, `date_premiere_embauche`, `administrateur`, `mot_de_passe_hash` |
| `restaurants` | `id_restaurant`, `nom`, `adresse`, `code_postal`, `ville` |
| `fonctions` | `id_fonction`, `intitule_poste` |
| `affectations` | `id_affectation`, `id_collaborateur`, `id_restaurant`, `id_fonction`, `date_debut`, `date_fin` |

Le schéma affiche également les marqueurs suivants lorsque cela est utile à la lecture :

- `PK` pour les clés primaires ;
- `FK` pour les clés étrangères ;
- `UQ` pour les contraintes d'unicité ;
- `NULL` pour les champs facultatifs.

Le diagramme n'affiche pas les colonnes techniques Laravel de type `created_at`, `updated_at` ou `deleted_at`, car elles n'apportent aucune information métier utile à la lecture du modèle.

### 8.3 Relations représentées

Trois relations seulement sont représentées dans le schéma :

| Relation | Cardinalité affichée | Lecture métier |
|---|---|---|
| `collaborateurs` -> `affectations` | `0,n` -> `1,1` | Un collaborateur peut posséder zéro, une ou plusieurs affectations. Une affectation référence obligatoirement un seul collaborateur. |
| `restaurants` -> `affectations` | `0,n` -> `1,1` | Un restaurant peut être lié à zéro, une ou plusieurs affectations. Une affectation référence obligatoirement un seul restaurant. |
| `fonctions` -> `affectations` | `0,n` -> `1,1` | Une fonction peut être utilisée dans zéro, une ou plusieurs affectations. Une affectation référence obligatoirement une seule fonction. |

Aucune relation directe n'est dessinée entre `collaborateurs` et `restaurants`, entre `collaborateurs` et `fonctions`, ni entre `restaurants` et `fonctions`. Toute relation métier transite par la table `affectations`.

### 8.4 Contraintes visibles sur le schéma

Le diagramme met en évidence les contraintes structurantes suivantes :

- unicité de `email` dans `collaborateurs` ;
- unicité de `intitule_poste` dans `fonctions` ;
- caractère facultatif de `date_fin` dans `affectations` ;
- cohérence chronologique d'une affectation : `date_fin` vide ou supérieure ou égale à `date_debut` ;
- interdiction d'un doublon strict d'affectation ;
- absence de colonne de statut stocké pour les affectations.

Le schéma rappelle également deux décisions métier importantes :

- l'historique métier est porté directement par la table `affectations` ;
- plusieurs affectations en cours pour un même collaborateur restent autorisées par le modèle.

### 8.5 Choix de représentation

Le diagramme a été volontairement limité à un niveau d'information intermédiaire : suffisamment détaillé pour montrer la vraie structure de la base, mais sans devenir un script SQL dessiné.

Les éléments suivants sont donc exclus du schéma graphique et restent documentés dans la section 7 :

- le détail des index ;
- la syntaxe exacte des contraintes PostgreSQL ;
- les règles de migration Laravel ;
- les choix d'implémentation Eloquent ;
- les statuts calculés `en cours`, `future` et `terminée` comme colonnes de base.

Ce choix rend le schéma plus lisible à l'oral. Il permet de défendre clairement la logique métier suivante : le modèle comporte quatre tables, trois relations, une table pivot centrale et aucune table additionnelle inutile.