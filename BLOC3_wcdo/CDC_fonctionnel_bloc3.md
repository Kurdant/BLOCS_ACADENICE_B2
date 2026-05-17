## Sommaire

## 1 Contexte

Wacdo est une enseigne de restauration rapide dont l'activité repose sur un réseau de restaurants répartis sur plusieurs villes. La gestion des ressources humaines — collaborateurs, postes occupés et affectations dans les restaurants — est aujourd'hui réalisée sans outil centralisé, ce qui génère des erreurs de suivi et une perte de traçabilité sur l'historique des affectations.

Dans le cadre du développement d'une nouvelle application de gestion interne, Wacdo commande la conception et le développement d'un système centralisé destiné à structurer la gestion opérationnelle des collaborateurs et de leurs affectations dans les différents restaurants du réseau.

L'application est une interface web à usage exclusivement interne. Elle centralise la gestion des collaborateurs, des restaurants, des fonctions (postes de travail) et des affectations. L'accès est restreint aux collaborateurs authentifiés disposant du droit administrateur. Aucune fonctionnalité n'est accessible sans authentification.

Ce document formalise les besoins fonctionnels de l'application afin de guider sa conception, son développement et sa recette.

## 2 Objectif

### 2.1 Objectif général

Le présent projet a pour objet le développement d'une application interne Wacdo dédiée à la gestion des affectations des collaborateurs dans les restaurants de l'enseigne. Cette application constitue l'outil d'administration central permettant de gérer les restaurants, les collaborateurs, les fonctions et l'historique des affectations, avec une vision fiable des postes en cours et des postes passés.

L'application vise à répondre à trois enjeux fondamentaux :

- **Pilotage opérationnel des affectations** : centraliser en un point unique la gestion des collaborateurs, des postes et de leurs affectations afin d'assurer une répartition lisible, à jour et exploitable des équipes dans les restaurants.
- **Fiabilité des données de gestion** : conserver un historique cohérent des affectations, identifier à tout moment les postes en cours, repérer les collaborateurs non affectés et réduire les erreurs de suivi.
- **Sécurité d'accès à l'outil** : réserver l'accès aux seuls collaborateurs autorisés, avec authentification obligatoire et contrôle strict des droits d'administration.

### 2.2 Objectifs fonctionnels détaillés

Le système doit permettre :

**Authentification et contrôle d'accès**
L'application n'est accessible qu'aux collaborateurs disposant du droit administrateur et d'un mot de passe. Aucune fonctionnalité n'est disponible sans authentification. Les autorisations sont vérifiées côté serveur avant tout accès à une fonctionnalité ou à une donnée de gestion.

**Gestion des restaurants**
L'application permet de consulter la liste des restaurants avec un moteur de recherche et de filtrage par nom, code postal et ville. Un administrateur peut créer un restaurant, consulter son détail et modifier ses informations. La fiche détail d'un restaurant affiche les collaborateurs actuellement en poste dans ce restaurant, avec filtrage par fonction, nom et date de début d'affectation. Elle permet également de consulter l'historique des affectations du restaurant et d'affecter un nouveau collaborateur.

**Gestion des fonctions**
L'application permet de consulter la liste des fonctions existantes au sein de Wacdo, de créer une nouvelle fonction et de modifier une fonction existante. Les fonctions constituent le référentiel des postes utilisables lors des affectations des collaborateurs.

**Gestion des collaborateurs**
L'application permet de consulter la liste des collaborateurs avec un moteur de recherche et de filtrage par nom, prénom et email. Un administrateur peut créer un collaborateur, consulter son détail et modifier ses informations. La fiche détail d'un collaborateur affiche ses affectations en cours ainsi que l'historique complet de ses affectations, avec filtrage par fonction et date de début d'affectation. Le système permet également d'identifier les collaborateurs non affectés, d'affecter un collaborateur à un nouveau poste et de modifier une affectation en cours.

**Recherche transversale des affectations**
L'application permet d'afficher la liste des affectations avec un moteur de recherche et de filtrage par fonction, date de début, date de fin et ville. Cette fonctionnalité fournit une vue transversale de l'occupation des postes dans l'ensemble du réseau de restaurants.

**Validation des données**
Le système contrôle la présence des informations essentielles avant validation d'une création ou d'une modification. Les formulaires appliquent des vérifications sur les champs d'identité, les adresses, les dates, les emails et l'ensemble des données obligatoires afin de garantir la qualité et la cohérence des informations enregistrées.

**Tests et validation**
Avant déploiement, une série de tests est effectuée afin de vérifier que l'application respecte les spécifications fonctionnelles, les règles de gestion des affectations, les contrôles d'accès, la fiabilité des recherches et la cohérence des données manipulées.

