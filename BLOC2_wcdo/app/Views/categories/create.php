<?php
// Vue : formulaire de création d'une catégorie — Administration uniquement
// Contrôleur : CategorieController::create
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Créer une catégorie</h2>
    <a href="/categories" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/categories" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   maxlength="100"
                   required>
        </div>

        <div class="form-group">
            <label for="description">Description
                <span class="hint">(optionnelle, 500 caractères max)</span>
            </label>
            <textarea id="description"
                      name="description"
                      maxlength="500"
                      rows="3"></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="/categories" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
