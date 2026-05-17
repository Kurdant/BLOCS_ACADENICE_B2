<?php
// Partial : messages flash (succès / erreur) affichés après une action
// Inclus dans layout.php — $flash est extrait par BaseController::view()

declare(strict_types=1);

/** @var array{type: string, message: string}|null $flash */
if (empty($flash) || !is_array($flash)) {
    return;
}

$type    = in_array($flash['type'], ['success', 'error', 'warning', 'info'], true)
    ? $flash['type']
    : 'info';
$message = htmlspecialchars((string) ($flash['message'] ?? ''), ENT_QUOTES, 'UTF-8');
?>
<div class="alert alert-<?= $type ?>" role="alert">
    <?= $message ?>
</div>
