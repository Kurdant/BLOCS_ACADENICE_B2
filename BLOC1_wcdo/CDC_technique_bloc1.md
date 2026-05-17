# 7 CDC technique

## Sommaire de travail

1. Organisation générale de la logique front
2. Chargement et normalisation des données
3. Contrats de données front
4. Contrats des modules front
5. Composition technique d’un menu
6. Règles de calcul des prix
7. Validation finale et construction du payload JSON
8. Contrat d’envoi vers l’API fictive
9. Persistance locale et remise à zéro
10. Machine d’états front et transitions d’interface
11. Règles de validation technique
12. Accessibilité, responsive et conformité web
13. Tests conceptuels avant développement
14. Architecture fichiers

## 1 Organisation générale de la logique front

- **`commande.js`** : fichier orchestrateur principal. Il initialise la page, écoute les actions utilisateur, appelle les autres modules, décide quand charger le catalogue, ouvrir une modale, ajouter un élément au panier, mettre à jour l’affichage et passer à l’étape suivante du parcours ;
- **`catalog-service.js`** : charge les fichiers JSON du catalogue et fournit les données nécessaires à l’écran de commande. Il retourne les catégories, les produits, les menus et les éléments retrouvés par identifiant ;
- **`modal.js`** : gère l’ouverture, la fermeture et le déroulement des modales de sélection. Il affiche les choix nécessaires pour un menu ou un produit configurable, récupère les choix utilisateur et transmet le résultat à `commande.js` ;
- **`cart.js`** : gère tout ce qui concerne le panier. Il ajoute les lignes, supprime les lignes, recalcule le total et retourne l’état courant du panier ;
- **`storage.js`** : gère exclusivement la persistance locale. Il enregistre, relit et supprime les données utiles au parcours dans le `localStorage` ;
- **`payload-builder.js`** : récupère les données finales du parcours, construit le JSON final à envoyer puis déclenche l’envoi vers l’API fictive. Il prépare également les `MouvementStock` nécessaires si cette génération est retenue.

La répartition des responsabilités est fixée de manière stricte :

- `commande.js` pilote le parcours et contrôle le passage d’une étape à l’autre, sans devenir un fichier de calcul ou de génération JSON ;
- `catalog-service.js` fournit les données mais n’affiche rien dans le DOM ;
- `modal.js` gère l’interface de sélection, vérifie qu’une sélection est suffisamment renseignée avant confirmation et peut afficher le prix courant ;
- `cart.js` gère le panier, transforme une sélection confirmée en ligne panier et calcule les montants ;
- `payload-builder.js` construit le JSON final et déclenche l’envoi sans piloter l’interface ni la composition du panier.

Le fonctionnement général de la page commande est le suivant :

1. `commande.js` démarre la page et demande à `catalog-service.js` de charger les données du catalogue ;
2. `commande.js` demande à `storage.js` de restaurer les données locales encore valides ;
3. l’utilisateur choisit une catégorie et sélectionne un produit ou un menu ;
4. si l’élément choisi nécessite une configuration, `commande.js` délègue cette configuration à `modal.js` ;
5. `modal.js` retourne une sélection complète ou bloque la validation si la sélection est incomplète ;
6. `commande.js` demande à `cart.js` de transformer cette sélection en `LignePanier`, d’ajouter la ligne au panier et de recalculer le total ;
7. `commande.js` demande ensuite à `storage.js` de persister le panier mis à jour ;
8. lorsque le panier est validé, le parcours passe vers `chevalet.html` ;
9. `chevalet.js` récupère le numéro de retrait et le transmet à `payload-builder.js` ;
10. `payload-builder.js` construit le JSON final et déclenche l’envoi à l’API fictive ;
11. `commande.js` met enfin l’interface à jour avec les données courantes tant que l’utilisateur reste sur la page commande.

Cette organisation constitue la base technique de la page commande. Les autres pages du parcours, comme l’accueil, la saisie du numéro de retrait et la confirmation finale, s’appuient sur la même logique générale : un fichier de page pilote l’écran et délègue le métier, le stockage et les traitements spécialisés aux modules dédiés.

## 2 Chargement et normalisation des données

Le chargement du catalogue est assuré uniquement par `catalog-service.js`.

Au démarrage de `commande.js`, ce module charge les fichiers JSON fournis depuis leur URL directe sur le serveur.

Le chargement suit les règles suivantes :

- charger `categories.json` ;
- charger `produits.json` ;
- établir la correspondance entre une catégorie et son groupe de produits à partir de `categories.title` ;
- convertir les prix décimaux lus dans les JSON en centimes entiers ;
- conserver les chemins d’image pour l’affichage.

`catalog-service.js` fournit au minimum :

- la liste des catégories ;
- la liste des produits par groupe ;
- la liste des menus ;
- un produit ou un menu à partir de son identifiant.

Si le chargement échoue ou si la structure des JSON est invalide, le catalogue est considéré comme indisponible et l’interface doit passer dans un état d’erreur bloquant.

## 3 Contrats de données front

Le front ne modifie jamais directement les objets JSON fournis.

Le front crée ensuite ses propres objets de travail pour gérer :

- les catégories chargées ;
- les items catalogue normalisés ;
- les brouillons de sélection ;
- les lignes de panier ;
- la commande courante ;
- les mouvements de stock éventuels.

Les identifiants du catalogue sont conservés dans les objets de travail dès qu’ils sont nécessaires à la validation, à la construction du payload final ou à la gestion du stock.

### 3.1 Données sources JSON

