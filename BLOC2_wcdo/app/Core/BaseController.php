<?php

declare(strict_types=1);

namespace App\Core;

use App\Repositories\UtilisateurRepository;

/**
 * Contrôleur de base : rendu de vues, JSON, redirection, garde d'authentification.
 */
abstract class BaseController
{
    private const VALID_ROLES = ['Administration', 'Preparation', 'Accueil'];

    /**
     * Rendu d'une vue avec layout.
     *
     * @param array<string, mixed> $data
     */
    protected function view(string $name, array $data = []): void
    {
        extract($data, EXTR_SKIP);

        $viewPath = dirname(__DIR__) . '/Views/' . $name . '.php';
        if (!is_file($viewPath)) {
            throw new \RuntimeException("Vue introuvable : {$name}");
        }

        // Capture le rendu de la vue dans un buffer
        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        // Injecte le contenu dans le layout si disponible et non désactivé
        $layoutPath = dirname(__DIR__) . '/Views/layout.php';
        if (is_file($layoutPath) && ($data['layout'] ?? true) !== false) {
            $title = $data['title'] ?? 'Wacdo';
            require $layoutPath;
            return;
        }

        echo $content;
    }

    /**
     * Réponse JSON.
     *
     * @param mixed $data
     */
    protected function json($data, int $status = 200): void
    {
        http_response_code($status);
        header('Content-Type: application/json; charset=UTF-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Redirection HTTP.
     */
    protected function redirect(string $url): void
    {
        header('Location: ' . $url);
        exit;
    }

    /**
     * Utilisateur courant stocké en session.
     *
     * @return array{id: int, identifiant: string, nom: string, prenom: string, role: string}|null
     */
    protected function currentUser(): ?array
    {
        if (empty($_SESSION['user']) || !is_array($_SESSION['user'])) {
            return null;
        }

        return $_SESSION['user'];
    }

    /**
     * Vérifie qu'un utilisateur est connecté et toujours actif en base.
     */
    protected function requireAuth(): void
    {
        if ($this->refreshAuthenticatedUser() === null) {
            $this->destroySession();
            $this->redirect('/login');
        }
    }

    /**
     * Vérifie que l'utilisateur courant possède l'un des rôles attendus.
     *
     * @param array<int, string> $roles
     */
    protected function requireRole(array $roles): void
    {
        $this->requireAuth();
        $userRole = $_SESSION['user']['role'] ?? null;
        if (!in_array($userRole, $roles, true)) {
            $this->forbidden();
        }
    }

    /**
     * Relit le compte actif et son rôle depuis la base.
     *
     * @return array{id: int, identifiant: string, nom: string, prenom: string, role: string}|null
     */
    protected function refreshAuthenticatedUser(): ?array
    {
        $sessionUser = $_SESSION['user'] ?? null;
        if (!is_array($sessionUser)) {
            return null;
        }

        $userId = filter_var($sessionUser['id'] ?? null, FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1],
        ]);
        if ($userId === false) {
            return null;
        }

        // Recharge le compte depuis la BDD (vérifie actif + rôle valide)
        $repository = new UtilisateurRepository();
        $user = $repository->findActiveWithRoleById((int) $userId);
        if ($user === null || !in_array($user['role'], self::VALID_ROLES, true)) {
            return null;
        }

        $_SESSION['user'] = [
            'id'          => (int) $user['id_utilisateur'],
            'identifiant' => (string) $user['identifiant'],
            'nom'         => (string) $user['nom'],
            'prenom'      => (string) $user['prenom'],
            'role'        => (string) $user['role'],
        ];

        return $_SESSION['user'];
    }

    // Retourne le token CSRF actuel ou en génère un nouveau si absent
    protected function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token']) || !is_string($_SESSION['csrf_token'])) {
            $this->regenerateCsrfToken();
        }

        return $_SESSION['csrf_token'];
    }

    // Valide le token CSRF du formulaire et en génère un nouveau (rotation à chaque POST)
    protected function requireCsrf(): void
    {
        $sessionToken = $_SESSION['csrf_token'] ?? null;
        $postedToken = $_POST['_csrf'] ?? null;

        // Les deux tokens doivent être des chaînes non vides
        if (!is_string($sessionToken) || !is_string($postedToken)) {
            $this->abort(403);
        }

        // Comparaison en temps constant pour éviter les attaques par timing
        if (!hash_equals($sessionToken, $postedToken)) {
            $this->abort(403);
        }

        $this->regenerateCsrfToken();
    }

    // Génère un nouveau token CSRF aléatoire et le stocke en session
    protected function regenerateCsrfToken(): void
    {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    // Stocke un message flash (type : 'success', 'error') en session
    protected function flash(string $type, string $message): void
    {
        $_SESSION['flash'] = [
            'type'    => $type,
            'message' => $message,
        ];
    }

    /**
     * @return array{type: string, message: string}|null
     */
    protected function getFlash(): ?array
    {
        if (empty($_SESSION['flash']) || !is_array($_SESSION['flash'])) {
            return null;
        }

        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);

        return $flash;
    }

    // Lit une valeur depuis POST puis GET, ou retourne $default
    protected function input(string $key, mixed $default = null): mixed
    {
        if (array_key_exists($key, $_POST)) {
            return $_POST[$key];
        }

        if (array_key_exists($key, $_GET)) {
            return $_GET[$key];
        }

        return $default;
    }

    // Envoie une réponse HTTP d'erreur et arrête le script
    protected function abort(int $status): void
    {
        $messages = [
            400 => '400 — Requête invalide',
            403 => '403 — Accès refusé',
            404 => '404 — Page introuvable',
            405 => '405 — Méthode non autorisée',
        ];

        if (!array_key_exists($status, $messages)) {
            throw new \InvalidArgumentException("Code HTTP non autorisé : {$status}");
        }

        http_response_code($status);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>' . htmlspecialchars($messages[$status], ENT_QUOTES, 'UTF-8') . '</h1>';
        exit;
    }

    // Raccourci vers abort(403)
    protected function forbidden(): void
    {
        $this->abort(403);
    }

    // Détruit complètement la session et redémarre une session vierge pour le flash post-logout
    protected function destroySession(): void
    {
        $_SESSION = [];

        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        // Invalide le cookie de session côté client
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, [
                'path'     => $params['path'],
                'domain'   => $params['domain'],
                'secure'   => $params['secure'],
                'httponly' => $params['httponly'],
                'samesite' => $params['samesite'] ?? 'Lax',
            ]);
        }

        session_destroy();

        // Redémarre une session vierge pour permettre le stockage du flash post-logout
        session_start();
    }
}
