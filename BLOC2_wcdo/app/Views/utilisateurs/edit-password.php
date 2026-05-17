<?php
/** @var string $title */
/** @var array{type: string, message: string}|null $flash */
/** @var string $csrfToken */
?>
<div class="page-header">
    <h2>Changer mon mot de passe</h2>
</div>

<?php include __DIR__ . '/../partials/flash.php'; ?>

<div class="card">
    <form method="POST" action="/mon-compte/mot-de-passe" novalidate>
        <input type="hidden" name="_csrf"
               value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">

        <div class="form-group">
            <label for="mot_de_passe_actuel">Mot de passe actuel</label>
            <input type="password"
                   id="mot_de_passe_actuel"
                   name="mot_de_passe_actuel"
                   autocomplete="current-password"
                   required>
        </div>

        <div class="form-group">
            <label for="nouveau_mot_de_passe">Nouveau mot de passe <span class="hint">(8 caractères minimum)</span></label>
            <input type="password"
                   id="nouveau_mot_de_passe"
                   name="nouveau_mot_de_passe"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72"
                   required>
        </div>

        <div class="form-group">
            <label for="confirmation">Confirmer le nouveau mot de passe</label>
            <input type="password"
                   id="confirmation"
                   name="confirmation"
                   autocomplete="new-password"
                   minlength="8"
                   maxlength="72"
                   required>
        </div>

        <div class="form-actions">
            <button type="submit" class="btn btn-primary">Enregistrer</button>
        </div>
    </form>
</div>