### 2.3 Résultat attendu

À l'issue du projet, l'application Wacdo doit constituer un système opérationnel, sécurisé et maintenable permettant de gérer de manière centralisée les restaurants, les collaborateurs, les fonctions et les affectations, avec une traçabilité complète des positions en cours et passées dans l'ensemble du réseau.

## 3 Périmètre

### 3.1 Périmètre inclus

Le présent projet couvre le développement complet de l'application interne Wacdo, incluant les domaines fonctionnels suivants :

**Authentification et contrôle d'accès**
L'accès à l'application est réservé aux collaborateurs disposant du droit d'utiliser l'application et du droit administrateur. L'authentification par mot de passe conditionne l'accès à l'ensemble des fonctionnalités de gestion.

**Gestion des restaurants**
Gestion des restaurants avec consultation de la liste, recherche et filtrage par nom, code postal et ville, création d'un restaurant, consultation du détail d'un restaurant et modification de ses informations. Le détail d'un restaurant inclut la liste des collaborateurs en poste, filtrable par fonction, nom et date de début d'affectation, ainsi que l'historique des affectations du restaurant.

**Gestion des collaborateurs**
Gestion des collaborateurs avec consultation de la liste, recherche et filtrage par nom, prénom et email, création d'un collaborateur, consultation de sa fiche détail et modification de ses informations. Le système inclut également la recherche des collaborateurs non affectés, l'affichage des affectations en cours et de l'historique des affectations pour chaque collaborateur.

**Gestion des fonctions**
Consultation de la liste des fonctions existantes, création d'une fonction et modification d'une fonction existante. Les fonctions gérées dans l'application constituent le référentiel utilisé lors de l'affectation des collaborateurs.

**Gestion des affectations**
Création d'une nouvelle affectation depuis le détail d'un restaurant ou depuis le détail d'un collaborateur, consultation des affectations en cours et de l'historique, modification des affectations en cours, et prise en compte des dates de début et de fin pour distinguer les affectations actives des affectations passées.

**Recherche transversale des affectations**
Consultation de la liste des affectations avec recherche et filtrage par fonction, date de début, date de fin et ville, afin d'obtenir une vue globale des affectations sur l'ensemble des restaurants.

**Validation des données**
Contrôle de la présence des informations essentielles avant validation d'une création ou d'une modification, avec vérification des champs saisis dans les formulaires afin de garantir la cohérence des données enregistrées.

---

### 3.2 Périmètre exclu

Les éléments suivants sont explicitement hors périmètre du présent projet :

| Domaine exclu | Justification |
|---|---|
| Gestion de la paie, des contrats et des dossiers RH complets | Le projet porte sur les collaborateurs, les restaurants, les fonctions et les affectations. Il ne couvre pas l'administration RH complète. |
| Gestion des congés, absences et plannings horaires détaillés | Le sujet traite des affectations à un restaurant et à une fonction, pas de la planification horaire quotidienne ou hebdomadaire. |
| Recrutement et gestion des candidatures | Aucun besoin de recrutement ou de suivi de candidature n'est défini dans le référentiel du bloc 3. |
| Gestion opérationnelle des restaurants hors affectations | Les ventes, les commandes clients, les stocks, la production et l'encaissement ne relèvent pas du périmètre fonctionnel de cette application. |
| Interfaces publiques et intégrations avec des systèmes externes non mentionnés | Le bloc 3 ne définit pas d'API publique ni d'échange avec un système tiers. Le périmètre se limite à une application interne d'administration. |

## 4 Acteurs

L'application Wacdo définie dans le bloc 3 ne comporte qu'un seul acteur applicatif autorisé à utiliser le système : le collaborateur disposant du droit administrateur et d'un mot de passe. Les autres collaborateurs peuvent exister dans les données gérées par l'application, mais ils ne disposent d'aucun accès à l'interface de gestion. Aucun rôle opérationnel distinct de type Préparation ou Accueil n'est défini dans le périmètre du projet, et aucun système externe n'interagit avec l'application.

---

### 4.1 Utilisateur interne — rôle Administration

L'administrateur est le seul utilisateur habilité à accéder à l'application Wacdo. Il se connecte avec son compte collaborateur et son mot de passe afin d'administrer les restaurants, les collaborateurs, les fonctions et les affectations.

**Responsabilités :**
- Consulter, rechercher, créer et modifier les restaurants.
- Consulter, rechercher, créer et modifier les collaborateurs.
- Consulter, créer et modifier les fonctions.
- Consulter, rechercher, créer et modifier les affectations.
- Consulter les affectations en cours, l'historique des affectations et la liste des collaborateurs non affectés.

