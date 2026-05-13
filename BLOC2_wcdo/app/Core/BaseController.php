<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Contrôleur de base : rendu de vues, JSON, redirection, garde d'authentification.
 */
abstract class BaseController
{
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

        ob_start();
        require $viewPath;
        $content = ob_get_clean();

        $layoutPath = dirname(__DIR__) . '/Views/layout.php';
        if (is_file($layoutPath)) {
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
     * Vérifie qu'un utilisateur est connecté en session.
     */
    protected function requireAuth(): void
    {
        if (empty($_SESSION['user'])) {
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
            http_response_code(403);
            header('Content-Type: text/html; charset=UTF-8');
            echo '<h1>403 — Accès refusé</h1>';
            exit;
        }
    }
}