Les fichiers `categories.json` et `produits.json` sont des données fournies par le sujet.

Règles associées :

- le front lit ces fichiers sans modifier leur structure ;
- aucun champ technique n’est ajouté dans les fichiers JSON source ;
- les informations complémentaires nécessaires au parcours sont portées par les objets de travail du front.

### 3.2 `CategorieCatalogue`

Champs utilisés :

- `id` : identifiant numérique de la catégorie ;
- `title` : nom de la catégorie et nom du groupe de produits correspondant ;
- `image` : chemin de l’image associée.

### 3.3 `ItemCatalogue`

Un item du catalogue correspond directement à un objet lu dans un groupe de `produits.json`.

Champs utilisés :

- `id` : identifiant numérique de l’item ;
- `nom` : nom affiché ;
- `prix` : prix décimal fourni par le JSON source ;
- `image` : chemin de l’image associée.

Règles associées :

- l’appartenance à un groupe (`menus`, `burgers`, `boissons`, `frites`, `encas`, `desserts`, `sauces`, `salades`, `wraps`) est déterminée par le groupe source dans `produits.json` ;
- cette appartenance n’est pas ajoutée comme champ dans l’objet source ;
- la conversion du prix en centimes est un traitement de calcul et non une modification du contrat source.

### 3.4 `BrouillonSelection`

Le brouillon de sélection représente l’état temporaire d’un produit ou d’un menu pendant l’utilisation de `modal.js`.

Champs utilisés :

- `itemId` : identifiant de l’item sélectionné ;
- `itemType` : type de sélection, `produit` ou `menu` ;
- `quantite` : quantité demandée ;
- `typeMenu` : `bestOf` ou `maxiBestOf` si la sélection concerne un menu, sinon `null` ;
- `accompagnementId` : identifiant de l’accompagnement sélectionné ou `null` ;
- `boissonId` : identifiant de la boisson sélectionnée ou `null` ;
- `tailleBoisson` : `normale`, `grande` ou `null` ;
- `sauceId` : identifiant de l’unique sauce optionnelle sélectionnée ou `null` ;
- `prixCalculeCentimes` : prix calculé courant de la sélection.

Règle associée :

- tant que ce brouillon n’est pas complet et validé, il ne devient pas une ligne de panier ;
- le brouillon ne stocke pas les noms d’affichage des choix ;
- le brouillon ne stocke aucun identifiant de burger, car le titre du menu sélectionné suffit à identifier le menu commandé.

### 3.5 `LignePanier`

Une ligne de panier représente une sélection validée et ajoutée au panier.

Champs utilisés :

- `lineId` : identifiant unique de ligne ;
- `itemId` : identifiant de l’item ajouté ;
- `itemType` : `produit` ou `menu` ;
- `nom` : nom conservé au moment de l’ajout ;
- `quantite` : quantité de la ligne ;
- `configuration` : objet de configuration ou `null` ;
- `prixUnitaireCentimes` : prix unitaire calculé de la ligne ;
- `prixTotalCentimes` : prix total calculé de la ligne.

Règles associées :

- une ligne de panier ne stocke pas d’image ;
- pour une ligne de type `menu`, le champ `nom` conserve le titre du menu choisi et `configuration` contient `typeMenu`, `accompagnementId`, `boissonId`, `tailleBoisson` et `sauceId` ;
- pour une ligne de type `produit`, `configuration` peut rester à `null` si aucune sélection complémentaire n’est nécessaire ;
- la ligne de panier conserve l’état exact de la sélection au moment de l’ajout.

### 3.6 `Panier`

Le panier représente l’état courant des sélections validées.

Champs utilisés :

- `lignes` : tableau de `LignePanier` ;
- `totalCentimes` : total calculé du panier.

### 3.7 `CommandeCourante`

La commande courante représente l’état final utile avant construction du payload JSON définitif.

Champs utilisés :

- `orderType` : type de commande, `surPlace` ou `aEmporter` ;
- `withdrawNumber` : numéro de retrait stocké comme chaîne de 3 chiffres ;
- `panier` : panier courant validé.

Cette structure sert de base à la validation finale et à la construction du payload de commande.

### 3.8 `MouvementStock`

Un mouvement de stock représente un élément physique à décrémenter à partir d’une ligne de panier validée.

Champs utilisés :

- `sourceLineId` : identifiant de la ligne de panier d’origine ;
- `stockType` : type d’élément décrémenté, `produit`, `burger`, `accompagnement`, `boisson` ou `sauce` ;
- `itemId` : identifiant de l’élément décrémenté ;
- `quantite` : quantité à décrémenter ;
- `taille` : `normale`, `grande` ou `null` selon le type d’élément.

Règles associées :

- un mouvement de stock est généré à partir d’une ligne de panier ou d’une commande validée ;
- une ligne de type `menu` peut produire plusieurs mouvements de stock ;
- le prix et les noms d’affichage ne font pas partie du mouvement de stock.

## 4 Contrats des modules front

Cette section définit le contrat public des modules utilisés par le front.

Règle générale d’orchestration :

- `commande.js` est le seul module autorisé à coordonner les autres modules de la page commande ;
- les autres modules ne se pilotent pas entre eux, sauf `modal.js` qui peut interroger `catalog-service.js` pour charger les choix nécessaires à une configuration et chevalet.js qui envoie le numéro de chevalet.
- chaque module reçoit des données, produit un résultat, puis laisse `commande.js` décider de la suite du parcours.

Chaque module est décrit par :

