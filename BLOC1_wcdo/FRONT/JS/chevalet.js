// ====================================================
// CHEVALET.JS — Page de saisie du numéro de chevalet (chevalet.html)
// Rôle : Afficher 3 cases à remplir, valider que c'est bien 3 chiffres,
//        puis envoyer la commande finale via payload-builder.js
// ====================================================

import { getStoredCart, getWithdrawNumber, saveWithdrawNumber } from "./storage.js";
import { submitOrder } from "./payload-builder.js";

// Références aux éléments HTML de la page
const form = document.querySelector("#withdraw-form");
const inputs = Array.from(document.querySelectorAll(".digit-input")); // Les 3 cases de saisie
const message = document.querySelector("#withdraw-message");
const submitButton = document.querySelector("#submit-withdraw");

document.addEventListener("DOMContentLoaded", init);

// Initialisation : vérifie que le panier n'est pas vide, restaure le numéro si déjà saisi, attache les événements
function init() {
  const cart = getStoredCart();
  // Sécurité : si le panier est vide, l'utilisateur n'a rien à faire ici → retour à commande.html
  if (!cart.lignes.length) {
    window.location.href = "commande.html";
    return;
  }

  restoreWithdrawNumber(); // Pré-remplit les cases si un numéro avait déjà été saisi
  inputs.forEach((input, index) => bindDigitInput(input, index));
  form.addEventListener("submit", handleSubmit);
  inputs[0].focus(); // Place le curseur sur la première case dès l'ouverture
}

// Si un numéro de retrait est déjà sauvegardé dans localStorage, on pré-remplit les 3 cases
function restoreWithdrawNumber() {
  const withdrawNumber = getWithdrawNumber();
  if (!withdrawNumber) {
    return;
  }
  withdrawNumber.split("").forEach((digit, index) => {
    inputs[index].value = digit;
  });
}

// Attache les comportements à une case de saisie :
// - Accepte uniquement des chiffres, 1 seul par case
// - Avance automatiquement à la case suivante dès qu'un chiffre est entré
// - Backspace sur une case vide → retourne à la case précédente
// - Gère le collage (paste) d'un numéro complet
function bindDigitInput(input, index) {
  input.addEventListener("input", () => {
    // Supprime tout ce qui n'est pas un chiffre, garde 1 seul caractère max
    input.value = input.value.replace(/\D/g, "").slice(0, 1);
    // Passe automatiquement à la case suivante si elle existe
    if (input.value && inputs[index + 1]) {
      inputs[index + 1].focus();
    }
  });

  input.addEventListener("keydown", (event) => {
    // Backspace sur une case vide → retourne à la case précédente
    if (event.key === "Backspace" && !input.value && inputs[index - 1]) {
      inputs[index - 1].focus();
    }
  });

  input.addEventListener("paste", (event) => {
    event.preventDefault();
    // Récupère les 3 premiers chiffres collés et les distribue dans chaque case
    const digits = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, 3);
    digits.split("").forEach((digit, digitIndex) => {
      inputs[digitIndex].value = digit;
    });
    inputs[Math.min(digits.length, 3) - 1]?.focus();
  });
}

// Gère la soumission du formulaire : valide le numéro, envoie la commande, puis redirige
async function handleSubmit(event) {
  event.preventDefault(); // Empêche le rechargement de la page
  const withdrawNumber = inputs.map((input) => input.value).join(""); // Recolle les 3 chiffres

  // Validation : doit être exactement 3 chiffres
  if (!/^\d{3}$/.test(withdrawNumber)) {
    message.textContent = "Le numéro doit contenir exactement 3 chiffres.";
    return;
  }

  message.textContent = "";
  submitButton.disabled = true;  // Désactive le bouton pour éviter les double-envois
  submitButton.textContent = "Envoi en cours...";

  try {
    saveWithdrawNumber(withdrawNumber); // Sauvegarde le numéro dans localStorage
    const result = await submitOrder(withdrawNumber); // Envoie la commande (via payload-builder)
    if (!result.ok || result.status !== 202) {
      throw new Error("Commande non transmise.");
    }
    window.location.href = "remerciement.html"; // Succès → page de remerciement
  } catch (error) {
    // En cas d'erreur : on réactive le bouton et on affiche le message d'erreur
    message.textContent = error.message || "La commande n'a pas pu être transmise.";
    submitButton.disabled = false;
    submitButton.textContent = "Enregistrer le numéro";
  }
}