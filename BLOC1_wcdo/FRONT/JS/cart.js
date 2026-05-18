// ====================================================
// CART.JS — Gestion du panier en mémoire vive
// Rôle : Ajouter, retirer des articles, et calculer les prix.
//        Travaille sur la version "active" du panier (RAM).
//        storage.js gère la sauvegarde persistante dans localStorage.
// ====================================================

// Supplément tarifaire pour les boissons grandes et les menus Maxi Best Of (+0,50€)
export const SUPPLEMENT_GRANDE_CENTIMES = 50;

// Panier courant en mémoire (vide au démarrage)
let currentCart = { lignes: [], totalCentimes: 0 };

// Remplace le panier courant par un panier existant (ex: chargé depuis localStorage).
// Filtre les lignes invalides et recalcule les totaux.
export function setCart(cart) {
  const lignes = Array.isArray(cart?.lignes) ? cart.lignes.filter(isUsableLine) : [];
  currentCart = recalculateCart({ lignes, totalCentimes: 0 });
  return getCart();
}

// Retourne une copie du panier courant (copie pour éviter les modifications accidentelles)
export function getCart() {
  return {
    lignes: currentCart.lignes.map((line) => ({ ...line, configuration: cloneConfiguration(line.configuration) })),
    totalCentimes: currentCart.totalCentimes
  };
}

// Ajoute un article au panier à partir d'un item catalogue et de la sélection du client.
// La sélection peut contenir : quantité, type de menu, accompagnement, boisson, sauce, taille.
export function addSelectionToCart(item, selection) {
  const line = createLineFromSelection(item, selection);
  currentCart = recalculateCart({ lignes: [...currentCart.lignes, line], totalCentimes: 0 });
  return getCart();
}

// Retire une ligne du panier en la cherchant par son identifiant unique (lineId).
export function removeLineFromCart(lineId) {
  currentCart = recalculateCart({
    lignes: currentCart.lignes.filter((line) => line.lineId !== lineId),
    totalCentimes: 0
  });
  return getCart();
}

// Retourne true si le panier est vide (aucune ligne), false sinon.
export function isCartEmpty(cart = currentCart) {
  return !Array.isArray(cart.lignes) || cart.lignes.length === 0;
}

// Recalcule le prix total de chaque ligne et le grand total du panier.
// Formule : prixTotalLigne = prixUnitaire × quantite ; total = somme de toutes les lignes.
export function recalculateCart(cart) {
  const lignes = Array.isArray(cart?.lignes) ? cart.lignes.map((line) => ({
    ...line,
    prixTotalCentimes: line.prixUnitaireCentimes * line.quantite
  })) : [];
  const totalCentimes = lignes.reduce((total, line) => total + line.prixTotalCentimes, 0);
  return { lignes, totalCentimes };
}

// Crée un objet "ligne de panier" structuré à partir d'un article et de la sélection du client.
function createLineFromSelection(item, selection) {
  if (!item || !selection || !Number.isInteger(selection.quantite) || selection.quantite < 1) {
    throw new Error("Sélection invalide.");
  }

  const prixUnitaireCentimes = calculateUnitPrice(item, selection);

  return {
    lineId: createLineId(),               // Identifiant unique généré pour cette ligne
    itemId: item.id,
    itemKey: item.itemKey,                // Ex: "burgers:3"
    itemType: item.itemType,              // "menu" ou "produit"
    group: item.group,
    nom: item.nom,
    quantite: selection.quantite,
    configuration: buildConfiguration(selection), // Détails du choix (taille, accompagnement, etc.)
    prixUnitaireCentimes,
    prixTotalCentimes: prixUnitaireCentimes * selection.quantite
  };
}

// Calcule le prix unitaire d'un article selon son type et ses options :
// - Menu Maxi Best Of → prix de base + 0,50€
// - Boisson grande    → prix de base + 0,50€
// - Produit simple    → prix de base (aucun supplément)
function calculateUnitPrice(item, selection) {
  if (item.itemType === "menu") {
    assertMenuSelection(selection);
    return item.prixCentimes + (selection.typeMenu === "maxiBestOf" ? SUPPLEMENT_GRANDE_CENTIMES : 0);
  }

  if (item.group === "boissons" && selection.tailleBoisson === "grande") {
    return item.prixCentimes + SUPPLEMENT_GRANDE_CENTIMES;
  }

  return item.prixCentimes;
}

// Construit l'objet configuration qui mémorise les choix détaillés du client :
// - Pour un menu : type (Best Of / Maxi), accompagnement, boisson, sauce
// - Pour une boisson : taille (normale / grande)
// - Pour un produit simple : null (pas de configuration)
function buildConfiguration(selection) {
  if (selection.itemType === "menu") {
    assertMenuSelection(selection);
    const taille = selection.typeMenu === "maxiBestOf" ? "grande" : "normale";
    return {
      typeMenu: selection.typeMenu,
      accompagnementId: selection.accompagnementId,
      accompagnementKey: selection.accompagnementKey,
      tailleAccompagnement: taille,
      boissonId: selection.boissonId,
      boissonKey: selection.boissonKey,
      tailleBoisson: taille,
      sauceId: selection.sauceId || null,
      sauceKey: selection.sauceKey || null
    };
  }

  if (selection.tailleBoisson) {
    return { tailleBoisson: selection.tailleBoisson };
  }

  return null;
}

// Vérifie qu'une sélection de menu est complète.
// Un menu DOIT avoir : un type (bestOf/maxiBestOf), un accompagnement et une boisson.
function assertMenuSelection(selection) {
  const hasType = ["bestOf", "maxiBestOf"].includes(selection.typeMenu);
  if (!hasType || !selection.accompagnementId || !selection.accompagnementKey || !selection.boissonId || !selection.boissonKey) {
    throw new Error("Menu incomplet.");
  }
}

// Vérifie qu'une ligne de panier est valide et exploitable (id, quantité valide, prix entier)
function isUsableLine(line) {
  return line && line.lineId && Number.isInteger(line.quantite) && line.quantite > 0 && Number.isInteger(line.prixUnitaireCentimes);
}

// Copie un objet configuration pour éviter les modifications par référence accidentelles
function cloneConfiguration(configuration) {
  return configuration ? { ...configuration } : null;
}

// Génère un identifiant unique pour une ligne de panier.
// Utilise crypto.randomUUID() si disponible, sinon un id basé sur l'horodatage.
function createLineId() {
  if (crypto?.randomUUID) {
    return crypto.randomUUID();
  }
  return `line-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}