**Accès au système :**
L'administrateur dispose de l'accès complet à l'ensemble des fonctionnalités prévues par le bloc 3. Il est le seul acteur capable d'utiliser le menu principal et d'exécuter les opérations de gestion associées.

**Interactions avec le système :**
L'administrateur accède à l'application via une interface web interne. Après authentification, il utilise les vues de gestion pour rechercher les informations, consulter les fiches de détail, modifier les données existantes et créer de nouvelles affectations.

**Contraintes :**
L'accès est refusé à tout collaborateur ne disposant pas du droit administrateur. Aucune fonctionnalité n'est accessible sans authentification préalable. Toute opération de création ou de modification reste soumise aux contrôles de validation définis par l'application.

### Synthèse

| Acteur | Type | Condition d'accès | Domaines d'action |
|---|---|---|---|
| Collaborateur administrateur | Interne | Compte collaborateur autorisé + mot de passe | Accès complet à l'application : gestion des restaurants, des collaborateurs, des fonctions et des affectations |

## 5 Glossaire

**Application Wacdo** = application web interne permettant de gérer les restaurants, les collaborateurs, les fonctions et les affectations.

**Collaborateur** = personne employée par Wacdo et enregistrée dans l'application avec ses informations d'identité et de gestion.

**Administrateur** = collaborateur autorisé à utiliser l'application. Il dispose d'un droit d'accès et d'un mot de passe lui permettant de se connecter au système.

**Droit d'utilisation de l'application** = information indiquant si un collaborateur est autorisé à accéder à l'application de gestion.

**Restaurant** = établissement Wacdo dans lequel des collaborateurs peuvent être affectés.

**Fonction** = poste de travail existant chez Wacdo, utilisé lors de l'affectation d'un collaborateur dans un restaurant.

**Affectation** = association d'un collaborateur, d'un restaurant et d'une fonction sur une période donnée, définie par une date de début et une date de fin éventuelle.

**Affectation en cours** = affectation active à la date de consultation. Elle respecte la règle suivante : la date de début est passée ou égale à la date du jour, et la date de fin est vide ou postérieure ou égale à la date du jour.

**Historique des affectations** = ensemble des affectations passées et en cours rattachées à un collaborateur ou à un restaurant.

**Collaborateur non affecté** = collaborateur enregistré ne disposant d'aucune affectation active à la date de consultation.

**Authentification** = mécanisme de contrôle d'accès permettant à un collaborateur administrateur de se connecter à l'application à l'aide de son mot de passe.

## 6 Règles de gestion

Les règles de gestion décrivent les contraintes métier que l'application Wacdo doit respecter. Elles servent de base au MCD, au MCT, aux écrans de gestion et aux tests fonctionnels.

### Accès et habilitations

RG-ACC-001 — Tous les collaborateurs ne disposent pas d'un accès à l'application

Un collaborateur peut exister dans l'application sans être autorisé à s'y connecter. Le droit d'utilisation de l'application est porté par la fiche collaborateur.

RG-ACC-002 — Seul un collaborateur administrateur peut accéder à l'application

L'application est réservée aux collaborateurs dont l'indicateur administrateur est actif.

RG-ACC-003 — Un collaborateur autorisé doit disposer d'un mot de passe

Un mot de passe est obligatoire pour tout collaborateur autorisé à utiliser l'application. Aucun mot de passe applicatif n'est requis pour un collaborateur non administrateur.

RG-ACC-004 — Aucune fonctionnalité n'est accessible sans authentification

La consultation du menu principal, des listes, des fiches détail et des écrans de création ou de modification exige une authentification préalable.

RG-ACC-005 — Les actions protégées sont réservées aux utilisateurs autorisés

Toute action de consultation protégée, de création ou de modification doit être refusée si l'utilisateur connecté ne dispose pas des autorisations requises.

### Collaborateurs

RG-COLLAB-001 — Un collaborateur possède les données minimales du modèle

Chaque collaborateur est défini au minimum par un nom, un prénom, un email, une date de première embauche et un indicateur administrateur.

RG-COLLAB-002 — Le statut administrateur d'un collaborateur est explicite

Chaque collaborateur est soit administrateur, soit non administrateur. Ce statut détermine son droit éventuel d'accès à l'application.

RG-COLLAB-003 — Un collaborateur peut ne jamais être affecté ou être affecté plusieurs fois dans le temps

L'historique doit permettre de conserver zéro, une ou plusieurs affectations pour un même collaborateur sur des périodes successives ou distinctes.

