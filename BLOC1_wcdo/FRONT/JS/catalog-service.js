// ====================================================
// CATALOG-SERVICE.JS — Chargement et gestion du catalogue produits
// Rôle : Aller chercher les produits et catégories depuis les fichiers JSON,
//        les organiser proprement, et les fournir au reste de l'application.
//        C'est la "bibliothèque" de tous les produits disponibles.
// ====================================================

const CATEGORIES_URL = "../Json_donnees/wacdo/categories.json";
const PRODUCTS_URL = "../Json_donnees/wacdo/produits.json";
const ASSET_ROOT = "../Json_donnees/wacdo"; // Dossier racine des images produits

// Catalogue chargé en mémoire — null tant que loadCatalog() n'a pas été appelé
let catalogState = null;

// Charge les catégories et les produits depuis les fichiers JSON (en parallèle).
// Normalise la structure et stocke le résultat dans catalogState.
export async function loadCatalog() {
  // On charge les deux fichiers JSON simultanément pour aller plus vite
  const [categoriesResponse, productsResponse] = await Promise.all([
    fetch(CATEGORIES_URL),
    fetch(PRODUCTS_URL)
  ]);

  if (!categoriesResponse.ok || !productsResponse.ok) {
    throw new Error("Catalogue indisponible.");
  }

  const [sourceCategories, sourceProducts] = await Promise.all([
    categoriesResponse.json(),
    productsResponse.json()
  ]);

  // Transforme les données brutes du JSON en structure utilisable par l'app
  catalogState = normalizeCatalog(sourceCategories, sourceProducts);
  return catalogState;
}

// Retourne le catalogue chargé en mémoire.
// Lance une erreur si loadCatalog() n'a pas encore été appelé.
export function getCatalog() {
  if (!catalogState) {
    throw new Error("Catalogue non chargé.");
  }
  return catalogState;
}

// Retourne la liste de tous les produits d'un groupe donné (ex: "burgers", "boissons")
export function getItemsByGroup(group) {
  return getCatalog().groups[group] || [];
}

// Cherche un produit par son groupe et son id numérique. Retourne null si non trouvé.
export function findItem(group, itemId) {
  return getItemsByGroup(group).find((item) => item.id === Number(itemId)) || null;
}

// Cherche un produit par sa clé unique "groupe:id" (ex: "burgers:3").
// Plus rapide que findItem car utilise une Map (recherche directe, pas de boucle).
export function findItemByKey(itemKey) {
  return getCatalog().itemsByKey.get(itemKey) || null;
}

// Met la première lettre d'un nom de groupe en majuscule pour l'affichage.
// Ex: "burgers" → "Burgers"
export function formatGroupLabel(group) {
  return group ? group.charAt(0).toUpperCase() + group.slice(1) : "";
}

// Transforme les données brutes du JSON en structure interne de l'application.
// Résultat : { categories, groups, itemsByKey }
function normalizeCatalog(sourceCategories, sourceProducts) {
  if (!Array.isArray(sourceCategories) || !sourceProducts || typeof sourceProducts !== "object") {
    throw new Error("Structure catalogue invalide.");
  }

  const groups = {};            // Produits organisés par groupe : { "burgers": [...], "boissons": [...] }
  const itemsByKey = new Map(); // Accès direct par clé unique : "burgers:3" → objet produit

  Object.entries(sourceProducts).forEach(([group, items]) => {
    if (!Array.isArray(items)) {
      throw new Error(`Groupe invalide: ${group}`);
    }

    groups[group] = items.map((item) => {
      validateProduct(item, group);
      const normalizedItem = {
        id: Number(item.id),
        nom: item.nom,
        prix: Number(item.prix),
        prixCentimes: toCentimes(item.prix),      // Prix en centimes pour éviter les erreurs float
        image: normalizeImagePath(item.image),     // Chemin image absolu
        group,
        itemKey: `${group}:${item.id}`,            // Clé unique ex: "burgers:3"
        itemType: group === "menus" ? "menu" : "produit" // Distingue menus et produits simples
      };
      itemsByKey.set(normalizedItem.itemKey, normalizedItem);
      return normalizedItem;
    });
  });

  const categories = sourceCategories.map((category) => {
    validateCategory(category);
    return {
      id: Number(category.id),
      title: category.title,
      image: normalizeImagePath(category.image),
      items: groups[category.title] || [] // Produits appartenant à cette catégorie
    };
  });

  return { categories, groups, itemsByKey };
}

// Valide qu'une catégorie a bien toutes ses données obligatoires (id, title, image)
function validateCategory(category) {
  if (!category || !Number.isFinite(Number(category.id)) || !category.title || !category.image) {
    throw new Error("Catégorie incomplète.");
  }
}

// Valide qu'un produit a bien toutes ses données obligatoires (id, nom, prix, image)
function validateProduct(item, group) {
  if (!item || !Number.isFinite(Number(item.id)) || !item.nom || !Number.isFinite(Number(item.prix)) || !item.image) {
    throw new Error(`Produit incomplet dans ${group}.`);
  }
}

// Corrige les chemins d'image qui commencent par "/" pour les rendre accessibles depuis le HTML.
// Ex: "/images/burger.png" → "../Json_donnees/wacdo/images/burger.png"
export function normalizeImagePath(imagePath) {
  if (!imagePath) {
    return "";
  }
  return imagePath.startsWith("/") ? `${ASSET_ROOT}${imagePath}` : imagePath;
}

// Convertit un prix en euros (ex: 9.99) en nombre entier de centimes (ex: 999).
// Indispensable car JavaScript peut donner 0.1 + 0.2 = 0.30000000000000004 sinon.
export function toCentimes(value) {
  return Math.round(Number(value) * 100);
}

// Formate un nombre de centimes en chaîne d'affichage en euros.
// Ex: 999 → "9,99 €"
export function formatPrice(centimes) {
  return new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR" }).format(centimes / 100);
}