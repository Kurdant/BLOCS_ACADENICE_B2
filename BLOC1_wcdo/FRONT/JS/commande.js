import { addSelectionToCart, getCart, isCartEmpty, removeLineFromCart, setCart } from "./cart.js";
import { findItemByKey, formatGroupLabel, formatPrice, loadCatalog } from "./catalog-service.js";
import { initModal, openDrinkModal, openMenuModal } from "./modal.js";
import { clearOrderFlow, getCommandeNumber, getOrderType, getStoredCart, saveCart } from "./storage.js";

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

let catalog = null;
let activeCategory = null;
const SCROLL_AMOUNT = 400;

document.addEventListener("DOMContentLoaded", init);

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

function setCatalogLoadingState() {
  elements.retryCatalog.hidden = true;
  elements.catalogStatus.textContent = "Chargement du catalogue...";
  elements.categoriesList.innerHTML = "";
  elements.productsGrid.innerHTML = "";
}

function purgeInvalidCartLines() {
  const cart = getCart();
  const validLines = cart.lignes.filter(isCartLineCatalogValid);
  if (validLines.length === cart.lignes.length) {
    return;
  }
  setCart({ lignes: validLines, totalCentimes: 0 });
  saveCart(getCart());
}

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

function renderCategories() {
  elements.categoriesList.innerHTML = catalog.categories.map((category) => `
    <button class="category-button ${category.title === activeCategory ? "is-active" : ""}" type="button" data-category="${category.title}" aria-pressed="${category.title === activeCategory}">
      <img src="${category.image}" alt="">
      <span>${formatGroupLabel(category.title)}</span>
    </button>
  `).join("");
}

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

function renderCatalogError(message) {
  clearOrderFlow();
  elements.retryCatalog.hidden = false;
  elements.catalogStatus.textContent = "Catalogue indisponible.";
  elements.productsGrid.innerHTML = `<p class="catalog-error" role="alert">${escapeHtml(message)}</p>`;
  elements.validateCart.disabled = true;
}

function handleCategoryClick(event) {
  const button = event.target.closest(".category-button");
  if (!button) {
    return;
  }
  activeCategory = button.dataset.category;
  renderCategories();
  renderProducts();
}

function handleProductKeydown(event) {
  if (!["Enter", " "].includes(event.key) || !event.target.classList.contains("product-card")) {
    return;
  }
  event.preventDefault();
  event.target.dispatchEvent(new MouseEvent("click", { bubbles: true }));
}

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

function handleCartClick(event) {
  const button = event.target.closest(".remove-line");
  if (!button) {
    return;
  }
  const cart = removeLineFromCart(button.dataset.lineId);
  saveCart(cart);
  renderCart();
}

function handleCartValidation() {
  const cart = getCart();
  if (isCartEmpty(cart)) {
    elements.cartError.textContent = "Ajoutez au moins un produit avant de valider.";
    return;
  }
  saveCart(cart);
  window.location.href = "chevalet.html";
}

function handleAbandon() {
  if (!window.confirm("Voulez-vous vraiment annuler votre commande ? Votre panier sera perdu.")) {
    return;
  }
  clearOrderFlow();
  window.location.href = "index.html";
}

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

function escapeHtml(value) {
  return String(value).replace(/[&<>'"]/g, (character) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    "'": "&#39;",
    '"': "&quot;"
  }[character]));
}