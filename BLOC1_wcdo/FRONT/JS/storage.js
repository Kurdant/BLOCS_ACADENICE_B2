const KEYS = {
  orderType: "wacdo.orderType",
  cart: "wacdo.cart",
  withdrawNumber: "wacdo.withdrawNumber",
  commandeNumber: "wacdo.commandeNumber"
};

const EMPTY_CART = Object.freeze({ lignes: [], totalCentimes: 0 });

export function clearOrderFlow() {
  localStorage.removeItem(KEYS.orderType);
  localStorage.removeItem(KEYS.cart);
  localStorage.removeItem(KEYS.withdrawNumber);
  localStorage.removeItem(KEYS.commandeNumber);
}
// Gestion du numéro de commande
export function saveCommandeNumber(commandeNumber) {
  if (!/^[0-9]{2}$/.test(commandeNumber)) {
    throw new Error("Numéro de commande invalide.");
  }
  localStorage.setItem(KEYS.commandeNumber, commandeNumber);
}

export function getCommandeNumber() {
  const n = localStorage.getItem(KEYS.commandeNumber);
  return /^[0-9]{2}$/.test(n || "") ? n : null;
}

export function clearCommandeNumber() {
  localStorage.removeItem(KEYS.commandeNumber);
}

export function saveOrderType(orderType) {
  if (!["surPlace", "aEmporter"].includes(orderType)) {
    throw new Error("Type de commande invalide.");
  }
  localStorage.setItem(KEYS.orderType, orderType);
}

export function getOrderType() {
  const orderType = localStorage.getItem(KEYS.orderType);
  return ["surPlace", "aEmporter"].includes(orderType) ? orderType : null;
}

export function initializeCart() {
  saveCart({ ...EMPTY_CART, lignes: [] });
}

export function saveCart(cart) {
  const safeCart = cart && Array.isArray(cart.lignes) ? cart : { ...EMPTY_CART, lignes: [] };
  localStorage.setItem(KEYS.cart, JSON.stringify(safeCart));
}

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

export function saveWithdrawNumber(withdrawNumber) {
  if (!/^\d{3}$/.test(withdrawNumber)) {
    throw new Error("Numéro de retrait invalide.");
  }
  localStorage.setItem(KEYS.withdrawNumber, withdrawNumber);
}

export function getWithdrawNumber() {
  const withdrawNumber = localStorage.getItem(KEYS.withdrawNumber);
  return /^\d{3}$/.test(withdrawNumber || "") ? withdrawNumber : null;
}