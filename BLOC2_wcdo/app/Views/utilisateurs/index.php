<?php
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<int, array<string, mixed>> $users */
/** @var int $currentUserId */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Utilisateurs</h2>
    <a href="/utilisateurs/creer" class="btn btn-primary">Créer un utilisateur</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <?php if (empty($users)): ?>
        <p class="empty-state">Aucun utilisateur trouvé.</p>
    <?php else: ?>
    <table class="table">
        <thead>
            <tr>
                <th>Identifiant</th>
                <th>Nom</th>
                <th>Prénom</th>
                <th>Rôle</th>
                <th>Statut</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($users as $user): ?>
            <tr class="<?= $user['actif'] ? '' : 'row-inactive' ?>">
                <td><?= htmlspecialchars((string) $user['identifiant'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $user['nom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $user['prenom'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= htmlspecialchars((string) $user['role'], ENT_QUOTES, 'UTF-8') ?></td>
                <td>
                    <?php if ($user['actif']): ?>
                        <span class="badge badge-success">Actif</span>
                    <?php else: ?>
                        <span class="badge badge-inactive">Inactif</span>
                    <?php endif; ?>
                </td>
                <td class="actions">
                    <a href="/utilisateurs/<?= (int) $user['id_utilisateur'] ?>/editer"
                       class="btn btn-secondary btn-sm">Modifier</a>

                    <?php if ($user['actif'] && (int) $user['id_utilisateur'] !== $currentUserId): ?>
                    <form method="POST"
                          action="/utilisateurs/<?= (int) $user['id_utilisateur'] ?>/desactiver"
                          onsubmit="return confirm(<?= htmlspecialchars(
                              json_encode('Désactiver « ' . $user['identifiant'] . ' » ?', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT),
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
