# CDC fonctionnel Bloc 2

## 1 Contexte

Wacdo est une enseigne de restauration rapide dont l'activité repose sur un système de prise de commande numérique externe. Les commandes clients sont transmises au back-office par ce système via une API REST. Le paiement est pris en charge par le système externe, en amont de toute interaction avec le back-office — il est hors périmètre du présent projet. Chaque commande est identifiée par un numéro de retrait permettant sa remise au client au comptoir.

Dans le cadre du développement d'une nouvelle application back-office, Wacdo commande la conception et le développement d'un système centralisé destiné à structurer la gestion opérationnelle de l'établissement. L'application existante ne permet pas de gérer de façon cohérente et sécurisée l'ensemble des données du catalogue, le traitement des commandes en temps réel et les accès différenciés du personnel.

Le back-office est une application web à usage exclusivement interne. Il centralise la gestion du catalogue de produits et de menus, le traitement opérationnel des commandes et la gestion des accès du personnel. L'accès est restreint aux utilisateurs authentifiés. Les droits sont organisés par rôle : **Administration**, **Preparation** et **Accueil**.

L'API REST exposée par le back-office constitue le point d'intégration avec le système de prise de commande externe : elle publie le catalogue commandable et reçoit les commandes validées. Les commandes — qu'elles soient reçues par API ou saisies manuellement par le rôle Accueil — suivent le même cycle opérationnel unifié : `à préparer` → `préparée` → `livrée`.

Ce document formalise les besoins fonctionnels du back-office afin de guider sa conception, son développement et sa recette.

## 2 Objectif

### 2.1 Objectif général

Le présent projet a pour objet le développement du back-office de l'application Wacdo. Ce back-office constitue l'interface d'administration centrale permettant de gérer l'ensemble des données de l'application, de traiter les commandes clients en temps réel et d'administrer les accès du personnel interne selon leurs responsabilités opérationnelles.

L'application vise à répondre à trois enjeux fondamentaux :

- **Efficacité opérationnelle** : centraliser en un point unique la gestion du catalogue, le suivi des commandes et l'administration des comptes, afin de réduire les erreurs de traitement et d'accélérer le service.
- **Sécurité des données** : contrôler strictement les accès selon le rôle de chaque utilisateur, protéger les données internes et garantir la traçabilité des actions sensibles.
- **Intégration avec le système de commande externe** : exposer une API fiable permettant au système tiers de récupérer le catalogue et de soumettre des commandes, sans exposer les données internes de l'établissement.

### 2.2 Objectifs fonctionnels détaillés

Le back-office doit permettre :

**Gestion des utilisateurs internes et des rôles**
Le système intègre une gestion complète des comptes utilisateurs internes, avec prise en compte des autorisations par rôle. Trois rôles sont définis, couvrant l'ensemble des responsabilités opérationnelles de l'établissement :

- Le rôle **Administration** dispose d'un accès complet au back-office. Son rôle principal est d'administrer l'établissement dans l'application : gestion des données du catalogue, des menus et de leurs compositions, ainsi que création, modification et désactivation des comptes utilisateurs internes. En raison de son niveau d'autorisation, il peut également réaliser les autres actions du back-office lorsque nécessaire.
- Le rôle **Preparation** est dédié au personnel en cuisine : consultation de la liste des commandes à traiter et déclaration des commandes comme préparées, permettant leur remise au client.
- Le rôle **Accueil** est dédié au personnel en salle et au centre d'appel : saisie de commandes au comptoir ou par téléphone, et remise des commandes préparées aux clients avec passage au statut `livrée`.

Aucune fonctionnalité du back-office n'est accessible sans authentification. Les droits de chaque action sont vérifiés côté serveur selon le rôle de l'utilisateur connecté.

**Gestion du catalogue — produits et menus**
Le back-office permet aux utilisateurs disposant du rôle Administration de gérer l'intégralité des données du catalogue : création et modification des produits (nom, description, prix, image, disponibilité, catégorie), gestion des menus et de leur composition (structure, parties obligatoires, options disponibles), et gestion des catégories servant à organiser l'offre. La disponibilité de chaque produit et menu est pilotable indépendamment de ses caractéristiques. Le catalogue actif constitue la référence unique exploitée par l'API externe et par la saisie manuelle des commandes.

**Saisie et traitement des commandes**
La saisie manuelle des commandes depuis le back-office est principalement assurée par le rôle Accueil — au comptoir ou lors d'une prise de commande par téléphone — en sélectionnant les produits, menus, quantités et options. Un administrateur peut également l'effectuer en raison de son accès complet. Les mêmes règles de validation et de calcul s'appliquent quelle que soit la source de la commande. Le rôle Accueil peut également identifier une commande préparée par son numéro de retrait et la déclarer `livrée` lors de la remise au client.

**Preparation des commandes**
Le personnel chargé de la confection des commandes accède via le rôle Preparation à la liste des commandes au statut `à préparer`, triées par heure de retrait prévue croissante. Une fois une commande confectionnée, le préparateur la déclare `préparée`, ce qui la rend disponible pour remise par le rôle Accueil.

**Sécurité applicative**
L'application met en œuvre les mesures de sécurité nécessaires à la protection des données internes : authentification des utilisateurs avec sessions sécurisées côté serveur, contrôle des autorisations à chaque action protégée, validation systématique des données reçues, protection des mots de passe et des données sensibles. Les mesures de sécurité techniques (algorithmes, configuration des sessions, protection contre la force brute, contrôle des fichiers) sont détaillées dans le CDC technique.

**Intégration API avec le système externe**
Le back-office expose une API REST permettant au système de prise de commande externe de récupérer le catalogue commandable — produits, menus et leurs options — et de soumettre des commandes. Toute commande reçue par API est soumise aux mêmes règles de validation métier que les commandes saisies manuellement. Les données internes (comptes, sessions, traces) ne sont pas exposées par cette API.

**Tests et validation**
Avant déploiement, une série de tests est effectuée pour vérifier que le back-office répond à l'ensemble des spécifications fonctionnelles, notamment les règles de gestion des commandes, les contrôles d'autorisation, la sécurité applicative et la bonne communication avec le système externe via l'API.

### 2.3 Résultat attendu

À l'issue du projet, le back-office Wacdo doit constituer un système opérationnel, sécurisé et maintenable, permettant à l'établissement de gérer son activité de restauration sans dépendance à des outils tiers non maîtrisés, avec une traçabilité complète des actions du personnel et une intégration stable avec le système de commande externe.

## 3 Périmètre

### 3.1 Périmètre inclus

