import { getStoredCart, getWithdrawNumber, saveWithdrawNumber } from "./storage.js";
import { submitOrder } from "./payload-builder.js";

const form = document.querySelector("#withdraw-form");
const inputs = Array.from(document.querySelectorAll(".digit-input"));
const message = document.querySelector("#withdraw-message");
const submitButton = document.querySelector("#submit-withdraw");

document.addEventListener("DOMContentLoaded", init);

function init() {
  const cart = getStoredCart();
  if (!cart.lignes.length) {
    window.location.href = "commande.html";
    return;
  }

  restoreWithdrawNumber();
  inputs.forEach((input, index) => bindDigitInput(input, index));
  form.addEventListener("submit", handleSubmit);
  inputs[0].focus();
}

function restoreWithdrawNumber() {
  const withdrawNumber = getWithdrawNumber();
  if (!withdrawNumber) {
    return;
  }
  withdrawNumber.split("").forEach((digit, index) => {
    inputs[index].value = digit;
  });
}

function bindDigitInput(input, index) {
  input.addEventListener("input", () => {
    input.value = input.value.replace(/\D/g, "").slice(0, 1);
    if (input.value && inputs[index + 1]) {
      inputs[index + 1].focus();
    }
  });

  input.addEventListener("keydown", (event) => {
    if (event.key === "Backspace" && !input.value && inputs[index - 1]) {
      inputs[index - 1].focus();
    }
  });

  input.addEventListener("paste", (event) => {
    event.preventDefault();
    const digits = event.clipboardData.getData("text").replace(/\D/g, "").slice(0, 3);
    digits.split("").forEach((digit, digitIndex) => {
      inputs[digitIndex].value = digit;
    });
    inputs[Math.min(digits.length, 3) - 1]?.focus();
  });
}

async function handleSubmit(event) {
  event.preventDefault();
  const withdrawNumber = inputs.map((input) => input.value).join("");

  if (!/^\d{3}$/.test(withdrawNumber)) {
    message.textContent = "Le numéro doit contenir exactement 3 chiffres.";
    return;
  }

  message.textContent = "";
  submitButton.disabled = true;
  submitButton.textContent = "Envoi en cours...";

  try {
    saveWithdrawNumber(withdrawNumber);
    const result = await submitOrder(withdrawNumber);
    if (!result.ok || result.status !== 202) {
      throw new Error("Commande non transmise.");
    }
    window.location.href = "remerciement.html";
  } catch (error) {
    message.textContent = error.message || "La commande n'a pas pu être transmise.";
    submitButton.disabled = false;
    submitButton.textContent = "Enregistrer le numéro";
  }
}