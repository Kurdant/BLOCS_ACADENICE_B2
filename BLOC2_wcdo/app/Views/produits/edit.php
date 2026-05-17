<?php
// Vue : formulaire d'édition d'un produit — Administration uniquement
// Contrôleur : ProduitController::edit
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<string, mixed> $produit */
/** @var array<int, array{id_categorie: int, nom: string}> $categories */
/** @var string $csrfToken */
$produitId = (int) $produit['id_produit'];
?>
<div class="page-header">
    <h2>Modifier un produit</h2>
    <a href="/produits" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/produits/<?= $produitId ?>"
          enctype="multipart/form-data" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   value="<?= htmlspecialchars((string) $produit['nom'], ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="150"
                   required>
        </div>

        <div class="form-group">
            <label for="description">Description <span class="required">*</span></label>
            <textarea id="description"
                      name="description"
                      rows="3"
                      required><?= htmlspecialchars((string) $produit['description'], ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="form-group">
            <label for="prix">Prix (€) <span class="required">*</span></label>
            <input type="number"
                   id="prix"
                   name="prix"
                   value="<?= htmlspecialchars(number_format((float) $produit['prix'], 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>"
                   step="0.01"
                   min="0.01"
                   required>
        </div>

        <div class="form-group">
            <label for="id_categorie">Catégorie <span class="required">*</span></label>
            <select id="id_categorie" name="id_categorie" required>
                <?php foreach ($categories as $cat): ?>
                <option value="<?= (int) $cat['id_categorie'] ?>"
                    <?= (int) $cat['id_categorie'] === (int) $produit['id_categorie'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $cat['nom'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label>
                <input type="checkbox" name="disponible"
                       <?= $produit['disponible'] ? 'checked' : '' ?>>
                Disponible à la vente
            </label>
        </div>

        <div class="form-group">
            <label for="image">Nouvelle image
                <span class="hint">(laisser vide pour conserver l’actuelle — JPEG ou PNG, 2 Mo max)</span>
            </label>
            <?php if ($produit['image'] !== ''): ?>
            <p class="hint">Image actuelle : <?= htmlspecialchars((string) $produit['image'], ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
            <input type="file"
                   id="image"
                   name="image"
                   accept="image/jpeg,image/png">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/produits" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