RG-COLLAB-004 — Un collaborateur non affecté ne possède aucune affectation active à la date de consultation

Un collaborateur est considéré comme non affecté lorsqu'il ne dispose d'aucune affectation active à la date de consultation.

RG-COLLAB-005 — La recherche des collaborateurs s'effectue par identité et email

La liste des collaborateurs doit pouvoir être filtrée par nom, prénom et email.

RG-COLLAB-006 — La fiche collaborateur distingue les affectations en cours et l'historique

Le détail d'un collaborateur doit afficher ses affectations actives ainsi que l'historique complet de ses affectations. Cet affichage doit être filtrable par poste et par date de début d'affectation.

### Restaurants

RG-REST-001 — Un restaurant possède les données minimales du modèle

Chaque restaurant est défini au minimum par un nom, une adresse, un code postal et une ville.

RG-REST-002 — La recherche des restaurants s'effectue par localisation et nom

La liste des restaurants doit pouvoir être filtrée par nom, code postal et ville.

RG-REST-003 — La fiche restaurant affiche les collaborateurs actuellement en poste

Le détail d'un restaurant doit présenter uniquement les collaborateurs disposant d'une affectation en cours dans ce restaurant.

RG-REST-004 — La liste des collaborateurs en poste dans un restaurant est filtrable

La liste des collaborateurs en poste dans un restaurant doit pouvoir être filtrée par poste, nom et date de début d'affectation.

RG-REST-005 — L'historique des affectations d'un restaurant est consultable depuis sa fiche

La fiche restaurant doit permettre d'accéder à l'historique des affectations du restaurant, avec possibilité de filtrage.

### Fonctions

RG-FONC-001 — Une fonction correspond à un poste de travail

La fonction représente un poste existant chez Wacdo et utilisé lors des affectations des collaborateurs.

RG-FONC-002 — Une fonction possède un intitulé de poste

Chaque fonction doit être définie par un intitulé exploitable dans les listes, les formulaires et les écrans de détail.

RG-FONC-003 — Les fonctions sont gérées dans un référentiel dédié

L'application doit permettre de consulter la liste des fonctions, de créer une fonction et de modifier une fonction existante.

### Affectations

RG-AFF-001 — Une affectation associe un collaborateur, un restaurant et une fonction

Toute affectation relie obligatoirement un collaborateur, un restaurant et une fonction.

RG-AFF-002 — Une affectation possède une date de début obligatoire

Une affectation ne peut pas être créée sans date de début.

RG-AFF-003 — La date de fin d'une affectation est facultative

Une affectation peut être créée sans date de fin lorsqu'aucune date de fin n'est connue au moment de l'enregistrement.

RG-AFF-004 — Une affectation en cours est active à la date de consultation

Une affectation est considérée comme active à la date de consultation lorsque sa date de début est passée ou égale à la date du jour, et que sa date de fin est vide ou postérieure ou égale à la date du jour.

RG-AFF-005 — Une affectation terminée possède une date de fin passée

Une affectation est terminée lorsque sa date de fin est renseignée et strictement antérieure à la date de consultation.

RG-AFF-006 — La cohérence chronologique d'une affectation est obligatoire

Lorsqu'une date de fin est renseignée, elle ne peut pas être antérieure à la date de début.

RG-AFF-007 — Une même affectation ne peut pas être dupliquée sur une période identique

Pour un même collaborateur, un même restaurant et une même fonction, l'application ne doit pas permettre l'enregistrement de doublons strictement identiques sur la même période.

RG-AFF-008 — Les affectations en cours sont modifiables

Une affectation active peut être modifiée depuis les écrans prévus à cet effet.

RG-AFF-009 — Un collaborateur peut être affecté depuis sa fiche détail

La fiche collaborateur doit permettre de créer une nouvelle affectation vers un poste et un restaurant.

RG-AFF-010 — Un restaurant peut recevoir une nouvelle affectation depuis sa fiche détail

La fiche restaurant doit permettre d'affecter un collaborateur à un poste dans ce restaurant.

RG-AFF-011 — La recherche des affectations s'effectue sur les critères définis par le besoin

La liste des affectations doit pouvoir être filtrée par poste, date de début, date de fin et ville.

RG-AFF-012 — L'historique des affectations doit être conservé

Une affectation terminée demeure consultable dans l'historique du collaborateur, du restaurant et dans la recherche des affectations.

### Validation des données

RG-VAL-001 — Une création ou une modification ne peut pas être finalisée si des informations essentielles sont manquantes

Aucune création ni modification de collaborateur, restaurant, fonction ou affectation ne peut être enregistrée si les données obligatoires ne sont pas renseignées.

