<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

/**
 * Dépôt de base : reçoit la connexion PDO partagée.
 */
abstract class BaseRepository
{
    protected PDO $pdo;

    // Injecte la connexion PDO ou utilise le singleton partagé
    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }
}
