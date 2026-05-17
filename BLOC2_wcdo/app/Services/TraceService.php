<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Database;

/**
 * Service de traçabilité des actions back-office.
 * Enregistre chaque opération sensible (création, modification, désactivation)
 * dans la table `traces_actions` pour l'audit.
 */
final class TraceService
{
    private \PDO $pdo;

    /**
     * @param \PDO|null $pdo Instance PDO optionnelle — utilise Database::connection() si null
     */
    public function __construct(?\PDO $pdo = null)
    {
        $this->pdo = $pdo ?? Database::connection();
    }
    /**
     * Enregistre une action dans la table `traces_actions`.
     *
     * L'ID utilisateur est lu depuis la session courante.
     * Si aucun utilisateur n'est connecté (API, tâche système), on stocke NULL.
     *
     * @param string      $action      Libellé de l'action : 'creation', 'modification', 'desactivation'
     * @param string      $tableCible  Nom de la table concernée : 'produits', 'categories', etc.
     * @param int|null    $idCible     ID de la ligne concernée dans la table cible (null si non applicable)
     * @param string|null $details     Informations complémentaires libres (JSON ou texte)
     */
    public function log(
        string  $action,
        string  $tableCible,
        ?int    $idCible,
        ?string $details = null
    ): void {
        // Sécurité défensive : si la session n'est pas active, ne pas planter l'action métier
        // (en pratique session_start() est toujours appelé dans index.php)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            return;
        }

        // Lire l'ID utilisateur depuis la session — null pour les appels API sans authentification
        $idUtilisateur = $_SESSION['user']['id'] ?? null;

        $statement = $this->pdo->prepare(
            'INSERT INTO traces_actions (id_utilisateur, action, table_cible, id_cible, details)
             VALUES (:id_utilisateur, :action, :table_cible, :id_cible, :details)'
        );

        $statement->execute([
            'id_utilisateur' => $idUtilisateur,
            'action'         => $action,
            'table_cible'    => $tableCible,
            'id_cible'       => $idCible,
            'details'        => $details,
        ]);
    }
}
