<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Routeur HTTP minimal avec support des paramètres {id}.
 */
final class Router
{
    /** @var array<string, array<int, array{pattern: string, params: array<int, string>, handler: string}>> */
    private array $routes = [
        'GET'  => [],
        'POST' => [],
    ];

    // Enregistre une route GET
    public function get(string $path, string $handler): void
    {
        $this->add('GET', $path, $handler);
    }

    // Enregistre une route POST
    public function post(string $path, string $handler): void
    {
        $this->add('POST', $path, $handler);
    }

    // Compile la route en regex et stocke le handler avec ses noms de paramètres
    private function add(string $method, string $path, string $handler): void
    {
        $params  = [];
        // Remplace {param} par un groupe capturant et mémorise le nom du paramètre
        $pattern = preg_replace_callback(
            '/\{([a-zA-Z_][a-zA-Z0-9_]*)\}/',
            static function (array $m) use (&$params): string {
                $params[] = $m[1];
                return '([^/]+)';
            },
            $path
        );
        $pattern = '#^' . rtrim($pattern, '/') . '/?$#';

        $this->routes[$method][] = [
            'pattern' => $pattern,
            'params'  => $params,
            'handler' => $handler,
        ];
    }

    // Cherche la route correspondante et appelle le handler, ou renvoie 404/405
    public function dispatch(string $method, string $uri): void
    {
        $method = strtoupper($method);
        $uri    = '/' . trim($uri, '/');
        if ($uri === '/') {
            $uri = '/';
        }

        // Méthode HTTP non enregistrée du tout : vérifier si l'URI existe pour un autre verbe
        if (!isset($this->routes[$method])) {
            foreach ($this->routes as $routes) {
                foreach ($routes as $route) {
                    if (preg_match($route['pattern'], $uri)) {
                        $this->methodNotAllowed();
                        return;
                    }
                }
            }
            $this->notFound();
            return;
        }

        // Phase 1 : tentative de correspondance sur le bon verbe
        foreach ($this->routes[$method] as $route) {
            if (preg_match($route['pattern'], $uri, $matches)) {
                array_shift($matches); // supprime le match complet (élément 0)
                $args = [];
                foreach ($route['params'] as $i => $name) {
                    $args[$name] = $matches[$i] ?? null;
                }
                $this->invoke($route['handler'], $args);
                return;
            }
        }

        // Phase 2 : l'URI existe sur un autre verbe → 405
        foreach ($this->routes as $m => $routes) {
            if ($m === $method) {
                continue;
            }
            foreach ($routes as $route) {
                if (preg_match($route['pattern'], $uri)) {
                    $this->methodNotAllowed();
                    return;
                }
            }
        }

        $this->notFound();
    }

    /**
     * Instancie le contrôleur et appelle la méthode indiquée (format "Classe::methode").
     *
     * @param array<string, string|null> $args
     */
    private function invoke(string $handler, array $args): void
    {
        if (!str_contains($handler, '::')) {
            throw new \RuntimeException("Handler invalide : {$handler}");
        }
        [$class, $action] = explode('::', $handler, 2);
        if (!class_exists($class)) {
            throw new \RuntimeException("Classe introuvable : {$class}");
        }
        $controller = new $class();
        if (!method_exists($controller, $action)) {
            throw new \RuntimeException("Méthode introuvable : {$class}::{$action}");
        }
        $controller->{$action}($args);
    }

    // Renvoie une réponse 404 minimale
    private function notFound(): void
    {
        http_response_code(404);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>404 — Page introuvable</h1>';
    }

    // Renvoie une réponse 405 minimale
    private function methodNotAllowed(): void
    {
        http_response_code(405);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<h1>405 — Méthode non autorisée</h1>';
    }
}
