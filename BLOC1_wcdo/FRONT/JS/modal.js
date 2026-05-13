import { SUPPLEMENT_GRANDE_CENTIMES } from "./cart.js";
import { getItemsByGroup } from "./catalog-service.js";

let modalRoot = null;
let activeResolver = null;
let menuEscapeHandler = null;
let isMenuRendering = false;

function filterAccompanimentsByMenu(typeMenu) {
  const frites = getItemsByGroup("frites");
  const salades = getItemsByGroup("salades");
  const normalIds = new Set([36, 38, 60]);
  const maxiIds = new Set([37, 39, 60]);
  const items = [...frites, ...salades];

  if (typeMenu === "bestOf") {
    return items.filter((item) => normalIds.has(item.id));
  }

  if (typeMenu === "maxiBestOf") {
    return items.filter((item) => maxiIds.has(item.id));
  }

  return items;
}

export function initModal(rootElement) {
  modalRoot = rootElement;
}

export function openMenuModal(item) {
  ensureModalRoot();
  const drinks = getItemsByGroup("boissons");
  const sauces = getItemsByGroup("sauces");
  const draft = {
    itemId: item.id,
    itemType: "menu",
    quantite: 1,
    typeMenu: null,
    accompagnementId: null,
    accompagnementKey: null,
    boissonId: null,
    boissonKey: null,
    tailleBoisson: null,
    sauceId: null,
    sauceKey: null,
    prixCalculeCentimes: item.prixCentimes
  };

  return new Promise((resolve) => {
    activeResolver = resolve;
    modalRoot.hidden = false;
    document.body.style.overflow = "hidden";
    showMenuStep(1, { item, draft, drinks, sauces });
  });
}

function showMenuStep(step, ctx) {
  if (isMenuRendering) return;
  isMenuRendering = true;
  if (menuEscapeHandler) {
    document.removeEventListener("keydown", menuEscapeHandler);
    menuEscapeHandler = null;
  }

  const { draft } = ctx;
  // Calcul des accompagnements AVANT le rendu si étape 2
  if (step === 2) {
    ctx.accompaniments = filterAccompanimentsByMenu(ctx.draft.typeMenu);
  }
  modalRoot.innerHTML = buildMenuStepHtml(step, ctx);

  function cleanupMenu() {
    isMenuRendering = false;
    if (menuEscapeHandler) {
      document.removeEventListener("keydown", menuEscapeHandler);
      menuEscapeHandler = null;
    }
    modalRoot.innerHTML = "";
    modalRoot.hidden = true;
    document.body.style.overflow = "";
  }

  function closeMenuModal() {
    const resolve = activeResolver;
    activeResolver = null;
    cleanupMenu();
    resolve(null);
  }

  function finishMenu() {
    draft.prixCalculeCentimes =
      ctx.item.prixCentimes + (draft.typeMenu === "maxiBestOf" ? SUPPLEMENT_GRANDE_CENTIMES : 0);
    const result = { ...draft };
    const resolve = activeResolver;
    activeResolver = null;
    cleanupMenu();
    resolve(result);
  }

  menuEscapeHandler = (e) => { if (e.key === "Escape") closeMenuModal(); };
  document.addEventListener("keydown", menuEscapeHandler);

  modalRoot.querySelector(".modal-backdrop").addEventListener("click", closeMenuModal);
  modalRoot.querySelector(".modal-close").addEventListener("click", closeMenuModal);

  const btnBack = modalRoot.querySelector(".btn-back");
  const btnNext = modalRoot.querySelector(".btn-next");
  const btnNoSauce = modalRoot.querySelector(".btn-no-sauce");

  if (btnBack) {
    btnBack.addEventListener("click", () => showMenuStep(step - 1, ctx));
  }

  if (btnNoSauce) {
    btnNoSauce.addEventListener("click", () => {
      draft.sauceId = null;
      draft.sauceKey = null;
      finishMenu();
    });
  }

  if (btnNext) {
    btnNext.addEventListener("click", () => {
      if (step < 4) {
        showMenuStep(step + 1, ctx);
      } else {
        finishMenu();
      }
    });
  }

  if (step === 1) {
    setupGridClick("typeMenu", (card) => {
      draft.typeMenu = card.dataset.value;
      draft.tailleBoisson = draft.typeMenu === "maxiBestOf" ? "grande" : "normale";
      if (btnNext) btnNext.disabled = false;
    });
  }

  if (step === 2) {
    setupGridClick("accompagnement", (card) => {
      draft.accompagnementId = Number(card.dataset.id);
      draft.accompagnementKey = card.dataset.key;
      if (btnNext) btnNext.disabled = false;
    });
  }

  if (step === 3) {
    setupCarouselInteraction("boisson", draft, () => {
      if (btnNext) btnNext.disabled = false;
    });
  }

  if (step === 4) {
    setupCarouselInteraction("sauce", draft, null);
  }

  modalRoot.querySelector(".selection-dialog").focus();
  isMenuRendering = false;
}