RG-VAL-002 — Les champs saisis dans les formulaires doivent être contrôlés avant validation

Les champs nom, prénom, adresse et l'ensemble des données structurées saisies dans l'application doivent être vérifiés avant enregistrement.

RG-VAL-003 — Tout champ téléphone présent dans un formulaire doit être validé

Lorsqu'un formulaire comporte un champ téléphone, sa valeur doit être contrôlée avant validation.

RG-VAL-004 — Les formats métiers doivent être respectés

Les emails, les codes postaux et les dates doivent respecter un format exploitable par l'application et cohérent avec les règles métier.

## 7 Parcours utilisateurs

Les parcours utilisateurs décrivent l'utilisation de l'application Wacdo par le seul acteur autorisé à y accéder : le collaborateur disposant du droit administrateur.

Chaque parcours commence par une authentification, car aucune fonctionnalité de l'application ne doit être accessible sans connexion préalable.

Le menu principal donne accès aux quatre domaines fonctionnels définis dans le référentiel : gestion des restaurants, gestion des collaborateurs, gestion des fonctions et recherche des affectations.

### Principes communs aux parcours

PU-COM-001 — Authentification obligatoire

Un collaborateur administrateur doit être authentifié avant d'accéder aux fonctionnalités de l'application.

PU-COM-002 — Refus d'authentification invalide

Si les identifiants sont invalides ou si le collaborateur ne dispose pas du droit administrateur, l'accès à l'application est refusé.

PU-COM-003 — Contrôle des autorisations

Avant chaque action protégée, l'application vérifie côté serveur que l'utilisateur connecté dispose des autorisations nécessaires.

PU-COM-004 — Contrôle des données avant validation

Avant tout enregistrement, l'application vérifie que les informations obligatoires sont présentes et que les données saisies respectent les formats attendus.

PU-COM-005 — Refus des actions invalides

Toute action incohérente, incomplète ou non autorisée est refusée et ne doit provoquer aucune modification des données métier.

### Parcours Administration

**Acteur principal** : collaborateur disposant du droit administrateur.

**Objectif** : administrer les restaurants, les collaborateurs, les fonctions et les affectations.

**Précondition** : le collaborateur dispose d'un compte autorisé à utiliser l'application et d'un mot de passe valide.

PU-ADM-001 — Accès à l'application

L'administrateur ouvre l'application Wacdo et accède à l'écran de connexion.

PU-ADM-002 — Authentification

L'administrateur saisit ses identifiants. Si les identifiants sont valides, l'application ouvre une session et autorise l'accès au menu principal.

PU-ADM-003 — Accès au menu principal

Après connexion, l'administrateur accède aux quatre entrées principales de l'application : gestion des restaurants, gestion des collaborateurs, gestion des fonctions et recherche des affectations.

PU-ADM-004 — Consultation et recherche des restaurants

Depuis la gestion des restaurants, l'administrateur consulte la liste des restaurants et utilise le formulaire de recherche pour filtrer les résultats par nom, code postal ou ville.

PU-ADM-005 — Création d'un restaurant

L'administrateur peut créer un restaurant depuis la liste. Avant enregistrement, l'application vérifie la présence des informations obligatoires. Si les données sont valides, le restaurant est enregistré.

PU-ADM-006 — Consultation du détail d'un restaurant

En sélectionnant un restaurant dans la liste, l'administrateur accède à sa fiche détail. Cette fiche affiche les collaborateurs actuellement en poste dans ce restaurant et permet un filtrage par poste, nom et date de début d'affectation.

PU-ADM-007 — Consultation de l'historique du restaurant et création d'une affectation

Depuis la fiche restaurant, l'administrateur accède à l'historique des affectations du restaurant, utilise les filtres disponibles et peut affecter un nouveau collaborateur à un poste dans ce restaurant.

PU-ADM-008 — Consultation et recherche des collaborateurs

Depuis la gestion des collaborateurs, l'administrateur consulte la liste des collaborateurs et utilise le formulaire de recherche pour filtrer les résultats par nom, prénom ou email.

PU-ADM-009 — Création d'un collaborateur

L'administrateur peut créer un collaborateur depuis la liste. Avant enregistrement, l'application contrôle la présence des données obligatoires et la validité des informations saisies.

PU-ADM-010 — Recherche des collaborateurs non affectés

Depuis la gestion des collaborateurs, l'administrateur peut lancer la recherche des collaborateurs ne disposant d'aucune affectation active à la date de consultation.

PU-ADM-011 — Consultation du détail d'un collaborateur

En sélectionnant un collaborateur dans la liste, l'administrateur accède à sa fiche détail. Cette fiche affiche les affectations en cours et l'historique des affectations du collaborateur, avec filtrage par poste et date de début d'affectation.

