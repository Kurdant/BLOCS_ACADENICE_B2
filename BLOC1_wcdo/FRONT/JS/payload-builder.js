// ====================================================
// PAYLOAD-BUILDER.JS — Construction et envoi de la commande finale
// Rôle : Assembler un objet JSON complet avec toute la commande,
//        valider que tout est cohérent, puis l'envoyer à l'API du serveur.
//        C'est le "bon de commande officiel" envoyé en cuisine.
// ====================================================

import { clearOrderFlow, getOrderType, getStoredCart } from "./storage.js";

const API_MODE = "mock";           // "mock" = simulation locale, "live" = vrai serveur
const API_ROUTE = "/envoie/commande"; // Route de l'API où envoyer la commande
const HTTP_TIMEOUT_MS = 10000;     // Délai max pour la réponse du serveur (10 secondes)

// Envoie la commande complète au serveur.
// Si la réponse est 202 (accepté), efface les données de commande du localStorage.
export async function submitOrder(withdrawNumber) {
  const payload = buildOrderPayload(withdrawNumber); // Construit l'objet commande
  const response = await sendPayload(payload);       // Envoie au serveur (ou simule)

  if (response.ok && response.status === 202) {
    clearOrderFlow(); // Nettoie le localStorage : la commande est terminée
  }

  return { ...response, payload };
}

// Construit l'objet JSON complet de la commande à partir des données du localStorage.
// Valide toutes les données avant de construire (numéro retrait, type, panier cohérent).
export function buildOrderPayload(withdrawNumber) {
  if (!/^\d{3}$/.test(withdrawNumber || "")) {
    throw new Error("Le numéro de retrait doit contenir exactement 3 chiffres.");
  }

  const orderType = getOrderType();     // "surPlace" ou "aEmporter"
  const panier = getStoredCart();       // Contenu du panier depuis localStorage

  validateOrderState(orderType, panier); // Vérifie la cohérence complète avant envoi

  return {
    contrat: {
      methode: "POST",
      route: API_ROUTE,
      mode: API_MODE
    },
    commande: {
      idCommande: createOrderId(),          // Identifiant unique de la commande
      orderType,
      withdrawNumber,
      createdAt: new Date().toISOString(),  // Horodatage ISO de la commande
      lignes: panier.lignes.map(normalizePayloadLine), // Lignes nettoyées pour l'API
      totalCentimes: panier.totalCentimes
    }
  };
}

// Envoie l'objet payload au serveur via HTTP POST.
// En mode "mock" : simule une réponse 202 après 350ms (tests sans serveur).
// En mode réel : fetch() avec un timeout de sécurité (abort si pas de réponse en 10s).
async function sendPayload(payload) {
  if (API_MODE === "mock") {
    // Mode simulation : on attend 350ms et on retourne une réponse fictive
    await new Promise((resolve) => window.setTimeout(resolve, 350));
    return { ok: true, status: 202, route: API_ROUTE };
  }

  // Mode réel : AbortController permet d'annuler la requête si elle prend trop de temps
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
    window.clearTimeout(timeoutId); // Annule le timeout quoi qu'il arrive
  }
}

// Vérifie la cohérence globale de la commande avant envoi :
// type valide + panier non vide + chaque ligne valide + total correct
function validateOrderState(orderType, panier) {
  if (!["surPlace", "aEmporter"].includes(orderType)) {
    throw new Error("Type de commande manquant.");
  }

  if (!panier || !Array.isArray(panier.lignes) || panier.lignes.length === 0) {
    throw new Error("Le panier est vide.");
  }

  panier.lignes.forEach(validateLine); // Vérifie chaque ligne individuellement

  // Recalcule le total et compare avec celui stocké (détecte les manipulations)
  const totalCentimes = panier.lignes.reduce((total, line) => total + line.prixTotalCentimes, 0);
  if (totalCentimes !== panier.totalCentimes) {
    throw new Error("Total panier incohérent.");
  }
}

// Vérifie qu'une ligne de panier est complète et cohérente :
// identifiants présents, quantité valide, prixUnitaire × quantité = prixTotal
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

  // Les lignes de type menu ont des règles supplémentaires (accompagnement + boisson obligatoires)
  if (line.itemType === "menu") {
    validateMenuConfiguration(line.configuration);
  }
}

// Vérifie qu'un menu a bien un type (bestOf/maxiBestOf), un accompagnement et une boisson
function validateMenuConfiguration(configuration) {
  const hasType = ["bestOf", "maxiBestOf"].includes(configuration?.typeMenu);
  if (!hasType || !configuration.accompagnementId || !configuration.boissonId) {
    throw new Error("Configuration menu incomplète.");
  }
}

// Nettoie une ligne de panier pour ne garder que les champs utiles à l'API
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

// Génère un identifiant unique pour la commande.
// Utilise crypto.randomUUID() si disponible, sinon un id basé sur l'horodatage.
function createOrderId() {
  if (crypto?.randomUUID) {
    return crypto.randomUUID();
  }
  return `cmd-${Date.now()}-${Math.floor(Math.random() * 100000)}`;
}