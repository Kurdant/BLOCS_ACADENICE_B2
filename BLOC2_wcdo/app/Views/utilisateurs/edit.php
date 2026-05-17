<?php
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<string, mixed> $user */
/** @var array<int, array{id_role: int, libelle: string}> $roles */
/** @var string $csrfToken */
$userId = (int) $user['id_utilisateur'];
?>
<div class="page-header">
    <h2>Modifier un utilisateur</h2>
    <a href="/utilisateurs" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/utilisateurs/<?= $userId ?>" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="identifiant">Identifiant <span class="required">*</span></label>
            <input type="text"
                   id="identifiant"
                   name="identifiant"
                   value="<?= htmlspecialchars((string) $user['identifiant'], ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="100"
                   autocomplete="username"
                   required>
        </div>

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   value="<?= htmlspecialchars((string) $user['nom'], ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="100"
                   autocomplete="family-name"
                   required>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom <span class="required">*</span></label>
            <input type="text"
                   id="prenom"
                   name="prenom"
                   value="<?= htmlspecialchars((string) $user['prenom'], ENT_QUOTES, 'UTF-8') ?>"
                   maxlength="100"
                   autocomplete="given-name"
                   required>
        </div>

        <div class="form-group">
            <label for="id_role">Rôle <span class="required">*</span></label>
            <select id="id_role" name="id_role" required>
                <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id_role'] ?>"
                    <?= (int) $role['id_role'] === (int) $user['id_role'] ? 'selected' : '' ?>>
                    <?= htmlspecialchars((string) $role['libelle'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="mot_de_passe">Nouveau mot de passe
                <span class="hint">(laisser vide pour ne pas changer)</span>
            </label>
            <input type="password"
                   id="mot_de_passe"
                   name="mot_de_passe"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72">
        </div>

        <div class="form-group">
            <label for="confirmation">Confirmer le nouveau mot de passe</label>
            <input type="password"
                   id="confirmation"
                   name="confirmation"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72">
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
            <a href="/utilisateurs" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