function buildMenuStepHtml(step, ctx) {
  const { draft, accompaniments, drinks, sauces } = ctx;

  const STEP_META = [
    null,
    {
      title: "Une grosse faim ?",
      subtitle: "Le menu maxi Best Of comprend un sandwich, une grande frite et une boisson 50 Cl"
    },
    {
      title: "Choisissez votre accompagnement",
      subtitle: "Frites, potatoes, la pomme de terre dans tous ses états"
    },
    {
      title: "Choisissez votre boisson",
      subtitle: "Un soda, un jus de fruit ou un verre d'eau pour accompagner votre repas"
    },
    {
      title: "Choisissez votre sauce",
      subtitle: "Optionnelle — une seule sauce possible"
    }
  ];

  const meta = STEP_META[step];
  const hasBack = step > 1;
  const isLastStep = step === 4;

  const backBtn = hasBack
    ? `<button class="btn-back" type="button">Retour</button>`
    : `<div></div>`;

  let body = "";
  if (step === 1) body = renderStep1Body(draft);
  else if (step === 2) body = renderStep2Body(draft, accompaniments);
  else if (step === 3) body = renderCarouselBody("boisson", drinks, draft.boissonId);
  else if (step === 4) body = renderCarouselBody("sauce", sauces, draft.sauceId);

  const nextLabel = isLastStep ? "Ajouter le menu à ma commande" : "Etape Suivante";
  const nextDisabled = isLastStep ? "" : getNextDisabled(step, draft);

  const footerExtras = isLastStep
    ? `<button class="btn-no-sauce" type="button">Je n'en veux pas</button>`
    : "";

  return `
    <div class="modal-backdrop"></div>
    <section class="selection-dialog" role="dialog" aria-modal="true" tabindex="-1">
      <div class="modal-step">
        <div class="step-header">
          ${backBtn}
          <button class="modal-close" type="button" aria-label="Fermer">×</button>
        </div>
        <h2 class="step-title">${escapeHtml(meta.title)}</h2>
        <p class="step-subtitle">${escapeHtml(meta.subtitle)}</p>
        <div class="step-body">${body}</div>
      </div>
      <div class="step-footer">
        <button class="btn-next" type="button" ${nextDisabled}>${escapeHtml(nextLabel)}</button>
        ${footerExtras}
      </div>
    </section>
  `;
}

function getNextDisabled(step, draft) {
  if (step === 1 && !draft.typeMenu) return "disabled";
  if (step === 2 && !draft.accompagnementId) return "disabled";
  if (step === 3 && !draft.boissonId) return "disabled";
  return "";
}

function renderStep1Body(draft) {
  const cards = [
    {
      value: "maxiBestOf",
      label: "Menu Maxi Best Of",
      img: "../Json_donnees/wacdo/images/illustration-maxi-best-of.png"
    },
    {
      value: "bestOf",
      label: "Menu Best Of",
      img: "../Json_donnees/wacdo/images/illustration-best-of.png"
    }
  ];

  return `
    <div class="step-choices-grid step-choices-grid--2col" data-choice-group="typeMenu">
      ${cards.map((c) => `
        <button class="step-card ${draft.typeMenu === c.value ? "is-selected" : ""}" type="button" data-value="${c.value}">
          <img src="${c.img}" alt="">
          <span>${c.label}</span>
        </button>
      `).join("")}
    </div>
  `;
}