- son rôle ;
- les données qu’il reçoit ;
- les données qu’il retourne ;
- les modules qu’il peut appeler ;
- ce qu’il ne doit pas faire.

### 4.1 `commande.js`

**Rôle**

`commande.js` orchestre la page de commande et le parcours utilisateur sur cet écran.

**Reçoit**

- les événements utilisateur ;
- les données du catalogue fournies par `catalog-service.js` ;
- les données restaurées par `storage.js` ;
- les résultats de sélection provenant de `modal.js` ;
- les résultats de calcul et de mise à jour provenant de `cart.js`.

**Retourne**

- un déclenchement d’action vers le bon module ;
- une demande de mise à jour de l’interface ;
- une transition vers l’étape suivante du parcours.

**Peut appeler**

- `catalog-service.js` ;
- `modal.js` ;
- `cart.js` ;
- `storage.js`.

Utilisation autorisée :

- appeler `catalog-service.js` pour charger le catalogue, lister une catégorie ou retrouver un item ;
- appeler `modal.js` pour ouvrir, mettre à jour ou fermer une modale de sélection ;
- appeler `cart.js` pour transformer une sélection complète en `LignePanier`, ajouter une ligne, supprimer une ligne ou relire le panier courant ;
- appeler `storage.js` pour restaurer, sauvegarder ou supprimer les données locales du parcours.

**Ne doit pas**

- charger directement les JSON ;
- calculer directement les montants détaillés d’une ligne de panier ;
- transformer seul une sélection en `LignePanier` ;
- écrire directement dans le `localStorage` ;
- construire seul le payload final.

### 4.2 `catalog-service.js`

**Rôle**

`catalog-service.js` charge le catalogue et fournit les catégories, produits et menus exploitables par la page commande.

**Reçoit**

- les sources `categories.json` et `produits.json`.

**Retourne**

- une liste de `CategorieCatalogue` ;
- une liste de `ItemCatalogue` pour une catégorie donnée ;
- un `ItemCatalogue` retrouvé par identifiant ;
- une erreur bloquante si le catalogue est indisponible ou invalide.

**Peut appeler**

- uniquement les fonctions natives de chargement HTTP nécessaires à la lecture des JSON.

Utilisation autorisée :

- charger `categories.json` ;
- charger `produits.json` ;
- retourner les données du catalogue à `commande.js` ;
- retourner à `modal.js` les listes de boissons, d’accompagnements ou de sauces nécessaires à l’affichage de la configuration.

**Ne doit pas**

- manipuler le DOM ;
- gérer le panier ;
- calculer les prix métier ;
- écrire dans le `localStorage`.

### 4.3 `modal.js`

**Rôle**

`modal.js` gère l’ouverture, la fermeture et le déroulement des modales de sélection.

**Reçoit**

- l’item sélectionné ;
- les données de choix utiles au produit ou au menu ;
- le `BrouillonSelection` courant.

**Retourne**

- un `BrouillonSelection` mis à jour vers `commande.js`;
- une confirmation de sélection ;
- une annulation de sélection.

**Peut appeler**

- ses propres fonctions d’ouverture, fermeture et mise à jour d’interface ;
- `catalog-service.js` uniquement pour récupérer les choix à afficher dans la modale.

Utilisation autorisée :

- afficher les choix nécessaires dans la modale ;
- appeler `catalog-service.js` pour récupérer les boissons, les accompagnements ou les sauces à proposer ;
- mettre à jour le `BrouillonSelection` courant ;
- vérifier que tous les choix obligatoires sont renseignés avant d’autoriser la confirmation ;
- mettre à jour le `prixCalculeCentimes` courant nécessaire à l’affichage ;
- retourner ce brouillon à `commande.js`.

**Ne doit pas**

- ajouter directement une ligne au panier ;
- appeler directement `cart.js` ;
- appeler directement `storage.js` ;
- écrire dans le `localStorage` ;
- décider seule de la validation finale d’une commande.

### 4.4 `cart.js`

**Rôle**

`cart.js` maintient l’état courant du panier et transforme une sélection confirmée en ligne exploitable.

**Reçoit**

- un `ItemCatalogue` sélectionné ;
- un `BrouillonSelection` complet si une configuration a été faite ;
- une demande de suppression de ligne ;
- un `Panier` restauré si nécessaire.

**Retourne**

- une `LignePanier` créée ;
- un `Panier` mis à jour ;
- un `totalCentimes` recalculé.

**Peut appeler**

- aucun autre module front.

Utilisation autorisée :

- transformer une sélection complète en `LignePanier` ;
- calculer `prixUnitaireCentimes` et `prixTotalCentimes` ;
- ajouter une ligne au panier ;
- supprimer une ligne existante ;
- recalculer le total du panier ;
- retourner le `Panier` courant à `commande.js`.

**Ne doit pas**

- appeler directement `storage.js` ;
- appeler directement `catalog-service.js` ;
- appeler directement `payload-builder.js` ;
- charger le catalogue ;
- décider du passage à l’étape suivante du parcours ;
- manipuler directement le DOM.

### 4.5 `storage.js`

**Rôle**

`storage.js` gère la persistance locale des données temporaires du parcours.

**Reçoit**

- un `Panier` ;
- un `orderType` ;
- un `withdrawNumber` ;
- une demande d’effacement.

**Retourne**

- les données locales restaurées si elles sont présentes et valides ;
- `null` ou une structure vide si aucune donnée exploitable n’est disponible.

**Peut appeler**

- le `localStorage` du navigateur.

Utilisation autorisée :

