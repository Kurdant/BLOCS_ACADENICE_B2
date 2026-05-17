<?php
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<int, array{id_categorie: int, nom: string, description: string|null, actif: bool}> $categories */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Catégories</h2>
    <a href="/categories/creer" class="btn btn-primary">Créer une catégorie</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <?php if (empty($categories)): ?>
        <p class="empty-state">Aucune catégorie trouvée.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Nom</th>
                <th>Description</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($categories as $categorie): ?>
            <tr class="<?= $categorie['actif'] ? '' : 'row-inactive' ?>">
                <td><?= htmlspecialchars((string) $categorie['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) ($categorie['description'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ($categorie['actif']): ?>
                        <span class="badge badge-success">Active</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Inactive</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="/categories/<?= (int) $categorie['id_categorie'] ?>/editer"
                       class="btn btn-secondary btn-sm">Modifier</a>

                    <?php if ($categorie['actif']): ?>
                    <form method="POST"
                          action="/categories/<?= (int) $categorie['id_categorie'] ?>/desactiver"
                          onsubmit="return confirm(<?= htmlspecialchars(
                              json_encode('Désactiver la catégorie « ' . $categorie['nom'] . ' » ?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT),
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