Le présent projet couvre le développement complet du back-office Wacdo, incluant les domaines fonctionnels suivants :

**Administration du catalogue**
Gestion des catégories, produits (nom, description, prix, image, disponibilité), menus, compositions de menus (règles, parties obligatoires et facultatives), et options disponibles par partie de menu. Ces opérations sont réservées au rôle Administration.

**Gestion des comptes utilisateurs internes**
Création, modification et désactivation des comptes utilisateurs internes, avec assignation de rôle. Gestion de l'authentification, des sessions et du changement de mot de passe. Réservée au rôle Administration, à l'exception de l'authentification et du changement de mot de passe personnel qui sont accessibles à tous les rôles.

**Traitement des commandes — réception et saisie**
Réception des commandes transmises par le système externe via l'API, et saisie manuelle de commandes depuis le back-office, principalement par le rôle Accueil (au comptoir ou par téléphone). Dans les deux cas, la commande est validée côté serveur, son total est calculé depuis le catalogue actif, et elle entre dans le cycle opérationnel au statut `à préparer`.

**Traitement des commandes — préparation**
Accès du rôle Preparation à la liste des commandes au statut `à préparer`, triées par heure de retrait prévue croissante. Déclaration d'une commande `préparée` une fois confectionnée.

**Traitement des commandes — remise client**
Identification d'une commande par son numéro de retrait et déclaration `livrée` lors de la remise au client, par le rôle Accueil.

**Sécurité applicative**
Authentification des utilisateurs internes, gestion des sessions côté serveur, contrôle des autorisations à chaque action, validation de toutes les données reçues, protection des mots de passe et des données sensibles, traçabilité des actions sensibles. Les mesures techniques (algorithmes de hachage, configuration des sessions, protection contre la force brute, contrôle des fichiers envoyés) sont détaillées dans le CDC technique.

**API REST externe**
Exposition d'une API REST permettant au système de commande externe de consulter le catalogue commandable et de soumettre des commandes. Les endpoints publics (lecture catalogue, soumission commande) sont distincts des endpoints internes (actions back-office nécessitant une session authentifiée).

---

### 3.2 Périmètre exclu

Les éléments suivants sont explicitement hors périmètre du présent projet :

| Domaine exclu | Justification |
|---|---|
| Gestion du paiement | Prise en charge en amont par le système externe. Le back-office ne manipule ni les montants réglés ni les moyens de paiement. |
| Développement du système de commande externe | Ce composant est un système tiers existant, consommateur de l'API du back-office. |
| Logistique de cuisine | L'organisation interne de la cuisine est hors périmètre applicatif. Seul le changement de statut de commande est couvert. |
| Gestion des stocks, fournisseurs et comptabilité | Ces domaines relèvent d'outils de gestion distincts, non intégrés dans ce projet. |
| Notifications clients et programme de fidélité | Fonctionnalités non requises dans le cadre de ce projet. |

## 4 Acteurs

Le back-office Wacdo implique quatre acteurs distincts : trois utilisateurs internes authentifiés, différenciés par leur rôle, et un système externe qui interagit avec l'application via l'API REST. L'ensemble des utilisateurs internes accède au back-office sous authentification. Aucune action protégée n'est accessible sans session valide, et les droits de chaque acteur sont vérifiés côté serveur à chaque requête.

---

### 4.1 Utilisateur interne — rôle Administration

L'administrateur est l'acteur disposant du niveau d'accès le plus élevé dans le back-office. Sa mission principale est de maintenir l'intégrité des données de l'application : structure du catalogue, disponibilité des produits et des menus, gestion des comptes du personnel interne.

**Responsabilités :**
- Créer, modifier et désactiver les catégories, produits, menus et leurs compositions.
- Gérer la disponibilité de chaque produit et de chaque menu de façon indépendante.
- Créer, modifier et désactiver les comptes utilisateurs internes et leur rôle associé.

**Accès complet au système :**
Le rôle Administration est le rôle de pilotage du back-office. En plus de ses responsabilités propres, l'administrateur peut réaliser l'ensemble des actions disponibles dans le système lorsque la situation l'exige, sans que ces actions opérationnelles constituent son usage principal. Aucune fonctionnalité interne du back-office ne lui est inaccessible.

**Interactions avec le système :**
L'administrateur accède au back-office via un navigateur web. Il s'authentifie pour ouvrir une session sécurisée, puis opère sur les données du catalogue et des comptes via les interfaces d'administration. Les modifications apportées au catalogue sont immédiatement répercutées sur le catalogue actif consommé par l'API externe et par la saisie manuelle des commandes.