- enregistrer `orderType` ;
- enregistrer un `Panier` ;
- enregistrer `withdrawNumber` ;
- relire ces données ;
- supprimer ces données.

**Ne doit pas**

- décider des règles métier ;
- recalculer les prix ;
- valider une sélection ;
- appeler directement `cart.js` ;
- appeler directement `payload-builder.js` ;
- manipuler l’interface.

### 4.6 `payload-builder.js`

**Rôle**

`payload-builder.js` récupère les données finales du parcours, construit le JSON final de commande puis déclenche l’envoi vers l’API fictive.
Il supporte un mode `mock` activé par défaut pour l’examen et un mode `http` conservé pour un branchement ultérieur vers une vraie route serveur.

**Reçoit**

- `withdrawNumber`.

**Retourne**

- un résultat d’envoi de commande ;
- une liste éventuelle de `MouvementStock`.

**Peut appeler**

- `storage.js` ;
- les fonctions natives d’envoi HTTP nécessaires à l’appel API final.

Utilisation autorisée :

- être appelé uniquement par `chevalet.js` ;
- recevoir `withdrawNumber` déjà contrôlé ;
- relire `orderType` et `panier` via `storage.js` ;
- construire en interne la structure finale de commande ;
- construire le JSON conforme au contrat d’envoi ;
- simuler par défaut une réponse de succès de type `202` en mode `mock`, sans dépendre d’un service externe réel ;
- utiliser un appel HTTP réel uniquement lorsqu’un mode `http` explicite est activé ;
- générer les `MouvementStock` éventuels à partir du panier final ;
- déclencher l’envoi vers l’API fictive ;
- retourner le résultat d’envoi à `chevalet.js`.

**Ne doit pas**

- manipuler le DOM ;
- ouvrir une modale ;
- appeler directement `cart.js` ;
- être déclenché directement par un événement d’interface ;
- lire ou écrire dans le `localStorage` ;
- charger le catalogue ;
- recalculer les prix avec une logique différente de celle déjà appliquée dans le panier.

### 4.7 `chevalet.js`

**Rôle**

`chevalet.js` gère uniquement la saisie du numéro final et sa transmission à `payload-builder.js`.

**Reçoit**

- les événements utilisateur de la page `chevalet.html` ;
- les données restaurées par `storage.js` ;
- le résultat d’envoi retourné par `payload-builder.js`.

**Retourne**

- une confirmation de saisie valide ;
- une transmission du numéro de chevalet à `payload-builder.js` ;
- une redirection vers `remerciement.html` en cas de succès.

**Peut appeler**

- `storage.js` ;
- `payload-builder.js`.

Utilisation autorisée :

- restaurer `withdrawNumber` si nécessaire ;
- valider que le numéro saisi contient exactement trois chiffres ;
- transmettre à `payload-builder.js` uniquement le numéro de chevalet saisi ;
- récupérer le résultat d’envoi retourné par `payload-builder.js` ;
- gérer le succès ou l’échec retourné.

**Ne doit pas**

- recharger le catalogue ;
- modifier la composition du panier ;
- recalculer les prix avec une logique différente de `cart.js` ;
- manipuler la modale de `commande.html` ;
- construire le JSON final ;
- déclencher lui-même l’envoi API.



## 5 Composition technique d’un menu

Pour le front, un menu n’est pas un produit ajoutable directement. Il doit d’abord être complété dans la modale.

Règles retenues :

- la sélection d’un item du groupe menus dans `produits.json` ouvre obligatoirement la modale de configuration ;
- le titre du menu sélectionné suffit à identifier le menu commandé, sans ajouter de lien technique vers un burger ;
- l’accompagnement est choisi dans `frites` ou `salades` ;
- la boisson est choisie dans `boissons` ;
- la sauce optionnelle est choisie dans `sauces` et se limite à une seule sélection ;
- le type de menu vaut obligatoirement `bestOf` ou `maxiBestOf` ;
- un menu ne peut être ajouté au panier que si tous les choix obligatoires sont renseignés ;
- si un choix obligatoire manque, la confirmation dans la modale est bloquée.

## 6 Règles de calcul des prix

Le calcul des montants reste volontairement simple et est réparti entre la modale et le panier.

La répartition retenue est la suivante :

- `modal.js` peut mettre à jour `prixCalculeCentimes` pour afficher à l’utilisateur le montant courant de sa sélection ;
- `cart.js` calcule le prix définitif d’une ligne au moment de son ajout et recalcule le total après chaque modification du panier ;
- `payload-builder.js` réutilise les montants déjà présents dans le panier et ne recalcule pas le total avec une autre logique.

Les règles de calcul restent les suivantes :

- il travaille uniquement en centimes entiers ;
- pour un produit simple, le prix de base provient du JSON fourni ;
- pour une boisson commandée seule en grande taille, un supplément unique de 50 centimes est ajouté au prix de base ;
- pour un menu `bestOf`, le prix appliqué est le prix de base du menu, sans supplément ;
- pour un menu `maxiBestOf`, un supplément unique de 50 centimes est ajouté au prix de base du menu ;
- ce supplément Maxi Best Of ne doit jamais être appliqué une seconde fois lors du calcul d’une ligne ou lors de la construction du payload final ;
- les accompagnements vendus seuls utilisent uniquement le prix défini dans le JSON fourni ;
- le prix d’une ligne est calculé à partir de son contenu et de sa quantité ;
- le total du panier est produit par somme des lignes ;
- le montant affiché est mis à jour après chaque modification du panier ;
- le montant transmis dans la commande JSON reprend le total validé du panier.

