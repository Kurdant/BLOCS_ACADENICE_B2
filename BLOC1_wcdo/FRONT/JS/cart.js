export const SUPPLEMENT_GRANDE_CENTIMES = 50;

let currentCart = { lignes: [], totalCentimes: 0 };

export function setCart(cart) {
  const lignes = Array.isArray(cart?.lignes) ? cart.lignes.filter(isUsableLine) : [];
  currentCart = recalculateCart({ lignes, totalCentimes: 0 });
  return getCart();
}

export function getCart() {
  return {
    lignes: currentCart.lignes.map((line) => ({ ...line, configuration: cloneConfiguration(line.configuration) })),
    totalCentimes: currentCart.totalCentimes
  };
}

export function addSelectionToCart(item, selection) {
  const line = createLineFromSelection(item, selection);
  currentCart = recalculateCart({ lignes: [...currentCart.lignes, line], totalCentimes: 0 });
  return getCart();
}

export function removeLineFromCart(lineId) {
  currentCart = recalculateCart({
    lignes: currentCart.lignes.filter((line) => line.lineId !== lineId),
    totalCentimes: 0
  });
  return getCart();
}

export function isCartEmpty(cart = currentCart) {
  return !Array.isArray(cart.lignes) || cart.lignes.length === 0;
}

export function recalculateCart(cart) {
  const lignes = Array.isArray(cart?.lignes) ? cart.lignes.map((line) => ({
    ...line,
    prixTotalCentimes: line.prixUnitaireCentimes * line.quantite
  })) : [];
  const totalCentimes = lignes.reduce((total, line) => total + line.prixTotalCentimes, 0);
  return { lignes, totalCentimes };
}

function createLineFromSelection(item, selection) {
  if (!item || !selection || !Number.isInteger(selection.quantite) || selection.quantite < 1) {
    throw new Error("Sélection invalide.");
  }

  const prixUnitaireCentimes = calculateUnitPrice(item, selection);

  return {
    lineId: createLineId(),
    itemId: item.id,
    itemKey: item.itemKey,
    itemType: item.itemType,
    group: item.group,
    nom: item.nom,
    quantite: selection.quantite,
    configuration: buildConfiguration(selection),
    prixUnitaireCentimes,
    prixTotalCentimes: prixUnitaireCentimes * selection.quantite
  };
}

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

function assertMenuSelection(selection) {
  const hasType = ["bestOf", "maxiBestOf"].includes(selection.typeMenu);
  if (!hasType || !selection.accompagnementId || !selection.accompagnementKey || !selection.boissonId || !selection.boissonKey) {
    throw new Error("Menu incomplet.");
  }
}

function isUsableLine(line) {
  return line && line.lineId && Number.isInteger(line.quantite) && line.quantite > 0 && Number.isInteger(line.prixUnitaireCentimes);
}

function cloneConfiguration(configuration) {
  return configuration ? { ...configuration } : null;
}

function createLineId() {
  if (crypto?.randomUUID) {
    return crypto.randomUUID();
  }
  return `line-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}