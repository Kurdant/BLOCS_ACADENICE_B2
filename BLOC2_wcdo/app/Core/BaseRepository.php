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

    public function __construct(?PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }
}