Le montant total n’est jamais une donnée de référence stockée dans le `localStorage`.

## 7 Validation finale et construction du payload JSON

Lors de la validation finale, le flux retenu est le suivant :

- `chevalet.js` récupère le numéro saisi et vérifie qu’il contient exactement trois chiffres ;
- `chevalet.js` transmet uniquement ce numéro à `payload-builder.js` ;
- `payload-builder.js` relit les données utiles persistées du parcours (`orderType`, `cart`) ;
- `payload-builder.js` vérifie que le panier n’est pas vide ;
- `payload-builder.js` vérifie que chaque ligne est complète et cohérente ;
- `payload-builder.js` contrôle que le total du panier correspond bien à la somme des lignes ;
- `payload-builder.js` construit le payload JSON final conforme au contrat d’envoi défini en section 8 ;
- `payload-builder.js` déclenche l’envoi à l’API fictive ;
- `chevalet.js` récupère ensuite le résultat d’envoi.

Si une incohérence est détectée, la validation finale est bloquée et l’interface doit afficher un état ou un message d’erreur approprié.

## 8 Contrat d’envoi vers l’API fictive

L’envoi final est déclenché uniquement par `payload-builder.js`, après réception du numéro de chevalet transmis par `chevalet.js`.

Le contrat d’envoi retenu est le suivant :

- méthode HTTP : `POST` ;
- route documentaire cible : `/envoie/commande` ;
- mode d’exécution par défaut : `mock`, configurable dans `payload-builder.js` via une constante dédiée ;
- en mode `mock`, aucun service externe n’est requis : le front simule un envoi asynchrone court et retourne un résultat de succès `{ ok: true, status: 202 }` ;
- en mode `http`, l’URL d’envoi réelle est configurée dans `payload-builder.js` et appelée avec `fetch` ;
- en-tête obligatoire : `Content-Type: application/json` ;
- corps de la requête : payload JSON retourné par `payload-builder.js` ;
- aucun envoi ne doit être tenté tant que `orderType`, `panier` et `withdrawNumber` ne sont pas valides.

Le rôle des deux fichiers est strictement séparé :

- `chevalet.js` collecte et valide la saisie finale ;
- `chevalet.js` transmet uniquement le numéro de chevalet à `payload-builder.js` ;
- `payload-builder.js` relit les autres données utiles, génère le JSON final et déclenche l’envoi ;
- `chevalet.js` n’écrit pas lui-même la structure JSON finale et ne déclenche pas lui-même l’envoi.

Réponse attendue en cas de succès :

- en mode `mock`, un statut simulé `202` ;
- en mode `http`, un code HTTP `202` ;
- la réception de cette réponse valide la transmission de la commande ;
- aucun contenu JSON de retour n’est obligatoire pour poursuivre le parcours.

Réponse attendue en cas d’échec :

- en mode `mock`, un échec seulement si un scénario de test le force explicitement ;
- en mode `http`, code HTTP différent de `202`, absence de réponse exploitable ou erreur réseau ;
- réponse éventuelle pouvant contenir un message d’erreur ;
- en cas d’échec, l’utilisateur reste sur `chevalet.html` avec son panier et son numéro déjà saisi.

Règles techniques d’envoi :

- le mode retenu par défaut pour l’examen est `mock` ;
- la route `/envoie/commande` reste le point d’entrée documentaire de référence pour un futur branchement serveur ;
- l’appel API est réalisé avec `fetch` depuis `payload-builder.js` uniquement en mode `http` ;
- en mode `http`, l’URL configurée doit accepter les requêtes `POST` depuis le navigateur ;
- en mode `http`, un délai maximal de 10 secondes est retenu pour considérer l’appel comme échoué ;
- un seul envoi doit être lancé par action utilisateur de confirmation ;
- le passage à `remerciement.html` n’est autorisé qu’après une réponse de succès.

## 9 Persistance locale et remise à zéro

Le service de stockage local applique les règles suivantes :

- l’état courant utile au parcours est maintenu en mémoire pendant l’exécution de la page ;
- le `localStorage` sert de persistance complémentaire entre les pages et en cas de rechargement ;
- au démarrage d’une nouvelle commande depuis l’écran d’accueil, le `localStorage` existant est vidé avant d’enregistrer le nouveau type de commande ;
- initialiser `wacdo.cart` avec une structure vide si la clé n’existe pas encore ;
- réécrire `wacdo.cart` après chaque modification du panier ;
- `wacdo.cart` peut contenir `totalCentimes` pour faciliter l’affichage après restauration ;
- lors de la restauration, `cart.js` doit recalculer `totalCentimes` à partir des lignes puis remplacer toute valeur stockée devenue incohérente ;
- enregistrer `wacdo.orderType` et `wacdo.withdrawNumber` uniquement lorsque ces données existent ;
- stocker `wacdo.withdrawNumber` comme une chaîne de 3 chiffres exactement afin de conserver d’éventuels zéros initiaux ;
- restaurer ces données au rechargement si elles sont encore valides ;
- supprimer les données persistées lors d’un abandon explicite du parcours ;
- supprimer les données persistées après validation finale réussie ;
- supprimer les données persistées lors d’une remise à zéro de la borne ou d’une inactivité prolongée.

La donnée de référence du total reste toujours la somme recalculée des lignes du panier, et non la valeur brute éventuellement retrouvée dans le `localStorage`.

## 10 Machine d’états front et transitions d’interface

Les états techniques sont pilotés par le contrôleur d’interface.

Le fonctionnement retenu repose sur une machine d’états front simple, adaptée à un parcours multi-pages sans sur-complexifier l’implémentation.