PU-ADM-012 — Modification du collaborateur et création d'une nouvelle affectation

Depuis la fiche collaborateur, l'administrateur peut modifier les informations du collaborateur et l'affecter à un nouveau poste.

PU-ADM-013 — Modification d'une affectation en cours

L'administrateur peut modifier une affectation active depuis les écrans prévus à cet effet. L'application contrôle la cohérence des données avant validation.

PU-ADM-014 — Consultation et gestion des fonctions

Depuis la gestion des fonctions, l'administrateur consulte la liste des fonctions existantes, crée une nouvelle fonction ou modifie une fonction existante.

PU-ADM-015 — Recherche des affectations

Depuis la recherche des affectations, l'administrateur consulte la liste des affectations et utilise le formulaire de recherche pour filtrer les résultats par poste, date de début, date de fin et ville.

PU-ADM-016 — Enregistrement des modifications

Lorsque les données saisies sont valides, l'application enregistre les créations et les modifications concernant les restaurants, les collaborateurs, les fonctions et les affectations.

PU-ADM-017 — Déconnexion

L'administrateur peut se déconnecter. La session est fermée et l'accès aux pages protégées redevient impossible sans nouvelle authentification.

## 8 Matrice des droits (rôle × fonctionnalité)

Une matrice des droits permet de vérifier rapidement quelles fonctionnalités sont accessibles selon le profil de l'utilisateur. Dans le cadre du bloc 3, un seul profil applicatif dispose d'un accès au système : le collaborateur administrateur.

Par défaut, toute action non explicitement autorisée est refusée.

| Fonctionnalité | Collaborateur administrateur | Collaborateur non administrateur | Utilisateur non authentifié |
|---|---|---|---|
| Accéder à l'application | Oui | Non | Non |
| S'authentifier | Oui | Non | Oui |
| Se déconnecter | Oui | Non | Non |
| Consulter le menu principal | Oui | Non | Non |
| Consulter la liste des restaurants | Oui | Non | Non |
| Rechercher et filtrer les restaurants | Oui | Non | Non |
| Créer un restaurant | Oui | Non | Non |
| Consulter le détail d'un restaurant | Oui | Non | Non |
| Consulter les collaborateurs en poste dans un restaurant | Oui | Non | Non |
| Consulter l'historique des affectations d'un restaurant | Oui | Non | Non |
| Affecter un collaborateur depuis la fiche d'un restaurant | Oui | Non | Non |
| Consulter la liste des collaborateurs | Oui | Non | Non |
| Rechercher et filtrer les collaborateurs | Oui | Non | Non |
| Créer un collaborateur | Oui | Non | Non |
| Rechercher les collaborateurs non affectés | Oui | Non | Non |
| Consulter le détail d'un collaborateur | Oui | Non | Non |
| Consulter les affectations en cours d'un collaborateur | Oui | Non | Non |
| Consulter l'historique des affectations d'un collaborateur | Oui | Non | Non |
| Modifier un collaborateur | Oui | Non | Non |
| Affecter un collaborateur à un nouveau poste depuis sa fiche | Oui | Non | Non |
| Modifier une affectation en cours | Oui | Non | Non |
| Consulter la liste des fonctions | Oui | Non | Non |
| Créer une fonction | Oui | Non | Non |
| Modifier une fonction | Oui | Non | Non |
| Consulter la liste des affectations | Oui | Non | Non |
| Rechercher et filtrer les affectations | Oui | Non | Non |
| Enregistrer une création ou une modification valide | Oui | Non | Non |

## 9 Exigences fonctionnelles

Les exigences fonctionnelles synthétisent, par domaine, ce que l'application doit obligatoirement réaliser. Elles constituent la base de vérification pour les tests, la recette et la démonstration de l'application.

### Authentification et accès

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-AUTH-001 | Autoriser l'accès uniquement aux collaborateurs disposant du droit administrateur | `RG-ACC-001`, `RG-ACC-002` |
| EF-AUTH-002 | Exiger une authentification avant tout accès aux fonctionnalités protégées | `RG-ACC-003`, `RG-ACC-004` |
| EF-AUTH-003 | Refuser toute action protégée effectuée sans autorisation valide | `RG-ACC-005` |