function renderStep2Body(draft, accompaniments) {
  return `
    <div class="step-choices-grid step-choices-grid--auto" data-choice-group="accompagnement">
      ${accompaniments.map((item) => `
        <button class="step-card ${draft.accompagnementId === item.id ? "is-selected" : ""}" type="button"
          data-id="${item.id}" data-key="${escapeHtml(item.itemKey)}">
          <img src="${escapeHtml(item.image)}" alt="">
          <span>${escapeHtml(item.nom)}</span>
        </button>
      `).join("")}
    </div>
  `;
}

function renderCarouselBody(group, items, selectedId) {
  const cardsHtml = items.map((item) => `
    <button class="carousel-item ${selectedId === item.id ? "is-selected" : ""}" type="button"
      data-id="${item.id}" data-key="${escapeHtml(item.itemKey)}">
      <img src="${escapeHtml(item.image)}" alt="">
      <span>${escapeHtml(item.nom)}</span>
    </button>
  `).join("");

  return `
    <div class="carousel-wrapper" data-carousel data-choice-group="${group}">
      <button class="modal-carousel-arrow modal-carousel-arrow--prev" type="button" aria-label="Précédent">&#9664;</button>
      <div class="carousel-track-outer">
        <div class="carousel-track" data-carousel-track>${cardsHtml}</div>
      </div>
      <button class="modal-carousel-arrow modal-carousel-arrow--next" type="button" aria-label="Suivant">&#9654;</button>
    </div>
  `;
}

function setupGridClick(group, onSelect) {
  const grid = modalRoot.querySelector(`[data-choice-group="${group}"]`);
  if (!grid) return;

  grid.addEventListener("click", (e) => {
    const card = e.target.closest(".step-card");
    if (!card) return;
    grid.querySelectorAll(".step-card").forEach((c) => c.classList.remove("is-selected"));
    card.classList.add("is-selected");
    onSelect(card);
  });
}

function setupCarouselInteraction(group, draft, onSelect) {
  const wrapper = modalRoot.querySelector("[data-carousel]");
  if (!wrapper) return;

  const track = wrapper.querySelector("[data-carousel-track]");
  const prevBtn = wrapper.querySelector(".modal-carousel-arrow--prev");
  const nextBtn = wrapper.querySelector(".modal-carousel-arrow--next");
  const items = Array.from(track.querySelectorAll(".carousel-item"));

  const CARD_STEP = 180 + 14;
  let idx = 0;

  function updateArrows() {
    const outerWidth = wrapper.querySelector(".carousel-track-outer").offsetWidth;
    const visible = Math.max(1, Math.floor(outerWidth / CARD_STEP));
    prevBtn.disabled = idx === 0;
    nextBtn.disabled = idx >= items.length - visible;
  }

  function slideTo(newIdx) {
    idx = Math.max(0, Math.min(items.length - 1, newIdx));
    track.style.transform = `translateX(-${idx * CARD_STEP}px)`;
    updateArrows();
  }

  prevBtn.addEventListener("click", () => slideTo(idx - 1));
  nextBtn.addEventListener("click", () => slideTo(idx + 1));

  const preselectedIdx = items.findIndex((i) => i.classList.contains("is-selected"));
  if (preselectedIdx > 0) {
    slideTo(preselectedIdx);
  } else {
    updateArrows();
  }

  items.forEach((item) => {
    item.addEventListener("click", () => {
      const isSelected = item.classList.contains("is-selected");

      if (group === "sauce" && isSelected) {
        item.classList.remove("is-selected");
        draft.sauceId = null;
        draft.sauceKey = null;
        return;
      }

      items.forEach((i) => i.classList.remove("is-selected"));
      item.classList.add("is-selected");

      if (group === "boisson") {
        draft.boissonId = Number(item.dataset.id);
        draft.boissonKey = item.dataset.key;
      } else if (group === "sauce") {
        draft.sauceId = Number(item.dataset.id);
        draft.sauceKey = item.dataset.key;
      }

      if (onSelect) onSelect(item);
    });
  });
}