Flux complet du parcours :

1. `index.html` vide le `localStorage`, enregistre le type de commande et ouvre `commande.html` ;
2. `commande.html` charge le catalogue, restaure le panier si besoin et affiche les produits ;
3. l’utilisateur ajoute des produits ou des menus au panier ;
4. quand le panier est valide, le parcours passe à `chevalet.html` ;
5. `chevalet.html` récupère le numéro saisi et vérifie qu’il contient trois chiffres ;
6. `chevalet.js` transmet uniquement `withdrawNumber` à `payload-builder.js` ;
7. `payload-builder.js` relit `orderType` et `panier` via `storage.js`, génère le JSON final puis déclenche l’envoi à l’API ;
8. `payload-builder.js` retourne à `chevalet.js` le résultat d’envoi ;
9. en cas de succès, la page suivante est `remerciement.html` ; en cas d’échec, l’utilisateur reste sur `chevalet.html` avec un message d’erreur.

| Page | État | Événement | Garde | Action | État suivant |
|---|---|---|---|---|---|
| `index.html` | `ACCUEIL_PRET` | choix du mode | `surPlace` ou `aEmporter` | vider le `localStorage`, stocker `orderType`, puis charger `commande.html` | `commande.html` |
| `commande.html` | `CATALOGUE_CHARGEMENT` | chargement du catalogue réussi | données valides | normaliser le catalogue et restaurer le panier valide si nécessaire | `PANIER_VIDE` ou `PANIER_ACTIF` |
| `commande.html` | `CATALOGUE_CHARGEMENT` | chargement du catalogue échoué | aucune | afficher une erreur bloquante | `CATALOGUE_ERREUR` |
| `commande.html` | `CATALOGUE_ERREUR` | réessayer | aucune | relancer le chargement Ajax | `CATALOGUE_CHARGEMENT` |
| `commande.html` | `PANIER_VIDE` | sélectionner un produit ou un menu | produit existant | ouvrir l’ajout direct ou la configuration de sélection | `CONFIG_SELECTION` ou `PANIER_ACTIF` |
| `commande.html` | `PANIER_VIDE` | valider le panier | panier vide | afficher un blocage | `PANIER_VIDE` |
| `commande.html` | `PANIER_ACTIF` | supprimer une ligne | ligne existante | supprimer la ligne, recalculer le total et persister le panier | `PANIER_VIDE` ou `PANIER_ACTIF` |
| `commande.html` | `PANIER_ACTIF` | valider le panier | panier complet | persister l’état final du panier et passer à la page suivante | `chevalet.html` |
| `commande.html` | `CONFIG_SELECTION` | modifier un choix | choix autorisé | mettre à jour le brouillon de sélection | `CONFIG_SELECTION` |
| `commande.html` | `CONFIG_SELECTION` | ajouter au panier | sélection complète | calculer la ligne, ajouter au panier et fermer la modale | `PANIER_ACTIF` |
| `commande.html` | `CONFIG_SELECTION` | ajouter au panier | sélection incomplète | afficher un blocage sans ajouter de ligne | `CONFIG_SELECTION` |
| `commande.html` | `CONFIG_SELECTION` | annuler | aucune | supprimer le brouillon de sélection et fermer la modale | `PANIER_VIDE` ou `PANIER_ACTIF` |
| `chevalet.html` | `CHEVALET_PRET` | saisir le numéro | exactement trois chiffres | stocker `withdrawNumber` en mémoire et dans le `localStorage` comme chaîne | `CHEVALET_VALIDE` |
| `chevalet.html` | `CHEVALET_PRET` | saisir le numéro | saisie invalide | afficher un blocage | `CHEVALET_PRET` |
| `chevalet.html` | `CHEVALET_VALIDE` | confirmer la commande | panier valide et numéro valide | transmettre le numéro de chevalet à `payload-builder.js`, qui construit le JSON final puis déclenche l’envoi | `ENVOI_COMMANDE` |
| `chevalet.html` | `ENVOI_COMMANDE` | réponse API réussie | succès | nettoyer les données temporaires et poursuivre le parcours | `remerciement.html` |
| `chevalet.html` | `ENVOI_COMMANDE` | réponse API en erreur | échec | conserver le panier et le numéro, puis afficher une erreur | `CHEVALET_PRET` |
| `remerciement.html` | `REMERCIEMENT` | nouvelle commande | aucune | charger `index.html` | `index.html` |

Cette machine d’états impose les règles suivantes :

- la variable mémoire porte l’état courant de la page ;
- le `localStorage` sert uniquement de persistance complémentaire ;
- aucun brouillon de sélection incomplet ne doit être ajouté au panier ;
- aucun écran de confirmation ne doit être affiché tant que l’envoi API n’a pas réussi.

## 11 Règles de validation technique

Les validations techniques minimales à appliquer sont les suivantes :

- `categories.json` et `produits.json` doivent être chargés sans erreur avant affichage du catalogue ;
- tout item manipulé par le front doit exister dans les données du catalogue ;
- aucune sélection incomplète ne peut être transformée en `LignePanier` ;
- aucun passage à `chevalet.html` n’est autorisé si le panier est vide ;
- aucun envoi API n’est autorisé si `withdrawNumber` ne contient pas exactement trois chiffres ;
- aucun envoi API n’est autorisé si le total recalculé du panier diffère de la somme des lignes ;
- toute erreur de chargement ou d’envoi doit produire un message visible pour l’utilisateur.

## 12 Accessibilité, responsive et conformité web

