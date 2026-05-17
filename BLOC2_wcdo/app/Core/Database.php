<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Connexion PostgreSQL en singleton.
 */
final class Database
{
    private static ?PDO $instance = null;

    private function __construct()
    {
    }

    // Retourne la connexion PDO unique (crée si absente)
    public static function connection(): PDO
    {
        if (self::$instance instanceof PDO) {
            return self::$instance;
        }

        // Lecture des variables d'environnement Docker avec valeurs par défaut
        $host = getenv('DB_HOST') ?: 'db';
        $port = getenv('DB_PORT') ?: '5432';
        $name = getenv('DB_NAME') ?: 'wacdo_dev';
        $user = getenv('DB_USER') ?: 'wacdo';
        $pass = getenv('DB_PASSWORD') ?: '';

        $dsn = sprintf('pgsql:host=%s;port=%s;dbname=%s', $host, $port, $name);

        // Création de la connexion avec erreurs en exceptions et requêtes préparées natives
        self::$instance = new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);

        return self::$instance;
    }
}
