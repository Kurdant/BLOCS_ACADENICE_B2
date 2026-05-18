// ====================================================
// COMMANDE.JS — Chef d'orchestre de la page de commande (commande.html)
// Rôle : Gère tout ce qui se passe sur la page principale :
//        - Afficher les catégories et les produits du catalogue
//        - Gérer les clics sur les produits (ouvrir les modales si besoin)
//        - Mettre à jour et afficher le panier en temps réel
//        - Valider et rediriger vers chevalet.html
// ====================================================

import { addSelectionToCart, getCart, isCartEmpty, removeLineFromCart, setCart } from "./cart.js";
import { findItemByKey, formatGroupLabel, formatPrice, loadCatalog } from "./catalog-service.js";
import { initModal, openDrinkModal, openMenuModal } from "./modal.js";
import { clearOrderFlow, getCommandeNumber, getOrderType, getStoredCart, saveCart } from "./storage.js";

// Références cachées vers tous les éléments HTML de la page (récupérées une seule fois au chargement)
const elements = {
  orderMode: document.querySelector("#order-mode"),
  orderNumber: document.querySelector(".cart-order-number"),
  catalogStatus: document.querySelector("#catalog-status"),
  catalogTitle: document.querySelector("#catalog-title"),
  catalogSubtitle: document.querySelector("#catalog-subtitle"),
  retryCatalog: document.querySelector("#retry-catalog"),
  categoriesList: document.querySelector("#categories-list"),
  carouselTrack: document.querySelector(".categories-track-wrapper"),
  carouselPrev: document.querySelector(".carousel-prev"),
  carouselNext: document.querySelector(".carousel-next"),
  productsGrid: document.querySelector("#products-grid"),
  cartLines: document.querySelector("#cart-lines"),
  cartTotal: document.querySelector("#cart-total"),
  cartError: document.querySelector("#cart-error"),
  validateCart: document.querySelector("#validate-cart"),
  abandonCart: document.querySelector("#abandon-cart"),
  modalRoot: document.querySelector("#selection-modal")
};

// Catégorie active (affichée dans la grille de produits), initialisée à la première catégorie
let catalog = null;
let activeCategory = null;
const SCROLL_AMOUNT = 400; // Pixels de défilement par clic sur les flèches du carousel

document.addEventListener("DOMContentLoaded", init);

// Point d'entrée : initialise la modale, restaure le contexte commande, charge le catalogue
function init() {
  initModal(elements.modalRoot);
  if (!restoreOrderContext()) {
    return;
  }

  setCart(getStoredCart());
  saveCart(getCart());
  renderCart();
  bindEvents();
  loadAndRenderCatalog();
}

// Vérifie qu'il y a bien un type de commande en cours, affiche le mode et le numéro.
// Si pas de type de commande (accès direct à la page), redirige vers index.html.
function restoreOrderContext() {
  const orderType = getOrderType();
  if (!orderType) {
    window.location.href = "index.html";
    return false;
  }

  elements.orderMode.textContent = orderType === "surPlace" ? "Sur place" : "A emporter";
  elements.orderNumber.textContent = getCommandeNumber() || "01";
  return true;
}

// Attache tous les écouteurs d'événements de la page (clics, clavier, etc.)
function bindEvents() {
  elements.retryCatalog.addEventListener("click", loadAndRenderCatalog);
  elements.categoriesList.addEventListener("click", handleCategoryClick);
  elements.productsGrid.addEventListener("click", handleProductClick);
  elements.productsGrid.addEventListener("keydown", handleProductKeydown);
  elements.cartLines.addEventListener("click", handleCartClick);
  elements.validateCart.addEventListener("click", handleCartValidation);
  elements.abandonCart.addEventListener("click", handleAbandon);
  elements.carouselPrev.addEventListener("click", () => {
    elements.carouselTrack.scrollBy({ left: -SCROLL_AMOUNT, behavior: "smooth" });
  });
  elements.carouselNext.addEventListener("click", () => {
    elements.carouselTrack.scrollBy({ left: SCROLL_AMOUNT, behavior: "smooth" });
  });
}

// Charge le catalogue depuis les JSON, puis affiche les catégories et les produits.
// En cas d'erreur (fichier absent, réseau coupé), affiche un message et un bouton "Réessayer".
async function loadAndRenderCatalog() {
  setCatalogLoadingState();
  try {
    catalog = await loadCatalog();
    activeCategory = catalog.categories[0]?.title || null;
    purgeInvalidCartLines();
    renderCategories();
    renderProducts();
    renderCart();
  } catch (error) {
    renderCatalogError(error.message || "Catalogue indisponible.");
  }
}