Le front doit respecter les règles suivantes :

- la structure HTML doit rester sémantique et valide ;
- tous les boutons et champs interactifs doivent être utilisables au clavier ;
- le focus clavier doit toujours être visible ;
- chaque image utile à la compréhension doit porter un texte alternatif pertinent ;
- les messages d’erreur doivent être lisibles et compréhensibles sans ambiguïté ;
- les contrastes de couleur doivent rester suffisants pour conserver une bonne lisibilité ;
- l’interface doit être prévue en priorité pour l’écran de borne et rester lisible sans rupture majeure sur des largeurs plus petites ;
- aucune information essentielle ne doit dépendre uniquement de la couleur.

## 13 Tests conceptuels avant développement

Les tests conceptuels minimaux attendus avant livraison sont les suivants :

- choisir `surPlace` depuis `index.html` vide bien le `localStorage`, enregistre `orderType` puis ouvre `commande.html` ;
- choisir `aEmporter` produit le même comportement avec la bonne valeur stockée ;
- charger `commande.html` avec un catalogue valide affiche les catégories et les produits attendus ;
- tenter d’ajouter un menu incomplet dans la modale reste bloqué ;
- ajouter un menu complet crée une `LignePanier` correcte et met à jour le total ;
- supprimer une ligne met à jour le panier et persiste le nouvel état ;
- saisir un numéro de chevalet invalide bloque la confirmation finale ;
- envoyer une commande valide avec succès redirige vers `remerciement.html` ;
- simuler un échec API conserve le panier et le numéro sur `chevalet.html` avec un message d’erreur.


## 14 Architecture fichiers

### 14.1 Architecture de la page `commande.html`

La page `commande.html` constitue l’écran principal de prise de commande.  
Elle regroupe, dans une même page :

- la navigation par catégories et menus ;
- l’affichage dynamique des produits ;
- le panier affiché dans la colonne de droite ;
- la modale de configuration d’une sélection, avec ses étapes internes.

Cette page est pilotée par un fichier d’entrée unique, `commande.js`, qui joue un rôle de contrôleur de page.  
Pour ce bloc, une architecture volontairement simple est retenue. La séparation en dossiers `state`, `domain`, `ui` ou `utils` n’est pas nécessaire.

#### Arborescence retenue pour `commande.html`

```text
FRONT/
├── HTML/
│   └── commande.html
├── CSS/
│   ├── components/
│   │   ├── categories.css
│   │   ├── products.css
│   │   ├── cart.css
│   │   └── modal.css
│   └── pages/
│       └── commande.css
├── JS/
│   ├── commande.js
│   ├── catalog-service.js
│   ├── modal.js
│   ├── cart.js
│   ├── storage.js
│   └── payload-builder.js
└── Json_donnees/wacdo/
    ├── categories.json
    ├── produits.json
    ├── burgers/
    ├── boissons/
    ├── frites/
    └── ...
```

Les chemins indiqués dans le code partent des pages placées dans `FRONT/HTML`.

```text
../CSS/pages/commande.css
../CSS/components/categories.css
../JS/commande.js
../Json_donnees/wacdo/categories.json
../Json_donnees/wacdo/produits.json
```

#### Principe de découpage retenu

Le découpage JS retenu repose sur six fichiers seulement :

- `commande.js` pilote la page ;
- `catalog-service.js` charge le catalogue ;
- `modal.js` gère la sélection utilisateur dans la modale ;
- `cart.js` construit les lignes de panier et calcule les montants ;
- `storage.js` persiste les données locales utiles ;
- `payload-builder.js` construit le JSON final et déclenche l’envoi pour la dernière étape du parcours.

Ce découpage est suffisant pour ce projet parce que :

- le nombre d’écrans reste limité ;
- les règles métier sont peu nombreuses ;
- une architecture plus découpée ajouterait surtout de la complexité documentaire et technique.

#### Rôle de chaque fichier principal

##### `FRONT/HTML/commande.html`

Ce fichier contient la structure HTML de la page principale de prise de commande.

Il doit au minimum porter :

- la zone de navigation des catégories et menus ;
- la zone d’affichage des produits ;
- la colonne latérale du panier ;
- le conteneur de la modale de configuration ;
- les zones de messages ou d’erreur éventuels ;
- les boutons d’action principaux de la page.

##### `FRONT/CSS/pages/commande.css`

Ce fichier définit la mise en page propre à `commande.html`.

Il gère notamment :

- la structure générale de la page ;
- la répartition entre le catalogue et le panier ;
- les espacements propres à l’écran ;
- les adaptations responsive de la page commande.

##### `FRONT/CSS/components/categories.css`

Ce fichier contient le style de la zone des catégories et des onglets de navigation.

##### `FRONT/CSS/components/products.css`

Ce fichier contient le style des cartes produit et des blocs d’affichage du catalogue.

##### `FRONT/CSS/components/cart.css`

Ce fichier contient le style du panier latéral affiché dans `commande.html`.

##### `FRONT/CSS/components/modal.css`

Ce fichier contient le style de la modale de configuration de sélection.

##### `FRONT/JS/commande.js`

Ce fichier est le contrôleur principal de `commande.html`.

Son rôle est de :

- démarrer la page ;
- demander le chargement du catalogue ;
- restaurer les données utiles ;
- ouvrir et fermer la modale ;
- demander l’ajout ou la suppression d’une ligne dans le panier ;
- contrôler le passage d’une étape à l’autre jusqu’à `chevalet.html`.

##### `FRONT/JS/catalog-service.js`