**Contraintes :**
La suppression physique d'un élément du catalogue déjà référencé dans une commande est interdite afin de préserver l'historique. Ces éléments doivent être désactivés ou archivés. Toutes les actions sensibles sont tracées (auteur, date, objet de l'action).

---

### 4.2 Utilisateur interne — rôle Preparation

Le préparateur est l'acteur opérationnel chargé de la confection des commandes en cuisine. Son périmètre d'accès est restreint aux seules informations nécessaires à l'exécution de cette mission.

**Responsabilités :**
- Consulter la liste des commandes ayant le statut `à préparer`, triées par heure de retrait prévue croissante.
- Consulter le détail d'une commande : lignes, produits, menus commandés, options sélectionnées.
- Déclarer une commande `préparée` une fois sa confection terminée.

**Interactions avec le système :**
Le préparateur accède au back-office via un navigateur web depuis le poste de cuisine. Il s'authentifie pour accéder à sa vue dédiée. Sa session ne lui donne accès ni aux données du catalogue, ni aux comptes, ni aux fonctionnalités d'accueil.

**Contraintes :**
Le rôle Preparation ne peut pas remettre une commande au client, modifier des données du catalogue, ni créer ou supprimer des comptes. La déclaration `préparée` n'est possible que sur une commande au statut `à préparer`.

---

### 4.3 Utilisateur interne — rôle Accueil

L'équipier d'accueil est l'acteur opérationnel chargé de la relation directe avec le client au comptoir et par téléphone. Il est le point d'entrée des commandes saisies manuellement dans le back-office, et l'acteur qui clôture le cycle de vie d'une commande au moment de sa remise.

**Responsabilités :**
- Saisir une commande pour un client (au comptoir ou par téléphone), en sélectionnant produits, menus, quantités et options depuis le catalogue actif.
- Identifier une commande préparée par son numéro de retrait et la déclarer `livrée` lors de la remise au client.

**Interactions avec le système :**
L'équipier d'accueil accède au back-office via un navigateur web depuis le poste comptoir. Il s'authentifie pour accéder aux fonctionnalités de saisie et de remise. Lors d'une saisie de commande, les mêmes règles de validation et de calcul s'appliquent qu'à une commande reçue par API.

**Contraintes :**
Le rôle Accueil ne peut pas déclarer une commande `préparée` ni accéder aux fonctionnalités d'administration du catalogue ou des comptes. La remise d'une commande (`livrée`) n'est possible que sur une commande au statut `préparée`. Si le numéro de retrait est inconnu, ambigu pour la journée ou associé à une commande déjà livrée, la remise est refusée.

---

### 4.4 Système externe — API REST

Le système de prise de commande externe (borne ou application client) est un acteur non humain qui communique avec le back-office via l'API REST exposée par le back-office.

**Responsabilités :**
- Récupérer le catalogue commandable : catégories, produits, menus, compositions, options et disponibilités.
- Soumettre les commandes validées par les clients, incluant les lignes, les options sélectionnées, les quantités et le numéro de retrait.

**Interactions avec le système :**
Les échanges s'effectuent exclusivement sur les endpoints publics de l'API. Le système externe ne dispose d'aucun accès aux fonctionnalités internes du back-office, aux comptes utilisateurs, aux sessions ou aux données de traçabilité interne. Chaque commande soumise est validée et recalculée côté serveur selon les règles métier en vigueur.

**Contraintes :**
Toute commande incohérente (sans numéro de retrait, sans ligne, avec produit indisponible, avec menu incomplet ou quantité invalide) est refusée par le back-office. Les prix transmis par le système externe ne font pas foi : le back-office recalcule le total depuis le catalogue actif.

---

### Synthèse

| Acteur | Type | Authentification | Domaines d'action |
|---|---|---|---|
| Administration | Interne | Session utilisateur | Accès complet au back-office : catalogue, comptes, disponibilités, saisie et traitement des commandes |
| Preparation | Interne | Session utilisateur | Consultation et traitement des commandes à préparer |
| Accueil | Interne | Session utilisateur | Saisie de commandes, remise client |
| Système externe | Applicatif | Endpoints publics API | Lecture catalogue, soumission de commandes |

## 5 Glossaire

**Utilisateur** = compte interne de l'application back-office, identifié par un identifiant et un mot de passe, associé à un rôle déterminant ses droits d'accès. Un utilisateur interne n'est pas un client de la borne.

**Rôle** = niveau d'autorisation attribué à un utilisateur interne, définissant les actions qu'il est autorisé à effectuer dans le back-office. Trois rôles existent : Administration, Accueil et Preparation.

**Administration** = rôle permettant la gestion complète des données de l'application (produits, menus, catégories) ainsi que la gestion des comptes utilisateurs internes.

**Accueil** = rôle permettant la saisie manuelle d'une commande (au comptoir ou par téléphone) et la remise d'une commande préparée à un client.

**Preparation** = rôle permettant la consultation de la liste des commandes à préparer et la déclaration d'une commande comme préparée.

**Produit** = élément du catalogue géré en back-office, identifié, nommé, décrit, tarifé, rattaché à une catégorie, associé à une image et doté d'un indicateur de disponibilité.

**Menu** = offre commerciale composée de plusieurs produits selon une structure prédéfinie, géré en back-office avec ses options disponibles.

**Catégorie** = regroupement logique de produits, géré en back-office, servant à organiser le catalogue.

**Disponibilité** = état d'un produit indiquant s'il peut être proposé ou non à la commande. Un produit indisponible ne doit pas être présenté au client ou commandable.

**Commande** = ensemble de lignes de commande validé, associé à un numéro de retrait, un type (sur place ou à emporter) et un statut évolutif. Une commande peut être reçue par l'application ou saisie manuellement depuis le back-office par un utilisateur autorisé à cette action.

**Ligne de commande** = élément constitutif d'une commande, correspondant à un produit simple ou un menu configuré, avec sa quantité et son prix.

**Statut de commande** = état courant d'une commande dans son cycle de vie opérationnel : `à préparer`, `préparée`, `livrée`.

**Numéro de retrait** = identifiant renseigné au moment de la prise de commande par la personne qui utilise le système de commande. Il permet d'associer une commande à un client au moment de la remise et n'est pas généré par le back-office.

**Application** = logiciel web centré sur un back-office accessible aux utilisateurs internes. C'est le système décrit dans ce document : il reçoit les requêtes, applique les règles de gestion, interagit avec la base de données et retourne les réponses.

**Authentification** = mécanisme de vérification de l'identité d'un utilisateur interne avant l'accès au back-office, reposant sur des sessions sécurisées côté serveur.

**Session** = données temporaires conservées côté serveur permettant de maintenir l'état authentifié d'un utilisateur entre ses requêtes, sans retransmettre les identifiants à chaque appel.

## 6 Règles de gestion

Les règles de gestion décrivent les contraintes métier que le back-office Wacdo doit respecter. Elles servent de base au MCD, au MCT, au CDC fonctionnel et aux tests conceptuels.

### Utilisateurs et rôles

RG-USER-001 — Un utilisateur est un compte interne

Un utilisateur correspond à un compte interne du back-office. Il ne représente pas un client de la borne de commande.

RG-USER-002 — Un utilisateur doit être identifié de manière unique

Chaque utilisateur doit posséder un identifiant unique permettant de le distinguer des autres comptes internes.

RG-USER-003 — Un utilisateur doit avoir un rôle

Chaque utilisateur interne doit être rattaché à un et un seul rôle.

RG-USER-004 — Les rôles disponibles sont limités

Le back-office distingue trois rôles : Administration, Preparation et Accueil.

RG-USER-005 — Seul un administrateur peut gérer les utilisateurs

La création, la modification ou la suppression d'un compte utilisateur est réservée aux utilisateurs ayant le rôle Administration.

RG-USER-006 — Un utilisateur doit être authentifié pour accéder au back-office

Aucune fonctionnalité du back-office ne doit être accessible à un utilisateur interne non authentifié.

RG-USER-007 — Les droits dépendent du rôle de l'utilisateur connecté

Les actions accessibles dans le back-office doivent être déterminées par le rôle de l'utilisateur connecté.

RG-USER-008 — Un utilisateur ne peut pas réaliser une action hors de son rôle

Une action non autorisée pour le rôle courant doit être refusée par l'application.

### Rôle Administration

RG-ADM-001 — L'administration gère les données du catalogue

Un utilisateur ayant le rôle Administration peut créer, modifier ou désactiver les données liées aux produits, aux catégories, aux menus et à leur composition.

RG-ADM-002 — L'administration gère la disponibilité des produits

Un utilisateur ayant le rôle Administration peut modifier l'état de disponibilité d'un produit.

RG-ADM-003 — L'administration gère les comptes internes

Un utilisateur ayant le rôle Administration peut créer, modifier ou désactiver les comptes internes du back-office.

RG-ADM-004 — L'administration peut accéder à toutes les fonctionnalités internes du back-office

Un utilisateur ayant le rôle Administration peut également réaliser les actions opérationnelles normalement utilisées par les rôles Preparation et Accueil.

### Rôle Preparation

RG-PREP-001 — La préparation consulte les commandes à préparer

Un utilisateur ayant le rôle Preparation peut consulter la liste des commandes qui doivent être préparées.

RG-PREP-002 — Les commandes à préparer sont triées par heure de retrait prévue croissante

La liste des commandes à préparer doit afficher en priorité les commandes dont l'heure de retrait prévue est la plus proche.

RG-PREP-003 — La préparation peut déclarer une commande préparée

Un utilisateur ayant le rôle Preparation peut changer le statut d'une commande lorsque sa préparation est terminée.

RG-PREP-004 — La préparation ne remet pas une commande au client

La remise d'une commande au client relève du rôle Accueil, pas du rôle Preparation.

RG-PREP-005 — La préparation ne gère pas le catalogue

Un utilisateur ayant le rôle Preparation ne peut pas créer, modifier ou supprimer les produits, les menus, les catégories ou les utilisateurs.

### Rôle Accueil

RG-ACC-001 — L'accueil peut saisir une commande

Un utilisateur ayant le rôle Accueil peut saisir une commande pour un client au comptoir ou par téléphone.

RG-ACC-002 — L'accueil peut remettre une commande préparée

Un utilisateur ayant le rôle Accueil peut déclarer livrée une commande qui a déjà été déclarée préparée.

RG-ACC-003 — L'accueil ne prépare pas une commande

Un utilisateur ayant le rôle Accueil ne peut pas déclarer une commande comme préparée.

RG-ACC-004 — L'accueil ne gère pas les données d'administration

Un utilisateur ayant le rôle Accueil ne peut pas créer, modifier ou supprimer les produits, les menus, les catégories ou les utilisateurs.

### Catalogue et produits

RG-CAT-001 — Une catégorie est identifiée de manière unique

Chaque catégorie doit posséder un identifiant unique.

RG-CAT-002 — Une catégorie possède un nom

Chaque catégorie doit posséder un nom exploitable dans le back-office et dans l'affichage du catalogue côté borne.

RG-CAT-003 — Une catégorie peut regrouper plusieurs produits

Une catégorie permet d'organiser les produits du catalogue et peut être associée à plusieurs produits.

RG-CAT-004 — Les suppressions du catalogue sont contrôlées

Une catégorie, un produit ou un menu déjà utilisé dans une commande ne doit pas être supprimé physiquement si cette suppression empêche la lecture historique de la commande. Il doit être désactivé, rendu indisponible ou archivé.

RG-PROD-001 — Un produit est identifié de manière unique

Chaque produit doit posséder un identifiant unique.

RG-PROD-002 — Un produit possède les informations minimales nécessaires

Chaque produit doit posséder au minimum un nom, une description, un prix, une image, une catégorie et un état de disponibilité.

RG-PROD-003 — Un produit appartient à une catégorie

Chaque produit doit être rattaché à une et une seule catégorie.

RG-PROD-004 — Le prix d'un produit doit être strictement positif

Un produit commandable ne peut pas avoir un prix nul ou négatif.

RG-PROD-005 — La disponibilité d'un produit détermine sa possibilité de commande

Un produit indisponible ne doit pas pouvoir être commandé dans l'application.

RG-PROD-006 — Le catalogue exploité doit refléter les données actives du back-office

Les produits et menus rendus disponibles par l'application doivent correspondre aux données enregistrées et disponibles dans le back-office.

RG-PROD-007 — La modification d'un produit ne modifie pas les commandes déjà enregistrées

Une commande doit conserver les informations nécessaires à sa lecture même si un produit est modifié après la validation de cette commande.

RG-PROD-008 — Un produit indisponible n'est plus proposé comme choix de menu

Un produit indisponible ne doit pas être proposé seul ni comme option disponible dans la composition d'un menu.

### Menus et composition

RG-MENU-001 — Un menu est une offre composée

Un menu est une offre commerciale composée de plusieurs produits selon une composition définie dans le back-office.

RG-MENU-002 — Un menu doit être identifié de manière unique

Chaque menu doit posséder un identifiant unique.

RG-MENU-003 — Un menu possède les informations minimales d'affichage

Chaque menu doit posséder au minimum un nom, une description, un prix, une image et un état de disponibilité.

RG-MENU-004 — Un menu doit posséder une composition

Un menu commandable doit préciser les produits ou types de produits qui le composent.

RG-MENU-005 — La composition d'un menu utilise uniquement des produits existants

Un menu ne peut pas référencer un produit absent du catalogue.

RG-MENU-006 — Les options disponibles d'un menu sont définies dans le back-office

Les choix proposés au client pour un menu doivent provenir des options enregistrées dans le back-office.

RG-MENU-007 — Un menu incomplet ne peut pas être commandé

Un menu dont la composition obligatoire n'est pas définie ne doit pas être exposé comme commandable.

RG-MENU-008 — Un menu indisponible ne peut pas être commandé

Un menu déclaré indisponible dans le back-office ne doit pas pouvoir être commandé.

RG-MENU-009 — Une option de menu doit être compatible avec le menu concerné

Une option ne peut être proposée que si elle est rattachée au menu ou à la règle de composition qui la prévoit.

RG-MENU-010 — La composition d'un menu est découpée en règles de composition

Un menu commandable doit être composé d'une ou plusieurs règles de composition définissant les parties attendues du menu, par exemple plat, boisson, accompagnement ou sauce.

RG-MENU-011 — Une règle de composition définit ses choix autorisés

Chaque règle de composition doit définir les produits ou options qui peuvent être choisis pour cette partie du menu.

RG-MENU-012 — Une règle de composition précise si le choix est obligatoire

Chaque règle de composition doit indiquer si le choix est obligatoire ou facultatif, ainsi que le nombre minimal et maximal de choix autorisés.

RG-MENU-013 — La disponibilité réelle d'un menu dépend de sa composition

Un menu ne peut être commandé que s'il est lui-même disponible et si chacune de ses règles obligatoires possède au moins un choix disponible.

### Commandes

RG-CMD-001 — Une commande peut provenir de deux sources

Une commande peut être reçue par l'application ou saisie manuellement dans le back-office par un utilisateur autorisé à cette action.

RG-CMD-002 — Une commande doit contenir au moins une ligne

Une commande vide ne peut pas être enregistrée.

RG-CMD-003 — Une commande doit être associée à un numéro de retrait

Chaque commande doit posséder un numéro de retrait renseigné lors de la prise de commande. Ce numéro n'est pas généré par le back-office et permet la remise au client.

RG-CMD-004 — Une commande ne déclenche pas de paiement

Le back-office ne gère pas le paiement. Le suivi d'une commande repose sur son numéro de retrait et son statut.

RG-CMD-005 — Une commande possède un statut métier unique

Chaque commande possède un seul statut parmi `à préparer`, `préparée` et `livrée`.

RG-CMD-006 — Une commande nouvellement créée est à préparer

Lorsqu'une commande est enregistrée, elle reçoit le statut `à préparer` et entre dans la liste des commandes à préparer.

RG-CMD-007 — La préparation et l'administration peuvent déclarer une commande préparée

Un utilisateur Preparation ou Administration peut faire passer une commande du statut `à préparer` au statut `préparée`.

RG-CMD-008 — L'accueil et l'administration peuvent déclarer une commande livrée

Un utilisateur Accueil ou Administration peut faire passer une commande du statut `préparée` au statut `livrée` lors de la remise au client.

RG-CMD-009 — Une commande livrée termine son cycle normal

Une commande déclarée livrée ne doit plus apparaître dans la liste des commandes à préparer ou à remettre.

RG-CMD-010 — Les transitions de statut doivent respecter le cycle de commande

Toute transition sautée, inverse ou non autorisée doit être refusée.

RG-CMD-011 — Les commandes à préparer doivent être consultables par les préparateurs

Les utilisateurs Preparation doivent accéder aux informations nécessaires à la confection des commandes.

RG-CMD-012 — Les commandes préparées doivent être consultables par l'accueil

Les utilisateurs Accueil doivent accéder aux informations nécessaires à la remise des commandes aux clients.

RG-CMD-013 — Une commande possède une date et une heure de référence pour le tri

Chaque commande doit conserver sa date et son heure de création. Si une heure de retrait est renseignée, elle sert au tri des commandes à préparer ; sinon le tri se fait par date de création croissante.

RG-CMD-014 — Le numéro de retrait doit permettre une remise sans ambiguïté

Chaque commande possède un identifiant technique unique et un numéro de retrait non vide. Le numéro de retrait doit permettre à l'accueil d'identifier sans ambiguïté une commande non livrée, au minimum sur une même journée d'activité.

RG-CMD-015 — Une commande conserve son origine

Chaque commande doit conserver sa source : réception externe ou saisie back-office. Lorsqu'elle est saisie depuis le back-office, l'utilisateur interne ayant créé la commande doit être conservé.

RG-CMD-016 — Les changements de statut sont tracés

Chaque passage à un nouveau statut doit conserver la date, le statut atteint et, lorsqu'il s'agit d'une action back-office, l'utilisateur interne responsable de l'action.

RG-CMD-017 — Le total d'une commande est figé à la validation

Le montant total d'une commande doit être calculé côté serveur et conservé au moment de l'enregistrement, même si les prix du catalogue changent ensuite.

### Lignes de commande

RG-LCMD-001 — Une ligne de commande appartient à une commande

Chaque ligne de commande doit être rattachée à une et une seule commande.

RG-LCMD-002 — Une ligne de commande concerne un élément commandé

Une ligne de commande correspond à un produit simple ou à un menu commandé.

RG-LCMD-003 — Une ligne de commande possède une quantité

Chaque ligne de commande doit avoir une quantité strictement positive.

RG-LCMD-004 — Une ligne de commande conserve le prix appliqué

Chaque ligne de commande doit conserver le prix appliqué au moment de la validation de la commande.

RG-LCMD-005 — Une ligne de commande conserve les choix utiles à la préparation

Lorsqu'une ligne correspond à un menu ou à une sélection avec options, les choix nécessaires à la préparation doivent être conservés.

RG-LCMD-006 — Une ligne de commande conserve les informations affichables de l'article

Chaque ligne de commande doit conserver le type d'article commandé, son nom, son prix unitaire appliqué et son total de ligne au moment de la validation.

RG-LCMD-007 — Une ligne de menu conserve les choix sélectionnés

Lorsqu'une ligne correspond à un menu, les produits ou options sélectionnés pour chaque partie du menu doivent être conservés avec leur nom, leur rôle dans le menu et leur éventuel supplément de prix.

RG-LCMD-008 — Une ligne de commande conserve la quantité validée

La quantité enregistrée sur une ligne de commande doit rester celle validée au moment de la création de la commande.

### API

RG-API-001 — L'API fournit les données commandables nécessaires à l'application

L'API expose les catégories, produits et menus commandables avec les champs nécessaires à leur exploitation : identifiant, nom, description, prix affiché, image, catégorie et disponibilité.

RG-API-002 — L'API fournit la configuration des menus

Pour chaque menu commandable, l'API expose les choix nécessaires à sa composition : groupes d'options, caractère obligatoire ou facultatif, nombre minimal et maximal de sélections, options autorisées, produits associés et éventuel supplément de prix.

RG-API-003 — L'API reçoit le détail d'une commande

Le back-end doit permettre la transmission d'une commande complète.

RG-API-004 — L'API contrôle et valorise la commande côté serveur

À la réception d'une commande, le back-end vérifie l'existence des produits et menus, leur disponibilité, la validité des quantités, la complétude des menus, la compatibilité des options et la présence du numéro de retrait. Les prix transmis ne font pas foi : le back-end recalcule les prix depuis le catalogue actif et conserve dans les lignes les libellés, choix et prix appliqués au moment de la validation.

RG-API-005 — L'API ne doit pas accepter une commande incohérente

Une commande sans numéro de retrait, sans ligne, avec quantité invalide, avec produit indisponible ou avec menu incomplet doit être refusée.

RG-API-006 — Les données exposées par l'API doivent être limitées au besoin externe

L'API ne doit pas exposer les informations internes inutiles, notamment les données d'authentification des utilisateurs.

RG-API-007 — Les endpoints externes et back-office sont distingués

Les endpoints externes permettent uniquement de consulter les données commandables et de créer une commande. Les actions d'administration, de préparation, d'accueil et de changement de statut nécessitent une session authentifiée et les droits du rôle correspondant.

### Sécurité

RG-SEC-001 — Les accès au back-office sont protégés

Les pages et actions du back-office doivent nécessiter une authentification.

RG-SEC-002 — Les mots de passe ne doivent pas être stockés en clair

Les mots de passe des utilisateurs internes doivent être conservés avec un hachage adapté aux mots de passe, jamais en clair.

RG-SEC-003 — Les sessions doivent être sécurisées

La session d'un utilisateur authentifié doit permettre d'identifier l'utilisateur connecté sans exposer son mot de passe.

RG-SEC-004 — Les autorisations doivent être vérifiées côté serveur

Le contrôle des droits ne doit pas reposer uniquement sur l'affichage ou le masquage d'éléments dans l'interface.

RG-SEC-005 — Les données reçues doivent être validées

Les données provenant du front, des formulaires du back-office ou de l'API doivent être contrôlées avant traitement.

RG-SEC-006 — Les données sensibles des utilisateurs internes doivent être protégées

Les informations liées aux comptes internes ne doivent pas être exposées aux utilisateurs non autorisés.

RG-SEC-007 — Les sessions expirées ou fermées ne donnent plus accès au back-office

Une session expirée ou explicitement fermée lors d'une déconnexion doit empêcher tout nouvel accès aux fonctionnalités protégées.

RG-SEC-008 — Les tentatives de connexion répétées doivent être limitées

L'application doit prévoir une protection contre les tentatives répétées d'authentification échouées.

RG-SEC-009 — Les images envoyées dans le back-office doivent être contrôlées

Les images associées aux produits ou menus doivent être validées avant enregistrement afin d'éviter l'envoi de fichiers non autorisés.

## 7 Parcours utilisateurs

Les parcours utilisateurs décrivent l'utilisation du back-office par les trois rôles internes prévus dans le sujet : Administration, Preparation et Accueil.

Chaque parcours commence par une authentification, car le back-office ne doit pas être accessible à un utilisateur non connecté.

Les commandes peuvent entrer dans le back-office par une réception externe ou par la saisie manuelle de l'Accueil. Dans les deux cas, elles rejoignent le même cycle métier : `à préparer`, `préparée`, `livrée`.

### Principes communs aux parcours

PU-COM-001 — Authentification obligatoire

Un utilisateur interne doit être authentifié avant d'accéder aux fonctionnalités du back-office.

PU-COM-002 — Refus d'authentification invalide

Si les identifiants sont invalides, si le compte est désactivé ou si la session est expirée, l'accès au back-office est refusé.

PU-COM-003 — Contrôle des autorisations

Avant chaque action protégée, l'application vérifie côté serveur que le rôle de l'utilisateur connecté possède le droit nécessaire.

PU-COM-004 — Refus des actions non autorisées

Si le rôle connecté ne possède pas le droit requis, l'action est refusée et aucune donnée métier n'est modifiée.

PU-COM-005 — Traçabilité des actions sensibles

Les actions sensibles sont tracées : authentification réussie ou échouée, création, modification, désactivation, création de commande, changement de statut et refus d'action non autorisée.

### Parcours Administration

**Acteur principal** : utilisateur ayant le rôle Administration.

**Objectif** : gérer les données du catalogue et les comptes utilisateurs internes.

**Précondition** : l'utilisateur possède un compte actif avec le rôle Administration.

PU-ADM-001 — Accès au back-office

L'administrateur ouvre l'application back-office et accède à l'écran de connexion.

PU-ADM-002 — Authentification

L'administrateur saisit ses identifiants. Si les identifiants sont valides, une session sécurisée est ouverte.

PU-ADM-003 — Accès au tableau de bord d'administration

Après connexion, l'administrateur accède à l'ensemble des fonctionnalités du back-office. Son usage principal reste l'administration du catalogue, des menus, des disponibilités et des comptes utilisateurs.

PU-ADM-004 — Gestion du catalogue

L'administrateur peut créer, consulter, modifier ou désactiver les catégories, produits, menus, compositions de menus et options disponibles.

PU-ADM-005 — Gestion de la disponibilité

L'administrateur peut rendre un produit ou un menu disponible ou indisponible selon les besoins opérationnels.

PU-ADM-006 — Gestion des utilisateurs internes

L'administrateur peut créer, consulter, modifier ou désactiver les comptes internes et leur rôle. Chaque compte interne possède un identifiant unique, un mot de passe sécurisé, un rôle et un état actif ou inactif.

PU-ADM-007 — Contrôle des données avant validation

Avant enregistrement, l'application vérifie que les données saisies sont complètes, cohérentes et autorisées.

PU-ADM-008 — Contrôle des images envoyées

Lors de l'ajout ou de la modification d'une image produit ou menu, l'application contrôle le type, la taille et le format du fichier avant enregistrement.

PU-ADM-009 — Enregistrement des modifications

Si les données sont valides, les modifications sont enregistrées en base de données, tracées avec l'utilisateur auteur, la date, l'action réalisée et l'objet concerné. Les produits, menus, compositions, options, prix, images et disponibilités deviennent exploitables par le back-office.

PU-ADM-010 — Conservation de l'historique

Si une catégorie, un produit ou un menu est déjà utilisé dans une commande, l'application empêche une suppression physique qui rendrait l'historique illisible. L'élément doit être désactivé ou archivé.

PU-ADM-011 — Déconnexion

L'administrateur peut se déconnecter. La session est fermée et l'accès aux pages protégées redevient impossible sans nouvelle authentification.

### Parcours Preparation

**Acteur principal** : utilisateur ayant le rôle Preparation.

**Objectif** : consulter les commandes à préparer et déclarer une commande préparée.

**Précondition** : l'utilisateur possède un compte actif avec le rôle Preparation.

PU-PREP-001 — Accès au back-office

Le préparateur ouvre l'application back-office et accède à l'écran de connexion.

PU-PREP-002 — Authentification

Le préparateur saisit ses identifiants. Si les identifiants sont valides, une session sécurisée est ouverte.

PU-PREP-003 — Accès à la liste des commandes à préparer

Après connexion, le préparateur accède à la liste des commandes ayant le statut `à préparer`, qu'elles proviennent d'une réception externe ou d'une saisie manuelle depuis le back-office.

PU-PREP-004 — Consultation des commandes triées

Les commandes à préparer sont affichées par heure de retrait prévue croissante. À défaut d'heure renseignée, elles sont triées par date de création croissante.

PU-PREP-005 — Consultation du détail d'une commande

Le préparateur ouvre une commande pour consulter les lignes, les produits, les menus, les options et les informations utiles à la confection.

PU-PREP-006 — Preparation physique de la commande

Le préparateur réalise la commande à partir des informations affichées dans le back-office.

PU-PREP-007 — Déclaration de la commande comme préparée

Lorsque la commande est terminée, le préparateur la déclare `préparée`. Seule une commande actuellement au statut `à préparer` peut être déclarée `préparée`.

PU-PREP-008 — Mise à jour du statut

L'application enregistre le passage du statut `à préparer` au statut `préparée`, trace l'action avec l'utilisateur auteur, la date, l'ancien statut et le nouveau statut, puis retire la commande de la liste des commandes à préparer.

PU-PREP-009 — Déconnexion

Le préparateur peut se déconnecter. La session est fermée et l'accès aux pages protégées redevient impossible sans nouvelle authentification.

### Parcours Accueil

**Acteur principal** : utilisateur ayant le rôle Accueil.

**Objectif** : saisir une commande au comptoir ou par téléphone, puis remettre une commande préparée au client.

**Précondition** : l'utilisateur possède un compte actif avec le rôle Accueil.

PU-ACC-001 — Accès au back-office

L'équipier d'accueil ouvre l'application back-office et accède à l'écran de connexion.

PU-ACC-002 — Authentification

L'équipier d'accueil saisit ses identifiants. Si les identifiants sont valides, une session sécurisée est ouverte.

PU-ACC-003 — Accès aux fonctions d'accueil

Après connexion, l'équipier d'accueil accède aux fonctionnalités de saisie de commande et de remise client.

PU-ACC-004 — Saisie d'une commande

L'équipier d'accueil peut créer une commande pour un client au comptoir ou par téléphone en sélectionnant les produits, menus, quantités et options demandés. Cette saisie utilise les mêmes règles métier qu'une commande reçue par l'application.

PU-ACC-005 — Validation de la commande saisie

Avant enregistrement, l'application vérifie que la commande contient au moins une ligne, que les produits et menus sont disponibles, que les compositions de menus et options sont valides, que les quantités sont correctes et qu'un numéro de retrait a été renseigné lors de la prise de commande. Les prix affichés ou transmis ne font pas foi : le serveur recalcule le total depuis le catalogue actif.

PU-ACC-006 — Enregistrement de la commande

Si la commande est valide, elle est enregistrée avec le statut `à préparer`, la création est tracée et la commande devient visible par les utilisateurs Preparation.

PU-ACC-007 — Consultation des commandes préparées

L'équipier d'accueil peut consulter les commandes ayant le statut `préparée`, afin de les remettre aux clients.

PU-ACC-008 — Identification de la commande à remettre

L'équipier d'accueil retrouve la commande à partir du numéro de retrait communiqué par le client. Si le numéro est inexistant, ambigu pour la journée d'activité ou associé à une commande déjà livrée, la remise est refusée.

PU-ACC-009 — Remise de la commande au client

Lorsque la commande est remise au client, l'équipier d'accueil la déclare `livrée`. Seule une commande actuellement au statut `préparée` peut être déclarée `livrée`.

PU-ACC-010 — Fin du cycle de commande

L'application enregistre le passage du statut `préparée` au statut `livrée`, trace l'action avec l'utilisateur auteur, la date, l'ancien statut et le nouveau statut, puis retire la commande des commandes à remettre.

PU-ACC-011 — Déconnexion

L'équipier d'accueil peut se déconnecter. La session est fermée et l'accès aux pages protégées redevient impossible sans nouvelle authentification.

## 8 Matrice des droits (rôle × fonctionnalité)

Une matrice des droits est un tableau qui croise les fonctionnalités de l'application avec les rôles autorisés à les utiliser. Elle permet de vérifier rapidement qui peut faire quoi dans le système, et surtout d'éviter qu'un rôle dispose d'actions qui ne lui appartiennent pas.

Par défaut, une action est considérée comme refusée si elle n'est pas explicitement autorisée.

| Fonctionnalité | Administration | Preparation | Accueil |
|---|---|---|---|
| Accéder au back-office | Oui | Oui | Oui |
| S'authentifier et se déconnecter | Oui | Oui | Oui |
| Gérer les comptes utilisateurs internes | Oui | Non | Non |
| Gérer les catégories | Oui | Non | Non |
| Gérer les produits, images et disponibilités | Oui | Non | Non |
| Gérer les menus, compositions et options | Oui | Non | Non |
| Consulter le catalogue exploitable pour la saisie | Oui | Non | Oui |
| Saisir une commande depuis le back-office | Oui | Non | Oui |
| Consulter les commandes à préparer | Oui | Oui | Non |
| Consulter le détail d'une commande à préparer | Oui | Oui | Non |
| Déclarer une commande `préparée` | Oui | Oui | Non |
| Consulter les commandes préparées à remettre | Oui | Non | Oui |
| Déclarer une commande `livrée` | Oui | Non | Oui |
| Modifier ou supprimer une commande validée | Non | Non | Non |


## 9 Exigences fonctionnelles

Les exigences fonctionnelles synthétisent, par domaine, ce que l'application doit obligatoirement réaliser. Elles constituent la référence vérifiable pour les tests et la recette.

### Authentification et sessions

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-AUTH-001 | Exiger l'authentification avant tout accès au back-office | `RG-USER-006`, `RG-SEC-001` |
| EF-AUTH-002 | Ouvrir une session sécurisée côté serveur après authentification réussie | `RG-SEC-003` |
| EF-AUTH-003 | Invalider la session à la déconnexion ou à l'expiration | `RG-SEC-007` |
| EF-AUTH-004 | Refuser l'accès en cas d'identifiants invalides, de compte inactif ou de session expirée | `PU-COM-002` |
| EF-AUTH-005 | Limiter les tentatives d'authentification répétées échouées | `RG-SEC-008` |

### Gestion des utilisateurs et droits

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-USR-001 | Permettre à l'Administration de créer, modifier et désactiver les comptes internes | `RG-ADM-003`, `RG-USER-005` |
| EF-USR-002 | Associer à chaque utilisateur un et un seul rôle parmi Administration, Preparation et Accueil | `RG-USER-003`, `RG-USER-004` |
| EF-USR-003 | Vérifier côté serveur les droits du rôle connecté avant chaque action protégée | `RG-SEC-004`, `RG-USER-008` |
| EF-USR-004 | Permettre à tout utilisateur connecté de modifier son propre mot de passe | `RG-SEC-002` |

### Gestion du catalogue

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-CAT-001 | Permettre à l'Administration de créer, modifier et désactiver les catégories, produits, menus, compositions de menus et options | `RG-ADM-001`, `RG-ADM-002` |
| EF-CAT-002 | Contrôler la cohérence des données catalogue avant enregistrement (prix > 0, catégorie existante, composition complète) | `RG-PROD-004`, `RG-MENU-005`, `RG-MENU-007` |
| EF-CAT-003 | Empêcher la suppression physique d'un élément du catalogue référencé dans une commande existante | `RG-CAT-004` |
| EF-CAT-004 | Exclure les produits et menus indisponibles de toute possibilité de commande | `RG-PROD-005`, `RG-MENU-008`, `RG-MENU-013` |
| EF-CAT-005 | Contrôler le type, le format et la taille des images avant enregistrement | `RG-SEC-009` |

### Gestion des commandes

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-CMD-001 | Enregistrer toute commande (saisie manuelle ou reçue par API) avec le statut `à préparer` | `RG-CMD-006` |
| EF-CMD-002 | Valider la cohérence d'une commande avant enregistrement : au moins une ligne, produits disponibles, compositions valides, numéro de retrait présent | `RG-CMD-002`, `RG-CMD-003`, `RG-API-005` |
| EF-CMD-003 | Recalculer le total côté serveur depuis le catalogue actif, quelle que soit la source | `RG-CMD-017` |
| EF-CMD-004 | Appliquer le cycle de vie des commandes (`à préparer` → `préparée` → `livrée`) et refuser toute transition non conforme | `RG-CMD-010` |
| EF-CMD-005 | Tracer chaque changement de statut avec la date, le nouveau statut et l'utilisateur responsable | `RG-CMD-016` |
| EF-CMD-006 | Conserver dans chaque ligne de commande le nom, le prix appliqué et les choix effectués au moment de la validation | `RG-LCMD-006`, `RG-LCMD-007`, `RG-PROD-007` |
| EF-CMD-007 | Afficher les commandes à préparer triées par heure de retrait prévue croissante | `RG-PREP-002`, `RG-CMD-013` |

### API externe

| ID | L'application doit… | RG de référence |
|---|---|---|
| EF-API-001 | Exposer le catalogue commandable (produits, menus, compositions, options) en lecture seule | `RG-API-001`, `RG-API-002` |
| EF-API-002 | Recevoir, valider et enregistrer les commandes externes selon les mêmes règles métier que la saisie manuelle | `RG-API-003`, `RG-API-004` |
| EF-API-003 | Refuser toute commande externe incohérente et retourner une réponse d'erreur explicite | `RG-API-005` |
| EF-API-004 | Distinguer les endpoints publics des endpoints internes nécessitant une session authentifiée | `RG-API-006`, `RG-API-007` |

---

## 10 Contraintes non fonctionnelles

Les contraintes non fonctionnelles définissent les exigences de qualité, d'architecture et de déploiement auxquelles le back-office doit se conformer.

### Architecture et développement

| ID | Contrainte |
|---|---|
| CNF-ARCH-001 | L'application est développée en langage serveur orienté objet avec utilisation de l'héritage, selon une architecture MVC. |
| CNF-ARCH-002 | La base de données est relationnelle (ex. MySQL, PostgreSQL), dédiée au projet, et cohérente avec le MCD validé. |
| CNF-ARCH-003 | Le code source est versionné et publié sur un dépôt GitHub accessible aux jurys. |

### Sécurité

| ID | Contrainte |
|---|---|
| CNF-SEC-001 | Les mots de passe sont stockés avec un algorithme de hachage adapté aux mots de passe (jamais en clair). Les détails techniques sont précisés dans le CDC technique. |
| CNF-SEC-002 | Les sessions sont gérées côté serveur. Les détails de sécurisation (durée, régénération d'identifiant, protection contre la fixation) sont précisés dans le CDC technique. |
| CNF-SEC-003 | Toutes les données reçues depuis l'extérieur (formulaires back-office, API) sont validées côté serveur avant traitement. |
| CNF-SEC-004 | Le contrôle des autorisations est appliqué côté serveur et ne repose pas sur le seul masquage de l'interface. |

### Tests et validation

| ID | Contrainte |
|---|---|
| CNF-TEST-001 | L'application fait l'objet de tests fonctionnels avant déploiement, couvrant les parcours utilisateurs, les transitions de statut, les règles de validation et les contrôles de droits. |
| CNF-TEST-002 | Les tests sont documentés et communicables aux jurys lors de la soutenance. |
| CNF-TEST-003 | Le candidat doit être en mesure de modifier son code en direct lors de la soutenance selon les demandes du jury. |

### Déploiement

| ID | Contrainte |
|---|---|
| CNF-DEPLOY-001 | L'application est déployée sur un serveur accessible lors de la soutenance. |
| CNF-DEPLOY-002 | Le dépôt GitHub contient les sources, les schémas conceptuels et physiques, les schémas fonctionnels et la documentation nécessaire. |

---

## 11 MCD 

/home/kurdant/Bureau/AcadéNice/Cours/ouii/BLOC2_wcdo/MCD_bloc2_wacdo.drawio

## 12 Livrables attendus

Conformément au référentiel du titre RNCP 37805 et aux attentes du jury :

| Livrable | Description |
|---|---|
| **MCD** — Modèle Conceptuel de Données | Diagramme entités-associations couvrant l'ensemble des entités métier du back-office. Argumenté lors de la soutenance. |
| **MCT** — Modèle Conceptuel des Traitements | Schéma fonctionnel décrivant les opérations métier par événements, couvrant au minimum le cycle de commande et l'authentification. |
| **MPD** — Modèle Physique de Données | Modèle relationnel dérivé du MCD, avec types de données, clés primaires et contraintes d'intégrité. |
| **Base de données** | Base de données relationnelle opérationnelle, cohérente avec le MPD, peuplée de données de test permettant la démonstration. |
| **Application back-office** | Application web fonctionnelle développée en OOP/MVC, déployée sur serveur, couvrant les fonctionnalités du présent CDC. |
| **API** | Endpoints exposant le catalogue et recevant les commandes, conformes aux exigences `EF-API-001` à `EF-API-004`. |
| **Dépôt GitHub** | Sources complètes, schémas, documentation et CDC, accessibles aux jurys avant la soutenance. |
| **Présentation jury** | Argumentation du modèle de données, des schémas conceptuels, des choix techniques et démonstration en direct avec capacité de modification à la demande du jury. |