export function openDrinkModal(item) {
  ensureModalRoot();
  const draft = {
    itemId: item.id,
    itemType: "produit",
    quantite: 1,
    tailleBoisson: null,
    prixCalculeCentimes: item.prixCentimes
  };
  const QTY_MIN = 1;

  modalRoot.hidden = false;
  modalRoot.innerHTML = buildDrinkModalHtml(item, draft);
  document.body.style.overflow = "hidden";

  const dialog = modalRoot.querySelector(".selection-dialog");
  const closeBtn = modalRoot.querySelector(".modal-close");
  const backdrop = modalRoot.querySelector(".modal-backdrop");
  const confirmBtn = modalRoot.querySelector(".drink-confirm");
  const cancelBtn = modalRoot.querySelector(".drink-cancel");
  const qtyDisplay = modalRoot.querySelector(".drink-qty-value");
  const qtyMinus = modalRoot.querySelector(".drink-qty-minus");
  const qtyPlus = modalRoot.querySelector(".drink-qty-plus");
  const sizeCards = Array.from(modalRoot.querySelectorAll(".drink-size-card"));
  const errorEl = modalRoot.querySelector(".modal-error");

  function closeDrink(result) {
    document.removeEventListener("keydown", handleEscape);
    modalRoot.innerHTML = "";
    modalRoot.hidden = true;
    document.body.style.overflow = "";
    const resolve = activeResolver;
    activeResolver = null;
    resolve(result);
  }

  function handleEscape(e) {
    if (e.key === "Escape") closeDrink(null);
  }

  document.addEventListener("keydown", handleEscape);
  backdrop.addEventListener("click", () => closeDrink(null));
  closeBtn.addEventListener("click", () => closeDrink(null));
  cancelBtn.addEventListener("click", () => closeDrink(null));

  sizeCards.forEach((card) => {
    card.addEventListener("click", () => {
      sizeCards.forEach((c) => c.classList.remove("is-selected"));
      card.classList.add("is-selected");
      draft.tailleBoisson = card.dataset.value;
      draft.prixCalculeCentimes = item.prixCentimes + (draft.tailleBoisson === "grande" ? SUPPLEMENT_GRANDE_CENTIMES : 0);
      errorEl.textContent = "";
    });
  });

  qtyMinus.addEventListener("click", () => {
    if (draft.quantite > QTY_MIN) {
      draft.quantite--;
      qtyDisplay.textContent = draft.quantite;
      qtyMinus.disabled = draft.quantite === QTY_MIN;
    }
  });

  qtyPlus.addEventListener("click", () => {
    draft.quantite++;
    qtyDisplay.textContent = draft.quantite;
    qtyMinus.disabled = false;
  });

  confirmBtn.addEventListener("click", () => {
    if (!draft.tailleBoisson) {
      errorEl.textContent = "Choisissez une taille de boisson.";
      return;
    }
    closeDrink({ ...draft });
  });

  dialog.focus();

  return new Promise((resolve) => {
    activeResolver = resolve;
  });
}

function buildDrinkModalHtml(item, draft) {
  return `
    <div class="modal-backdrop"></div>
    <section class="selection-dialog selection-dialog--drink" role="dialog" aria-modal="true" tabindex="-1">
      <div class="drink-modal-body">
        <button class="modal-close" type="button" aria-label="Fermer">×</button>
        <h2 class="step-title">Une petite soif ?</h2>
        <p class="step-subtitle">Choisissez la taille de votre boisson, +0.50€ pour le format 50 Cl</p>
        <div class="drink-size-grid">
          <button class="drink-size-card ${draft.tailleBoisson === "normale" ? "is-selected" : ""}" type="button" data-value="normale">
            <img src="${escapeHtml(item.image)}" alt="">
            <span>30Cl</span>
          </button>
          <button class="drink-size-card ${draft.tailleBoisson === "grande" ? "is-selected" : ""}" type="button" data-value="grande">
            <img src="${escapeHtml(item.image)}" alt="">
            <span>50Cl</span>
          </button>
        </div>
        <div class="drink-quantity">
          <button class="drink-qty-btn drink-qty-minus" type="button" aria-label="Enlever une boisson" disabled>−</button>
          <span class="drink-qty-value">${draft.quantite}</span>
          <button class="drink-qty-btn drink-qty-plus" type="button" aria-label="Ajouter une boisson">+</button>
        </div>
        <p class="modal-error" role="alert"></p>
      </div>
      <footer class="drink-modal-footer">
        <button class="drink-cancel" type="button">Annuler</button>
        <button class="drink-confirm" type="button">Ajouter à ma commande</button>
      </footer>
    </section>
  `;
}

