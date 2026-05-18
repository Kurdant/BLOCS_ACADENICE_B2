// ====================================================
// STORAGE.JS — Gestion du localStorage (mémoire du navigateur)
// Rôle : Sauvegarder et lire toutes les données de la commande.
//        Le localStorage est un "bloc-notes" du navigateur qui
//        survit au rechargement de la page.
//        Tous les autres fichiers JS passent par ici pour lire/écrire.
// ====================================================

// Clés de stockage : noms des entrées dans le localStorage (centralisées ici pour éviter les fautes)
const KEYS = {
  orderType: "wacdo.orderType",           // "surPlace" ou "aEmporter"
  cart: "wacdo.cart",                     // Contenu du panier (objet JSON)
  withdrawNumber: "wacdo.withdrawNumber", // Numéro du chevalet (3 chiffres)
  commandeNumber: "wacdo.commandeNumber"  // Numéro de commande affiché sur la borne (01-99)
};

// Panier vide par défaut — utilisé à l'initialisation et en cas d'erreur de lecture
const EMPTY_CART = Object.freeze({ lignes: [], totalCentimes: 0 });

// Efface toutes les données de la commande en cours (remise à zéro complète)
export function clearOrderFlow() {
  localStorage.removeItem(KEYS.orderType);
  localStorage.removeItem(KEYS.cart);
  localStorage.removeItem(KEYS.withdrawNumber);
  localStorage.removeItem(KEYS.commandeNumber);
}
// --- NUMÉRO DE COMMANDE (affiché sur l'écran de la borne, format "01" à "99") ---

// Sauvegarde le numéro de commande après validation du format (exactement 2 chiffres)
export function saveCommandeNumber(commandeNumber) {
  if (!/^[0-9]{2}$/.test(commandeNumber)) {
    throw new Error("Numéro de commande invalide.");
  }
  localStorage.setItem(KEYS.commandeNumber, commandeNumber);
}

// Lit le numéro de commande depuis localStorage. Retourne null si absent ou invalide.
export function getCommandeNumber() {
  const n = localStorage.getItem(KEYS.commandeNumber);
  return /^[0-9]{2}$/.test(n || "") ? n : null;
}

// Supprime le numéro de commande du localStorage
export function clearCommandeNumber() {
  localStorage.removeItem(KEYS.commandeNumber);
}

// --- TYPE DE COMMANDE ("surPlace" ou "aEmporter") ---

// Sauvegarde le type de commande choisi sur la page d'accueil
export function saveOrderType(orderType) {
  if (!["surPlace", "aEmporter"].includes(orderType)) {
    throw new Error("Type de commande invalide.");
  }
  localStorage.setItem(KEYS.orderType, orderType);
}

// Lit le type de commande depuis localStorage. Retourne null si absent ou invalide.
export function getOrderType() {
  const orderType = localStorage.getItem(KEYS.orderType);
  return ["surPlace", "aEmporter"].includes(orderType) ? orderType : null;
}

// --- PANIER ---

// Crée un panier vide dans le localStorage (appelé au démarrage d'une nouvelle commande)
export function initializeCart() {
  saveCart({ ...EMPTY_CART, lignes: [] });
}

// Sauvegarde le panier complet en JSON dans le localStorage
export function saveCart(cart) {
  const safeCart = cart && Array.isArray(cart.lignes) ? cart : { ...EMPTY_CART, lignes: [] };
  localStorage.setItem(KEYS.cart, JSON.stringify(safeCart));
}

// Lit et retourne le panier depuis le localStorage.
// Si le panier est absent ou corrompu (JSON invalide), retourne un panier vide.
export function getStoredCart() {
  const rawCart = localStorage.getItem(KEYS.cart);
  if (!rawCart) {
    return { ...EMPTY_CART, lignes: [] };
  }

  try {
    const parsedCart = JSON.parse(rawCart);
    return parsedCart && Array.isArray(parsedCart.lignes) ? parsedCart : { ...EMPTY_CART, lignes: [] };
  } catch {
    return { ...EMPTY_CART, lignes: [] };
  }
}

// --- NUMÉRO DE RETRAIT (chevalet sur la table, format 3 chiffres ex: "042") ---

// Sauvegarde le numéro du chevalet après validation (doit être exactement 3 chiffres)
export function saveWithdrawNumber(withdrawNumber) {
  if (!/^\d{3}$/.test(withdrawNumber)) {
    throw new Error("Numéro de retrait invalide.");
  }
  localStorage.setItem(KEYS.withdrawNumber, withdrawNumber);
}

// Lit le numéro du chevalet depuis localStorage. Retourne null si absent ou invalide.
export function getWithdrawNumber() {
  const withdrawNumber = localStorage.getItem(KEYS.withdrawNumber);
  return /^\d{3}$/.test(withdrawNumber || "") ? withdrawNumber : null;
}