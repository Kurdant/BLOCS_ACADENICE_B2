<?php
// Vue : formulaire de création d'un produit — Administration uniquement
// Contrôleur : ProduitController::create
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<int, array{id_categorie: int, nom: string}> $categories */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Créer un produit</h2>
    <a href="/produits" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/produits"
          enctype="multipart/form-data" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   maxlength="150"
                   required>
        </div>

        <div class="form-group">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description"
                      name="description"
                      rows="3"
                      required></textarea>
        </div>

        <div class="form-group">
            <label for="prix">Prix (€) <span class="required">*</span></label>
            <input type="number"
                   id="prix"
                   name="prix"
                   step="0.01"
                   min="0.01"
                   required>
        </div>

        <div class="form-group">
            <label for="id_categorie">Catégorie <span class="required">*</span></label>
            <select id="id_categorie" name="id_categorie" required>
                <option value="">-- Choisir une catégorie --</option>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id_categorie'] ?>">
                    <?= htmlspecialchars((string) $cat['nom'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="image">Image <span class="required">*</span>
                <span class="hint">(JPEG ou PNG, 2 Mo max)</span>
            </label>
            <input type="file"
                   id="image"
                   name="image"
                   accept="image/jpeg,image/png"
                   required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="/produits" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
