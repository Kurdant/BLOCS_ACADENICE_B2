const CATEGORIES_URL = "../Json_donnees/wacdo/categories.json";
const PRODUCTS_URL = "../Json_donnees/wacdo/produits.json";
const ASSET_ROOT = "../Json_donnees/wacdo";

let catalogState = null;

export async function loadCatalog() {
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

  catalogState = normalizeCatalog(sourceCategories, sourceProducts);
  return catalogState;
}

export function getCatalog() {
  if (!catalogState) {
    throw new Error("Catalogue non chargé.");
  }
  return catalogState;
}

export function getItemsByGroup(group) {
  return getCatalog().groups[group] || [];
}

export function findItem(group, itemId) {
  return getItemsByGroup(group).find((item) => item.id === Number(itemId)) || null;
}

export function findItemByKey(itemKey) {
  return getCatalog().itemsByKey.get(itemKey) || null;
}

export function formatGroupLabel(group) {
  return group ? group.charAt(0).toUpperCase() + group.slice(1) : "";
}

function normalizeCatalog(sourceCategories, sourceProducts) {
  if (!Array.isArray(sourceCategories) || !sourceProducts || typeof sourceProducts !== "object") {
    throw new Error("Structure catalogue invalide.");
  }

  const groups = {};
  const itemsByKey = new Map();

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
        prixCentimes: toCentimes(item.prix),
        image: normalizeImagePath(item.image),
        group,
        itemKey: `${group}:${item.id}`,
        itemType: group === "menus" ? "menu" : "produit"
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
      items: groups[category.title] || []
    };
  });

  return { categories, groups, itemsByKey };
}

function validateCategory(category) {
  if (!category || !Number.isFinite(Number(category.id)) || !category.title || !category.image) {
    throw new Error("Catégorie incomplète.");
  }
}

function validateProduct(item, group) {
  if (!item || !Number.isFinite(Number(item.id)) || !item.nom || !Number.isFinite(Number(item.prix)) || !item.image) {
    throw new Error(`Produit incomplet dans ${group}.`);
  }
}

export function normalizeImagePath(imagePath) {
  if (!imagePath) {
    return "";
  }
  return imagePath.startsWith("/") ? `${ASSET_ROOT}${imagePath}` : imagePath;
}

export function toCentimes(value) {
  return Math.round(Number(value) * 100);
}

export function formatPrice(centimes) {
  return new Intl.NumberFormat("fr-FR", { style: "currency", currency: "EUR" }).format(centimes / 100);
}