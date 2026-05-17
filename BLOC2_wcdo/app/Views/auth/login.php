<?php
// Vue : page de connexion — sans layout, pleine page
// Contrôleur : AuthController::showLogin

declare(strict_types=1);

/** @var array{type: string, message: string}|null $flash */
/** @var string $identifiant Dernier identifiant saisi (réaffiché en cas d'erreur) */
$identifiant = htmlspecialchars((string) ($identifiant ?? ''), ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion — Wacdo</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/components.css">
    <link rel="stylesheet" href="/css/login.css">
</head>
<body>
    <div class="login">
        <h2>Connexion</h2>

        <?php include __DIR__ . '/../partials/flash.php'; ?>

        <form method="POST" action="/login" novalidate>
            <input type="hidden" name="_csrf"
                   value="<?= htmlspecialchars($csrfToken ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <div class="form-group">
                <label for="identifiant">Identifiant</label>
                <input
                    type="text"
                    id="identifiant"
                    name="identifiant"
                    value="<?= $identifiant ?>"
                    autocomplete="username"
                    required
                    autofocus
                >
            </div>
            <div class="form-group">
                <label for="mot_de_passe">Mot de passe</label>
                <input
                    type="password"
                    id="mot_de_passe"
                    name="mot_de_passe"
                    autocomplete="current-password"
                    required
                >
            </div>
            <button type="submit">Se connecter</button>
        </form>
    </div>
</body>
</html>