### Gestion des restaurants

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-REST-001 | Afficher la liste des restaurants avec recherche et filtrage par nom, code postal et ville | `RG-REST-001`, `RG-REST-002` |
| EF-REST-002 | Permettre la création et la modification d'un restaurant après contrôle des données obligatoires | `RG-REST-001`, `RG-VAL-001`, `RG-VAL-002` |
| EF-REST-003 | Afficher, dans la fiche d'un restaurant, les collaborateurs actuellement en poste | `RG-REST-003` |
| EF-REST-004 | Permettre le filtrage des collaborateurs en poste dans un restaurant par poste, nom et date de début d'affectation | `RG-REST-004` |
| EF-REST-005 | Permettre la consultation de l'historique des affectations d'un restaurant et l'affectation d'un nouveau collaborateur depuis cette fiche | `RG-REST-005`, `RG-AFF-010` |

### Gestion des collaborateurs

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-COLLAB-001 | Permettre la création et la modification d'un collaborateur avec les données minimales attendues | `RG-COLLAB-001`, `RG-COLLAB-002`, `RG-VAL-001` |
| EF-COLLAB-002 | Afficher la liste des collaborateurs avec recherche et filtrage par nom, prénom et email | `RG-COLLAB-005` |
| EF-COLLAB-003 | Identifier et afficher les collaborateurs non affectés | `RG-COLLAB-004` |
| EF-COLLAB-004 | Afficher, dans la fiche d'un collaborateur, les affectations en cours et l'historique des affectations, avec filtrage par poste et date de début | `RG-COLLAB-003`, `RG-COLLAB-006` |
| EF-COLLAB-005 | Permettre, depuis la fiche d'un collaborateur, la création d'une nouvelle affectation vers un poste et un restaurant | `RG-AFF-009` |

### Gestion des fonctions

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-FONC-001 | Afficher la liste des fonctions enregistrées dans l'application | `RG-FONC-003` |
| EF-FONC-002 | Permettre la création et la modification d'une fonction définie par un intitulé de poste | `RG-FONC-001`, `RG-FONC-002`, `RG-FONC-003` |
| EF-FONC-003 | Utiliser les fonctions comme référentiel de postes dans les affectations | `RG-FONC-001`, `RG-AFF-001` |

### Gestion des affectations

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-AFF-001 | Enregistrer toute affectation avec un collaborateur, un restaurant, une fonction et une date de début | `RG-AFF-001`, `RG-AFF-002` |
| EF-AFF-002 | Gérer les affectations actives avec une date de fin facultative | `RG-AFF-003`, `RG-AFF-004` |
| EF-AFF-003 | Considérer une affectation comme terminée lorsqu'une date de fin est renseignée | `RG-AFF-005` |
| EF-AFF-004 | Refuser toute affectation dont la date de fin est antérieure à la date de début | `RG-AFF-006` |
| EF-AFF-005 | Empêcher l'enregistrement de doublons strictement identiques pour un même collaborateur, un même restaurant, une même fonction et une même période | `RG-AFF-007` |
| EF-AFF-006 | Permettre la modification des affectations en cours | `RG-AFF-008` |
| EF-AFF-007 | Permettre la recherche des affectations par poste, date de début, date de fin et ville | `RG-AFF-011` |
| EF-AFF-008 | Conserver et afficher l'historique des affectations dans les fiches et dans la recherche transversale | `RG-AFF-012` |

### Validation des données

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-VAL-001 | Refuser toute création ou modification incomplète portant sur un collaborateur, un restaurant, une fonction ou une affectation | `RG-VAL-001` |
| EF-VAL-002 | Contrôler les champs saisis dans les formulaires avant enregistrement | `RG-VAL-002` |
| EF-VAL-003 | Contrôler la valeur du téléphone lorsqu'un champ téléphone est présent dans un formulaire | `RG-VAL-003` |
| EF-VAL-004 | Vérifier les formats des emails, codes postaux et dates | `RG-VAL-004` |

## 10 Contraintes non fonctionnelles

Les contraintes non fonctionnelles définissent les exigences de qualité, de sécurité, de réalisation technique et de déploiement auxquelles l'application Wacdo doit se conformer.

### Architecture et réalisation technique

| ID | Contrainte |
|---|---|
| CNF-ARCH-001 | L'application est réalisée avec un framework back. |
| CNF-ARCH-002 | Les fonctionnalités métier sont développées avec un langage serveur adapté au framework retenu. |
| CNF-ARCH-003 | L'interface web s'appuie sur un moteur de template compatible avec le framework choisi. |
| CNF-ARCH-004 | Les données sont stockées dans une base de données SQL cohérente avec le modèle de données validé. |
| CNF-ARCH-005 | La gestion des entités applicatives repose sur un ORM compatible avec la base de données et le framework retenus. |

### Sécurité applicative

