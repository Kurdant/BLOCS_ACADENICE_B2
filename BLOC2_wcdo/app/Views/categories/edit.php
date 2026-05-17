<?php
// Vue : formulaire d'édition d'une catégorie — Administration uniquement
// Contrôleur : CategorieController::edit
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array{id_categorie: int, nom: string, description: string|null, actif: bool} $categorie */
/** @var string $csrfToken */
$categorieId = (int) $categorie['id_categorie'];
?>
<div class="page-header">
    <h2>Modifier une catégorie</h2>
    <a href="/categories" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/categories/<?= $categorieId ?>" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   value="<?= htmlspecialchars((string) $categorie['nom'], ENT_QUOTES, 'UTF-8') ?>"
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
                      rows="3"><?= htmlspecialchars((string) ($categorie['description'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/categories" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