// Met la page en état "chargement" : vide les listes, affiche le message d'attente
function setCatalogLoadingState() {
  elements.retryCatalog.hidden = true;
  elements.catalogStatus.textContent = "Chargement du catalogue...";
  elements.categoriesList.innerHTML = "";
  elements.productsGrid.innerHTML = "";
}

// Supprime du panier les lignes dont les produits n'existent plus dans le catalogue chargé.
// Cas rare mais possible si le catalogue change entre deux sessions.
function purgeInvalidCartLines() {
  const cart = getCart();
  const validLines = cart.lignes.filter(isCartLineCatalogValid);
  if (validLines.length === cart.lignes.length) {
    return;
  }
  setCart({ lignes: validLines, totalCentimes: 0 });
  saveCart(getCart());
}

// Vérifie qu'une ligne du panier correspond toujours à des produits existants dans le catalogue.
// Pour les menus, vérifie aussi que l'accompagnement, la boisson et la sauce existent encore.
function isCartLineCatalogValid(line) {
  if (!findItemByKey(line.itemKey)) {
    return false;
  }

  if (line.itemType !== "menu") {
    return true;
  }

  const config = line.configuration;
  if (!config || !findItemByKey(config.accompagnementKey) || !findItemByKey(config.boissonKey)) {
    return false;
  }

  return !config.sauceKey || Boolean(findItemByKey(config.sauceKey));
}

// Génère le HTML des boutons de catégorie et les injecte dans le DOM.
// La catégorie active est mise en surbrillance (classe is-active).
function renderCategories() {
  elements.categoriesList.innerHTML = catalog.categories.map((category) => `
    <button class="category-button ${category.title === activeCategory ? "is-active" : ""}" type="button" data-category="${category.title}" aria-pressed="${category.title === activeCategory}">
      <img src="${category.image}" alt="">
      <span>${formatGroupLabel(category.title)}</span>
    </button>
  `).join("");
}

// Affiche les produits de la catégorie active dans la grille.
// Met à jour le titre et le sous-titre descriptif de la catégorie.
function renderProducts() {
  const category = catalog.categories.find((candidate) => candidate.title === activeCategory);
  const items = category?.items || [];
  const categoryLabel = formatGroupLabel(activeCategory || "");
  const subtitles = {
    menus: "Un sandwich, une friture ou une salade et une boisson",
    burgers: "Nos burgers préparés avec des ingrédients de qualité",
    sandwiches: "Découvrez notre sélection de sandwiches",
    wraps: "Savourez nos wraps croustillants",
    frites: "Nos accompagnements cuits à point",
    boissons: "Chaudes, froides, avec ou sans bulles",
    desserts: "Terminez en beauté avec nos desserts",
    encas: "Pour les petites faims",
    sauces: "Ketchup, mayo, barbecue..."
  };

  elements.catalogTitle.textContent = `Nos ${categoryLabel.toLowerCase()}`;
  elements.catalogSubtitle.textContent = subtitles[activeCategory] || "";

  if (items.length === 0) {
    elements.productsGrid.innerHTML = `<p class="empty-products">Aucun produit disponible dans cette catégorie.</p>`;
    return;
  }

  elements.productsGrid.innerHTML = items.map((item) => `
    <article class="product-card" data-item-key="${item.itemKey}" role="button" tabindex="0" aria-label="${escapeHtml(item.nom)} - ${formatPrice(item.prixCentimes)}">
      <img src="${item.image}" alt="${escapeHtml(item.nom)}">
      <h3>${escapeHtml(item.nom)}</h3>
      <p class="product-price">${formatPrice(item.prixCentimes)}</p>
    </article>
  `).join("");
}

// Affiche un message d'erreur quand le catalogue ne peut pas être chargé.
// Désactive le bouton Payer et affiche le bouton "Réessayer".
function renderCatalogError(message) {
  clearOrderFlow();
  elements.retryCatalog.hidden = false;
  elements.catalogStatus.textContent = "Catalogue indisponible.";
  elements.productsGrid.innerHTML = `<p class="catalog-error" role="alert">${escapeHtml(message)}</p>`;
  elements.validateCart.disabled = true;
}

// Gestion du clic sur une catégorie : met à jour la catégorie active et recharge la grille
function handleCategoryClick(event) {
  const button = event.target.closest(".category-button");
  if (!button) {
    return;
  }
  activeCategory = button.dataset.category;
  renderCategories();
  renderProducts();
}

// Permet d'activer une carte produit avec Entrée ou Espace (accessibilité clavier)
function handleProductKeydown(event) {
  if (!["Enter", " "].includes(event.key) || !event.target.classList.contains("product-card")) {
    return;
  }
  event.preventDefault();
  event.target.dispatchEvent(new MouseEvent("click", { bubbles: true }));
}