Ce fichier charge `categories.json` et `produits.json`, prépare les données utiles et les retourne à `commande.js`.

##### `FRONT/JS/modal.js`

Ce fichier gère la modale de sélection.

Il permet notamment de :

- afficher les choix nécessaires ;
- mettre à jour le `BrouillonSelection` ;
- vérifier qu’une sélection est complète avant confirmation ;
- mettre à jour le prix affiché de la sélection.

##### `FRONT/JS/cart.js`

Ce fichier gère le panier.

Il permet notamment de :

- transformer une sélection en `LignePanier` ;
- calculer le prix d’une ligne ;
- ajouter une ligne ;
- supprimer une ligne ;
- recalculer le total du panier.

##### `FRONT/JS/storage.js`

Ce fichier gère exclusivement la persistance locale via `localStorage`.

##### `FRONT/JS/payload-builder.js`

Ce fichier reçoit le numéro de chevalet transmis par `chevalet.js`, relit les autres données utiles via `storage.js`, construit le JSON final puis déclenche l’envoi.

Il peut également produire les `MouvementStock` associés si ce besoin est conservé dans l’implémentation.


#### Principe de fonctionnement global

Le fonctionnement retenu pour `commande.html` est le suivant :

1. `commande.js` initialise la page ;
2. `catalog-service.js` charge les données nécessaires ;
3. `storage.js` recharge les données utiles déjà persistées ;
4. `modal.js` gère les choix de configuration et retourne une sélection complète ;
5. `cart.js` transforme cette sélection en ligne de panier et met à jour le total ;
6. `payload-builder.js` construit le JSON final puis déclenche l’envoi lors de la validation finale ;
7. `commande.js` orchestre l’ensemble sans multiplier les fichiers techniques intermédiaires.

### 14.2 Architecture des autres pages

En complément de `commande.html`, l’application repose sur trois autres pages plus simples : l’accueil, la saisie du chevalet et le remerciement final.

#### Arborescence retenue pour les autres pages

```text
FRONT/
├── HTML/
│   ├── index.html
│   ├── chevalet.html
│   └── remerciement.html
├── CSS/
│   └── pages/
│       ├── index.css
│       ├── chevalet.css
│       └── remerciement.css
└── JS/
    ├── accueil.js
    └── chevalet.js
```

#### Principe de découpage retenu

Le découpage des autres pages suit les règles suivantes :

- chaque page possède son fichier HTML ;
- chaque page possède son fichier CSS dédié ;
- un fichier JavaScript n’est ajouté que lorsqu’une page porte une vraie logique de parcours ;
- `remerciement.html` ne contient pas de logique métier et ne nécessite donc pas de fichier JavaScript dédié.

#### `index.html`

Cette page correspond à l’écran d’accueil de la borne.  
Elle permet au client de choisir entre **sur place** et **à emporter**.

Fichiers associés :

- `FRONT/HTML/index.html`
- `FRONT/JS/accueil.js`
- `FRONT/CSS/pages/index.css`

Rôle de `accueil.js` :

- initialiser l’écran d’accueil ;
- écouter le choix du type de commande ;
- vider complètement le `localStorage` au démarrage d’un nouveau parcours ;
- enregistrer le type de commande sélectionné ;
- préparer l’entrée dans la page `commande.html`.

Cette page ne manipule ni le panier, ni le catalogue, ni le payload final.

#### `chevalet.html`

Cette page correspond à l’écran de saisie du numéro de chevalet ou de retrait.

Fichiers associés :

- `FRONT/HTML/chevalet.html`
- `FRONT/JS/chevalet.js`
- `FRONT/CSS/pages/chevalet.css`

Rôle de `chevalet.js` :

- afficher le formulaire ou le clavier de saisie ;
- enregistrer le numéro saisi ;
- vérifier que la saisie est exploitable ;
- transmettre uniquement le numéro de chevalet à `payload-builder.js` ;
- récupérer le résultat d’envoi retourné ;
- déclencher la poursuite du parcours en cas de succès.

Cette page peut réutiliser des modules partagés tels que :

- `storage.js` ;
- `payload-builder.js`.

Cette page ne recharge pas le catalogue et ne gère pas la composition du panier.

#### `remerciement.html`

Cette page correspond à l’écran final de remerciement.

Fichiers associés :

- `FRONT/HTML/remerciement.html`
- `FRONT/CSS/pages/remerciement.css`

Cette page ne nécessite pas de fichier JavaScript dédié.

Rôle de `remerciement.html` :

- afficher le message final de confirmation ;
- confirmer à l’utilisateur que la commande a bien été prise en compte ;
- proposer un bouton `Nouvelle commande` redirigeant vers `index.html`.

Cette page ne lit pas le `localStorage` et ne reconstruit aucune donnée de commande.

#### Vue d’ensemble finale de l’architecture

L’architecture retenue pour l’ensemble de l’application est donc la suivante :

- `FRONT/HTML/index.html` pour l’entrée dans le parcours ;
- `FRONT/HTML/commande.html` pour la prise de commande complète ;
- `FRONT/HTML/chevalet.html` pour la saisie du numéro final ;
- `FRONT/HTML/remerciement.html` pour la fin du parcours ;
- `FRONT/JS/accueil.js`, `FRONT/JS/commande.js` et `FRONT/JS/chevalet.js` comme fichiers JS d’entrée ;
- aucun fichier JS dédié pour `FRONT/HTML/remerciement.html` ;
- des modules partagés simples pour le catalogue, la modale, le panier, le stockage et la construction du JSON final.