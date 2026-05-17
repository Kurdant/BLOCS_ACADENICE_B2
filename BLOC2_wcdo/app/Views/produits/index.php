<?php
// Vue : liste des produits — Administration uniquement
// Contrôleur : ProduitController::index
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<int, array<string, mixed>> $produits */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Produits</h2>
    <a href="/produits/creer" class="btn btn-primary">Créer un produit</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <?php if (empty($produits)): ?>
        <p class="empty-state">Aucun produit trouvé.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Catégorie</th>
                <th>Prix</th>
                <th>Disponible</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($produits as $produit): ?>
            <tr class="<?= $produit['actif'] ? '' : 'row-inactive' ?>">
                <td><?= htmlspecialchars((string) $produit['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $produit['categorie_nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= number_format((float) $produit['prix'], 2, ',', ' ') ?>&nbsp;€</td>
                <td>
                    <?php if ($produit['disponible']): ?>
                        <span class="badge badge-success">Oui</span>
                    <?php else: ?>
                        <span class="badge badge-warning">Non</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($produit['actif']): ?>
                        <span class="badge badge-success">Actif</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Inactif</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="/produits/<?= (int) $produit['id_produit'] ?>/editer"
                       class="btn btn-secondary btn-sm">Modifier</a>

                    <?php if ($produit['actif']): ?>
                    <form method="POST"
                          action="/produits/<?= (int) $produit['id_produit'] ?>/desactiver"
                          onsubmit="return confirm(<?= htmlspecialchars(
                              json_encode('Désactiver le produit « ' . $produit['nom'] . ' » ?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT),
                              ENT_QUOTES,
                              'UTF-8'
                          ) ?>)">
                        <input type="hidden" name="_csrf"
                               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <button type="submit" class="btn btn-danger btn-sm">Désactiver</button>
                    </form>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>
