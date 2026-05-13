import { clearOrderFlow, getOrderType, getStoredCart } from "./storage.js";

const API_MODE = "mock";
const API_ROUTE = "/envoie/commande";
const HTTP_TIMEOUT_MS = 10000;

export async function submitOrder(withdrawNumber) {
  const payload = buildOrderPayload(withdrawNumber);
  const response = await sendPayload(payload);

  if (response.ok && response.status === 202) {
    clearOrderFlow();
  }

  return { ...response, payload };
}

export function buildOrderPayload(withdrawNumber) {
  if (!/^\d{3}$/.test(withdrawNumber || "")) {
    throw new Error("Le numéro de retrait doit contenir exactement 3 chiffres.");
  }

  const orderType = getOrderType();
  const panier = getStoredCart();

  validateOrderState(orderType, panier);

  return {
    contrat: {
      methode: "POST",
      route: API_ROUTE,
      mode: API_MODE
    },
    commande: {
      idCommande: createOrderId(),
      orderType,
      withdrawNumber,
      createdAt: new Date().toISOString(),
      lignes: panier.lignes.map(normalizePayloadLine),
      totalCentimes: panier.totalCentimes
    }
  };
}

async function sendPayload(payload) {
  if (API_MODE === "mock") {
    await new Promise((resolve) => window.setTimeout(resolve, 350));
    return { ok: true, status: 202, route: API_ROUTE };
  }

  const controller = new AbortController();
  const timeoutId = window.setTimeout(() => controller.abort(), HTTP_TIMEOUT_MS);

  try {
    const response = await fetch(API_ROUTE, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
      signal: controller.signal
    });
    return { ok: response.ok && response.status === 202, status: response.status, route: API_ROUTE };
  } finally {
    window.clearTimeout(timeoutId);
  }
}

function validateOrderState(orderType, panier) {
  if (!["surPlace", "aEmporter"].includes(orderType)) {
    throw new Error("Type de commande manquant.");
  }

  if (!panier || !Array.isArray(panier.lignes) || panier.lignes.length === 0) {
    throw new Error("Le panier est vide.");
  }

  panier.lignes.forEach(validateLine);

  const totalCentimes = panier.lignes.reduce((total, line) => total + line.prixTotalCentimes, 0);
  if (totalCentimes !== panier.totalCentimes) {
    throw new Error("Total panier incohérent.");
  }
}

function validateLine(line) {
  if (!line.lineId || !line.itemId || !["produit", "menu"].includes(line.itemType) || !Number.isInteger(line.quantite) || line.quantite < 1) {
    throw new Error("Ligne de panier invalide.");
  }

  if (!Number.isInteger(line.prixUnitaireCentimes) || !Number.isInteger(line.prixTotalCentimes)) {
    throw new Error("Prix de ligne invalide.");
  }

  if (line.prixUnitaireCentimes * line.quantite !== line.prixTotalCentimes) {
    throw new Error("Prix total de ligne incohérent.");
  }

  if (line.itemType === "menu") {
    validateMenuConfiguration(line.configuration);
  }
}

function validateMenuConfiguration(configuration) {
  const hasType = ["bestOf", "maxiBestOf"].includes(configuration?.typeMenu);
  if (!hasType || !configuration.accompagnementId || !configuration.boissonId) {
    throw new Error("Configuration menu incomplète.");
  }
}

function normalizePayloadLine(line) {
  return {
    lineId: line.lineId,
    itemId: line.itemId,
    itemKey: line.itemKey,
    itemType: line.itemType,
    group: line.group,
    nom: line.nom,
    quantite: line.quantite,
    configuration: line.configuration,
    prixUnitaireCentimes: line.prixUnitaireCentimes,
    prixTotalCentimes: line.prixTotalCentimes
  };
}

function createOrderId() {
  if (crypto?.randomUUID) {
    return crypto.randomUUID();
  }
  return `cmd-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}