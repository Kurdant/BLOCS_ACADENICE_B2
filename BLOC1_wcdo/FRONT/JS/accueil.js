import { clearOrderFlow, initializeCart, saveOrderType, saveCommandeNumber } from "./storage.js";

document.addEventListener("DOMContentLoaded", () => {
  document.querySelectorAll("[data-order-type]").forEach((button) => {
    button.addEventListener("click", () => {
      const orderType = button.dataset.orderType;
      clearOrderFlow();
      saveOrderType(orderType);
      initializeCart();
      // Générer et stocker le numéro de commande
      const commandeNumber = String(Math.floor(Math.random() * 99) + 1).padStart(2, "0");
      saveCommandeNumber(commandeNumber);
      window.location.href = "commande.html";
    });
  });
});