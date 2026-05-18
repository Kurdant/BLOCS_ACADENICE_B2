// ====================================================
// ACCUEIL.JS — Page d'accueil (index.html)
// Rôle : Détecter le choix "Sur place" ou "À emporter",
//        initialiser la commande et rediriger vers commande.html
// ====================================================

import { clearOrderFlow, initializeCart, saveOrderType, saveCommandeNumber } from "./storage.js";

// Attend que la page soit entièrement chargée avant d'activer les boutons
document.addEventListener("DOMContentLoaded", () => {
  // Sélectionne les deux boutons via l'attribut data-order-type dans le HTML
  document.querySelectorAll("[data-order-type]").forEach((button) => {
    button.addEventListener("click", () => {
      const orderType = button.dataset.orderType; // "surPlace" ou "aEmporter"

      clearOrderFlow();            // Efface toute commande précédente dans localStorage
      saveOrderType(orderType);    // Sauvegarde le choix du client dans localStorage
      initializeCart();            // Crée un panier vide dans localStorage

      // Génère un numéro de commande aléatoire entre 01 et 99 (affiché sur la borne)
      const commandeNumber = String(Math.floor(Math.random() * 99) + 1).padStart(2, "0");
      saveCommandeNumber(commandeNumber);

      window.location.href = "commande.html"; // Redirige vers la page de commande
    });
  });
});