function openSelectionDialog(config) {
  modalRoot.hidden = false;
  modalRoot.innerHTML = renderDialog(config);
  document.body.style.overflow = "hidden";

  const dialog = modalRoot.querySelector(".selection-dialog");
  const closeButtons = modalRoot.querySelectorAll("[data-modal-close]");
  const confirmButton = modalRoot.querySelector(".confirm-selection");
  const errorElement = modalRoot.querySelector(".modal-error");
  const priceElement = modalRoot.querySelector(".modal-price strong");

  closeButtons.forEach((button) => button.addEventListener("click", closeWithoutSelection));
  modalRoot.addEventListener("click", handleChoiceClick);
  document.addEventListener("keydown", handleEscape);

  confirmButton.addEventListener("click", () => {
    const validationMessage = config.validate();
    if (validationMessage) {
      errorElement.textContent = validationMessage;
      return;
    }
    finishSelection(config.draft);
  });

  updatePriceLabel();
  confirmButton.focus();

  return new Promise((resolve) => {
    activeResolver = resolve;
  });

  function handleChoiceClick(event) {
    const choiceButton = event.target.closest(".choice-button");
    if (!choiceButton || !modalRoot.contains(choiceButton)) {
      return;
    }
    const group = choiceButton.dataset.choice;
    const groupElement = choiceButton.closest("[data-choice-group]");

    if (choiceButton.dataset.optional === "true" && choiceButton.classList.contains("is-selected")) {
      choiceButton.classList.remove("is-selected");
      choiceButton.setAttribute("aria-pressed", "false");
      clearOptionalChoice(config.draft, group);
    } else {
      groupElement.querySelectorAll(".choice-button").forEach((button) => {
        button.classList.remove("is-selected");
        button.setAttribute("aria-pressed", "false");
      });
      choiceButton.classList.add("is-selected");
      choiceButton.setAttribute("aria-pressed", "true");
      config.updateDraft(choiceButton);
    }

    errorElement.textContent = "";
    updatePriceLabel();
  }

  function updatePriceLabel() {
    priceElement.textContent = formatPrice(config.updatePrice());
  }

  function handleEscape(event) {
    if (event.key === "Escape") {
      closeWithoutSelection();
    }
  }

  function closeWithoutSelection() {
    cleanup();
    activeResolver(null);
  }

  function finishSelection(selection) {
    cleanup();
    activeResolver({ ...selection });
  }

  function cleanup() {
    document.removeEventListener("keydown", handleEscape);
    modalRoot.removeEventListener("click", handleChoiceClick);
    modalRoot.innerHTML = "";
    modalRoot.hidden = true;
    document.body.style.overflow = "";
    activeResolver = null;
  }
}

function renderDialog(config) {
  return `
    <div class="modal-backdrop" data-modal-close></div>
    <section class="selection-dialog" role="dialog" aria-modal="true" aria-labelledby="selection-title" tabindex="-1">
      <header class="modal-header">
        <div class="modal-title-group">
          <h2 id="selection-title">${escapeHtml(config.title)}</h2>
          <p>${escapeHtml(config.subtitle)}</p>
        </div>
        <button class="modal-close" type="button" data-modal-close aria-label="Fermer la sélection">×</button>
      </header>
      <div class="modal-content">${config.renderBody()}</div>
      <footer class="modal-footer">
        <div>
          <p class="modal-price">Prix courant <strong>0,00 €</strong></p>
          <p class="modal-error" role="alert"></p>
        </div>
        <button class="confirm-selection" type="button">Ajouter au panier</button>
      </footer>
    </section>
  `;
}

function clearOptionalChoice(draft, group) {
  if (group === "sauce") {
    draft.sauceId = null;
    draft.sauceKey = null;
  }
}

function ensureModalRoot() {
  if (!modalRoot) {
    throw new Error("Modale non initialisée.");
  }
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