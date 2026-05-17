<?php
/** @var string $title */
/** @var string $content */
/** @var string[] $extraCss   CSS supplémentaires injectés par la vue courante */
$title    = $title    ?? 'Wacdo';
$extraCss = $extraCss ?? [];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($title, ENT_QUOTES, 'UTF-8') ?> — Wacdo</title>
    <link rel="stylesheet" href="/css/style.css">
    <link rel="stylesheet" href="/css/layout.css">
    <link rel="stylesheet" href="/css/components.css">
    <?php foreach ($extraCss as $css): ?>
    <link rel="stylesheet" href="<?= htmlspecialchars($css, ENT_QUOTES, 'UTF-8') ?>">
    <?php endforeach; ?>
</head>
<body>
<div class="site-wrapper">
    <header class="site-header">
        <h1>Wacdo</h1>
        <?php if (!empty($_SESSION['user'])): ?>
        <div class="header-user">
            <?= htmlspecialchars($_SESSION['user']['identifiant'] ?? '', ENT_QUOTES, 'UTF-8') ?>
            <form method="POST" action="/logout" style="display:inline">
                <input type="hidden" name="_csrf"
                       value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <button type="submit" class="btn btn-secondary btn-sm">Déconnexion</button>
            </form>
        </div>
        <?php endif; ?>
    </header>

    <aside class="site-sidebar">
        <nav aria-label="Navigation principale">
            <ul>
                <li class="nav-section">Commandes</li>
                <li><a href="/commandes">Liste des commandes</a></li>

                <li class="nav-section">Catalogue</li>
                <?php if (($_SESSION['user']['role'] ?? '') === 'Administration'): ?>
                <li><a href="/produits">Produits</a></li>
                <li><a href="/categories">Catégories</a></li>
                <?php endif; ?>

                <li class="nav-section">Gestion</li>
                <?php if (($_SESSION['user']['role'] ?? '') === 'Administration'): ?>
                <li><a href="/utilisateurs">Utilisateurs</a></li>
                <?php endif; ?>

                <li class="nav-section">Mon compte</li>
                <li><a href="/mon-compte/mot-de-passe">Changer mon mot de passe</a></li>
            </ul>
        </nav>
    </aside>

    <main class="site-main">
        <?= $content ?>
    </main>

    <footer class="site-footer">
        <small>Wacdo — Bloc 2</small>
    </footer>
</div>
<script src="/js/app.js"></script>
</body>
</html>
