<?php
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var array<int, array{id_role: int, libelle: string}> $roles */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Créer un utilisateur</h2>
    <a href="/utilisateurs" class="btn btn-secondary">Retour</a>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/utilisateurs" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="identifiant">Identifiant <span class="required">*</span></label>
            <input type="text"
                   id="identifiant"
                   name="identifiant"
                   maxlength="100"
                   autocomplete="username"
                   required>
        </div>

        <div class="form-group">
            <label for="nom">Nom <span class="required">*</span></label>
            <input type="text"
                   id="nom"
                   name="nom"
                   maxlength="100"
                   autocomplete="family-name"
                   required>
        </div>

        <div class="form-group">
            <label for="prenom">Prénom <span class="required">*</span></label>
            <input type="text"
                   id="prenom"
                   name="prenom"
                   maxlength="100"
                   autocomplete="given-name"
                   required>
        </div>

        <div class="form-group">
            <label for="id_role">Rôle <span class="required">*</span></label>
            <select id="id_role" name="id_role" required>
                <option value="">-- Choisir un rôle --</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?= (int) $role['id_role'] ?>">
                    <?= htmlspecialchars((string) $role['libelle'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group">
            <label for="mot_de_passe">Mot de passe <span class="required">*</span>
                <span class="hint">(8 caractères minimum)</span>
            </label>
            <input type="password"
                   id="mot_de_passe"
                   name="mot_de_passe"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72"
                   required>
        </div>

        <div class="form-group">
            <label for="confirmation">Confirmer le mot de passe <span class="required">*</span></label>
            <input type="password"
                   id="confirmation"
                   name="confirmation"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72"
                   required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Créer</button>
            <a href="/utilisateurs" class="btn btn-secondary">Annuler</a>
        </div>
    </form>
</div>
