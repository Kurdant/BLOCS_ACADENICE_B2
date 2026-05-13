<?php

declare(strict_types=1);

/**
 * Point d'entrée unique de l'application Wacdo Bloc 2.
 */

define('BASE_PATH', dirname(__DIR__));

// -----------------------------------------------------------------------------
// Chargement du fichier .env (parser maison, sans dépendance Composer)
// -----------------------------------------------------------------------------
$envFile = BASE_PATH . '/.env';
if (is_file($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        if (!str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);
        if (
            (str_starts_with($value, '"') && str_ends_with($value, '"')) ||
            (str_starts_with($value, "'") && str_ends_with($value, "'"))
        ) {
            $value = substr($value, 1, -1);
        }
        if (getenv($key) === false) {
            putenv($key . '=' . $value);
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }
    }
}

// -----------------------------------------------------------------------------
// Autoloader PSR-4 minimal : App\ -> app/
// -----------------------------------------------------------------------------
spl_autoload_register(static function (string $class): void {
    $prefix = 'App\\';
    if (!str_starts_with($class, $prefix)) {
        return;
    }
    $relative = substr($class, strlen($prefix));
    $path = BASE_PATH . '/app/' . str_replace('\\', '/', $relative) . '.php';
    if (is_file($path)) {
        require $path;
    }
});

// -----------------------------------------------------------------------------
// Session PHP sécurisée
// -----------------------------------------------------------------------------
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'domain'   => '',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Lax',
]);
session_start();

// -----------------------------------------------------------------------------
// Gestion globale des erreurs
// -----------------------------------------------------------------------------
$debug = (getenv('APP_DEBUG') === 'true');

set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

set_exception_handler(static function (Throwable $e) use ($debug): void {
    http_response_code(500);
    if ($debug) {
        header('Content-Type: text/plain; charset=UTF-8');
        echo "Erreur : " . $e->getMessage() . "\n\n";
        echo $e->getTraceAsString();
        return;
    }
    header('Content-Type: text/html; charset=UTF-8');
    echo '<h1>Erreur serveur</h1><p>Une erreur est survenue. Veuillez réessayer.</p>';
});

// -----------------------------------------------------------------------------
// Routeur : chargement des routes web et API
// -----------------------------------------------------------------------------
$router = new App\Core\Router();

require BASE_PATH . '/routes/web.php';
require BASE_PATH . '/routes/api.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$uri    = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

$router->dispatch($method, $uri);