| ID | Contrainte |
|---|---|
| CNF-SEC-001 | L'accès à l'application est limité aux collaborateurs autorisés disposant du droit administrateur et d'un mot de passe. |
| CNF-SEC-002 | L'application met en œuvre des mécanismes d'authentification et d'autorisation côté serveur. |
| CNF-SEC-003 | Les mots de passe ne doivent jamais être stockés en clair. Les modalités techniques de protection sont précisées dans le CDC technique. |
| CNF-SEC-004 | Toutes les données reçues depuis les formulaires sont validées côté serveur avant traitement et enregistrement. |

### Qualité et validation

| ID | Contrainte |
|---|---|
| CNF-QUAL-001 | Aucune étape métier ne peut être finalisée si les informations essentielles sont absentes ou incohérentes. |
| CNF-QUAL-002 | Les formulaires doivent contrôler les champs sensibles mentionnés dans le référentiel, notamment nom, prénom, adresse et téléphone lorsqu'il est présent. |
| CNF-QUAL-003 | L'application doit conserver une cohérence permanente entre les données affichées, les données enregistrées et les règles métier définies dans le présent CDC. |

### Tests et recette

| ID | Contrainte |
|---|---|
| CNF-TEST-001 | Avant déploiement, l'application fait l'objet d'une série de tests d'interface utilisateur. |
| CNF-TEST-002 | Avant déploiement, l'application fait l'objet d'une série de tests fonctionnels couvrant les parcours et les règles de gestion. |
| CNF-TEST-003 | Avant déploiement, l'application fait l'objet de tests de sécurité adaptés à son périmètre. |

### Déploiement et soutenance

| ID | Contrainte |
|---|---|
| CNF-DEPLOY-001 | L'environnement de développement est construit avec le framework choisi et l'ensemble des dépendances nécessaires aux fonctionnalités attendues. |
| CNF-DEPLOY-002 | L'application est développée dans l'environnement du framework retenu et livrée comme une application complète. |
| CNF-DEPLOY-003 | L'application est déployée sur un serveur et présentée en état de fonctionnement lors de la soutenance. |
| CNF-DEPLOY-004 | La soutenance doit permettre d'argumenter le fonctionnement global du framework, son architecture et les choix de dépendances installées. |
| CNF-DEPLOY-005 | Le code doit pouvoir être modifié pendant la soutenance pour répondre à une problématique imprévue demandée par le jury. |


## 11 Livrables attendus

Conformément au référentiel du bloc 3 et aux attentes du jury, le projet Wacdo doit produire les livrables suivants :

| Livrable | Description |
|---|---|
| **CDC fonctionnel** | Document décrivant le contexte, les objectifs, le périmètre, les acteurs, le glossaire, les règles de gestion, les parcours utilisateurs, les droits, les exigences fonctionnelles et le MCD. |
| **CDC technique** | Document décrivant les choix techniques retenus, l'architecture applicative, la persistance, la sécurité, l'environnement de développement, le déploiement et les conventions de développement. |
| **Dictionnaire des données** | Tableau de référence décrivant les données manipulées par l'application : nom, définition, entité concernée, type, caractère obligatoire, contraintes et exemples. |
| **MCD** — Modèle Conceptuel de Données | Diagramme entités-associations couvrant les objets métier du projet : collaborateurs, restaurants, fonctions et affectations. |
| **MCT** — Modèle Conceptuel des Traitements | Schéma fonctionnel décrivant les traitements métier principaux : authentification, gestion des restaurants, gestion des collaborateurs, gestion des fonctions et recherche des affectations. |
| **MPD / Schéma de base de données** | Modèle relationnel dérivé du MCD, avec tables, clés primaires, clés étrangères, types de données et contraintes d'intégrité. |
| **Base de données SQL** | Base relationnelle opérationnelle, cohérente avec le MPD, contenant des données de démonstration suffisantes pour présenter l'application. |
| **Application web Wacdo** | Application complète développée dans l'environnement du framework retenu, couvrant l'ensemble des fonctionnalités décrites dans le présent CDC. |
| **Sécurité applicative** | Authentification, autorisation, gestion sécurisée des mots de passe, protection des accès et validation serveur des données saisies. |
| **Tests de validation** | Tests d'interface utilisateur, tests fonctionnels et tests de sécurité permettant de vérifier que l'application répond aux spécifications. |
| **Application déployée** | Application fonctionnelle déployée sur un serveur et accessible lors de la soutenance. |
| **Présentation jury** | Argumentation du fonctionnement global du framework, de ses spécificités, de son architecture, des dépendances installées et des choix de conception. |
| **Capacité de modification en direct** | Projet suffisamment maîtrisé pour permettre une modification ou un ajout de code pendant la soutenance, en réponse à une demande du jury. |