// Gestion du clic sur un produit : ouvre la bonne modale ou ajoute directement au panier.
async function handleProductClick(event) {
  const card = event.target.closest(".product-card[data-item-key]");
  if (!card) {
    return;
  }

  const item = findItemByKey(card.dataset.itemKey);
  if (!item) {
    return;
  }

  try {
    const selection = await buildSelection(item);
    if (!selection) {
      return;
    }
    const cart = addSelectionToCart(item, selection);
    saveCart(cart);
    renderCart();
  } catch (error) {
    elements.cartError.textContent = error.message || "Sélection impossible.";
  }
}

// Décide quelle action effectuer selon le type de produit cliqué :
// - Menu    → ouvre la modale multi-étapes (type, accompagnement, boisson, sauce)
// - Boisson → ouvre la modale de choix de taille
// - Autre   → ajoute directement au panier (pas de configuration nécessaire)
function buildSelection(item) {
  if (item.itemType === "menu") {
    return openMenuModal(item);
  }

  if (item.group === "boissons") {
    return openDrinkModal(item);
  }

  return {
    itemId: item.id,
    itemType: "produit",
    quantite: 1,
    prixCalculeCentimes: item.prixCentimes
  };
}

// Gestion du clic sur la poubelle d'une ligne du panier : retire la ligne et met à jour l'affichage
function handleCartClick(event) {
  const button = event.target.closest(".remove-line");
  if (!button) {
    return;
  }
  const cart = removeLineFromCart(button.dataset.lineId);
  saveCart(cart);
  renderCart();
}

// Gestion du clic sur "Payer" : vérifie que le panier n'est pas vide, sauvegarde et redirige
function handleCartValidation() {
  const cart = getCart();
  if (isCartEmpty(cart)) {
    elements.cartError.textContent = "Ajoutez au moins un produit avant de valider.";
    return;
  }
  saveCart(cart);
  window.location.href = "chevalet.html";
}

// Gestion du clic sur "Abandon" : demande confirmation, puis efface tout et retourne à l'accueil
function handleAbandon() {
  if (!window.confirm("Voulez-vous vraiment annuler votre commande ? Votre panier sera perdu.")) {
    return;
  }
  clearOrderFlow();
  window.location.href = "index.html";
}

// Met à jour l'affichage complet du panier (lignes, total, état du bouton Payer)
function renderCart() {
  const cart = getCart();
  elements.cartError.textContent = "";
  elements.cartTotal.textContent = formatPrice(cart.totalCentimes);
  elements.validateCart.disabled = isCartEmpty(cart);

  if (isCartEmpty(cart)) {
    elements.cartLines.innerHTML = `<p class="cart-empty">Votre panier est vide.</p>`;
    return;
  }

  elements.cartLines.innerHTML = cart.lignes.map((line) => `
    <article class="cart-line">
      <div class="cart-line-info">
        <h3>${escapeHtml(line.nom)}</h3>
        <ul class="cart-line-details">
          <li>${line.quantite} × ${formatPrice(line.prixUnitaireCentimes)}</li>
          ${renderLineConfigurationItems(line)}
        </ul>
        <strong>${formatPrice(line.prixTotalCentimes)}</strong>
      </div>
      <button class="remove-line" type="button" data-line-id="${line.lineId}" aria-label="Supprimer ${escapeHtml(line.nom)}">
        <img src="../Json_donnees/wacdo/images/trash.png" alt="">
      </button>
    </article>
  `).join("");
}

// Génère le HTML des détails de configuration d'une ligne (taille boisson, type menu, etc.)
function renderLineConfigurationItems(line) {
  if (!line.configuration) {
    return "";
  }

  if (line.itemType === "menu") {
    const config = line.configuration;
    const typeLabel = config.typeMenu === "maxiBestOf" ? "Maxi Best Of" : "Best Of";
    const accompaniment = findItemByKey(config.accompagnementKey)?.nom || "Accompagnement";
    const drink = findItemByKey(config.boissonKey)?.nom || "Boisson";
    const sauce = config.sauceKey ? findItemByKey(config.sauceKey)?.nom : null;
    const items = [typeLabel, escapeHtml(accompaniment), escapeHtml(drink)];
    if (sauce) items.push(escapeHtml(sauce));
    return items.map((label) => `<li>${label}</li>`).join("");
  }

  if (line.configuration.tailleBoisson) {
    const sizeLabel = line.configuration.tailleBoisson === "grande" ? "Grande taille" : "Taille normale";
    return `<li>${sizeLabel}</li>`;
  }

  return "";
}

// Sécurité : échappe les caractères spéciaux HTML pour éviter les injections XSS.
// Ex: "<script>" devient "&lt;script&gt;" (s'affiche en texte, ne s'exécute pas)
function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "'": "&#39;",
    '"': "&quot;"
  }[character]));
}