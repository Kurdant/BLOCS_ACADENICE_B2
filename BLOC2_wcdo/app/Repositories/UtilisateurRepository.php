<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

final class UtilisateurRepository extends BaseRepository
{
    /**
     * Retourne un utilisateur actif avec son rôle, recherché par ID.
     *
     * @return array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, actif: bool, role: string}|null
     */
    public function findActiveWithRoleById(int $id): ?array
    {
        $sql = <<<'SQL'
            SELECT
                utilisateurs.id_utilisateur,
                utilisateurs.identifiant,
                utilisateurs.nom,
                utilisateurs.prenom,
                utilisateurs.actif,
                roles.libelle AS role
            FROM utilisateurs
            INNER JOIN roles ON roles.id_role = utilisateurs.id_role
            WHERE utilisateurs.id_utilisateur = :id_utilisateur
              AND utilisateurs.actif = true
            LIMIT 1
        SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['id_utilisateur' => $id]);
        $user = $statement->fetch();

        return is_array($user) ? $this->normalizeUser($user) : null;
    }

    /**
     * Retourne un utilisateur actif avec son rôle, recherché par identifiant.
     *
     * @return array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, actif: bool, role: string}|null
     */
    public function findActiveWithRoleByIdentifiant(string $identifiant): ?array
    {
        $sql = <<<'SQL'
            SELECT
                utilisateurs.id_utilisateur,
                utilisateurs.identifiant,
                utilisateurs.nom,
                utilisateurs.prenom,
                utilisateurs.actif,
                roles.libelle AS role
            FROM utilisateurs
            INNER JOIN roles ON roles.id_role = utilisateurs.id_role
            WHERE utilisateurs.identifiant = :identifiant
              AND utilisateurs.actif = true
            LIMIT 1
        SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['identifiant' => $identifiant]);
        $user = $statement->fetch();

        return is_array($user) ? $this->normalizeUser($user) : null;
    }

    /**
     * Retourne les données d'authentification incluant le hash du mot de passe.
     * Utilisé exclusivement par AuthService — ne jamais exposer le hash ailleurs.
     *
     * @return array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, role: string, mot_de_passe_hash: string}|null
     */
    public function findForAuth(string $identifiant): ?array
    {
        $sql = <<<'SQL'
            SELECT
                utilisateurs.id_utilisateur,
                utilisateurs.identifiant,
                utilisateurs.nom,
                utilisateurs.prenom,
                utilisateurs.mot_de_passe_hash,
                roles.libelle AS role
            FROM utilisateurs
            INNER JOIN roles ON roles.id_role = utilisateurs.id_role
            WHERE utilisateurs.identifiant = :identifiant
              AND utilisateurs.actif = true
            LIMIT 1
        SQL;

        $statement = $this->pdo->prepare($sql);
        $statement->execute(['identifiant' => $identifiant]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            return null;
        }

        return [
            'id_utilisateur'    => (int) $user['id_utilisateur'],
            'identifiant'       => (string) $user['identifiant'],
            'nom'               => (string) $user['nom'],
            'prenom'            => (string) $user['prenom'],
            'mot_de_passe_hash' => (string) $user['mot_de_passe_hash'],
            'role'              => (string) $user['role'],
        ];
    }

    /**
     * Retourne uniquement le hash du mot de passe d'un utilisateur actif.
     * Utilisé pour vérifier le mot de passe actuel avant changement.
     */
    public function findHashById(int $id): ?string
    {
        $statement = $this->pdo->prepare(
            'SELECT mot_de_passe_hash FROM utilisateurs WHERE id_utilisateur = :id AND actif = true LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? (string) $row['mot_de_passe_hash'] : null;
    }

    /**
     * Met à jour le hash du mot de passe d'un utilisateur.
     */
    public function updatePasswordById(int $id, string $newHash): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE utilisateurs SET mot_de_passe_hash = :hash WHERE id_utilisateur = :id AND actif = true'
        );
        $statement->execute(['hash' => $newHash, 'id' => $id]);
    }

    // =========================================================================
    // Méthodes CRUD — utilisées par Sprint 4 (Gestion utilisateurs)
    // =========================================================================

    /**
     * Retourne un utilisateur par son ID avec son rôle, sans filtre actif.
     * Utilisé par les formulaires d'édition (l'admin peut modifier un utilisateur inactif).
     *
     * @return array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, actif: bool, role: string, id_role: int}|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                utilisateurs.id_utilisateur,
                utilisateurs.identifiant,
                utilisateurs.nom,
                utilisateurs.prenom,
                utilisateurs.actif,
                utilisateurs.id_role,
                roles.libelle AS role
             FROM utilisateurs
             INNER JOIN roles ON roles.id_role = utilisateurs.id_role
             WHERE utilisateurs.id_utilisateur = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $user = $statement->fetch();

        if (!is_array($user)) {
            return null;
        }

        return [
            'id_utilisateur' => (int) $user['id_utilisateur'],
            'identifiant'    => (string) $user['identifiant'],
            'nom'            => (string) $user['nom'],
            'prenom'         => (string) $user['prenom'],
            'actif'          => $user['actif'] === 't',
            'id_role'        => (int) $user['id_role'],
            'role'           => (string) $user['role'],
        ];
    }

    /**
     * Retourne tous les utilisateurs avec leur rôle, triés par nom puis prénom.
     * Utilisé par la page liste utilisateurs (rôle Administration uniquement).
     *
     * @return array<int, array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, actif: bool, role: string}>
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                utilisateurs.id_utilisateur,
                utilisateurs.identifiant,
                utilisateurs.nom,
                utilisateurs.prenom,
                utilisateurs.actif,
                roles.libelle AS role
             FROM utilisateurs
             INNER JOIN roles ON roles.id_role = utilisateurs.id_role
             ORDER BY utilisateurs.nom ASC, utilisateurs.prenom ASC'
        );

        return array_map([$this, 'normalizeUser'], $statement->fetchAll());
    }

    /**
     * Retourne la liste complète des rôles disponibles.
     * Utilisé pour alimenter la liste déroulante du formulaire utilisateur.
     *
     * @return array<int, array{id_role: int, libelle: string}>
     */
    public function findAllRoles(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_role, libelle FROM roles ORDER BY libelle ASC'
        );

        return array_map(static function (array $row): array {
            return [
                'id_role' => (int) $row['id_role'],
                'libelle' => (string) $row['libelle'],
            ];
        }, $statement->fetchAll());
    }

    /**
     * Vérifie si un identifiant existe déjà en base.
     * Le paramètre $excludeId permet d'exclure l'utilisateur en cours de modification
     * (pour ne pas signaler de doublon sur lui-même lors d'un update).
     */
    public function existsByIdentifiant(string $identifiant, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM utilisateurs
                 WHERE identifiant = :identifiant
                   AND id_utilisateur <> :exclude
                 LIMIT 1'
            );
            $statement->execute(['identifiant' => $identifiant, 'exclude' => $excludeId]);
        } else {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM utilisateurs WHERE identifiant = :identifiant LIMIT 1'
            );
            $statement->execute(['identifiant' => $identifiant]);
        }

        return $statement->fetch() !== false;
    }

    /**
     * Insère un nouvel utilisateur et retourne son ID généré.
     *
     * @param string $hash Hash bcrypt du mot de passe (généré avant l'appel, cost 12)
     */
    public function create(
        string $identifiant,
        string $nom,
        string $prenom,
        string $hash,
        int    $idRole
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO utilisateurs (identifiant, nom, prenom, mot_de_passe_hash, id_role)
             VALUES (:identifiant, :nom, :prenom, :hash, :id_role)
             RETURNING id_utilisateur'
        );
        $statement->execute([
            'identifiant' => $identifiant,
            'nom'         => $nom,
            'prenom'      => $prenom,
            'hash'        => $hash,
            'id_role'     => $idRole,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Met à jour les informations d'un utilisateur (sans toucher au mot de passe).
     * La modification du mot de passe passe exclusivement par updatePasswordById().
     */
    public function update(
        int    $id,
        string $nom,
        string $prenom,
        string $identifiant,
        int    $idRole
    ): void {
        $statement = $this->pdo->prepare(
            'UPDATE utilisateurs
             SET nom = :nom, prenom = :prenom, identifiant = :identifiant, id_role = :id_role
             WHERE id_utilisateur = :id'
        );
        $statement->execute([
            'nom'         => $nom,
            'prenom'      => $prenom,
            'identifiant' => $identifiant,
            'id_role'     => $idRole,
            'id'          => $id,
        ]);
    }

    /**
     * Retourne le nombre d'utilisateurs actifs ayant un rôle donné.
     * Utilisé pour protéger le dernier compte Administration actif.
     */
    public function countActiveByRole(string $role): int
    {
        $statement = $this->pdo->prepare(
            'SELECT COUNT(*) AS nb
             FROM utilisateurs
             INNER JOIN roles ON roles.id_role = utilisateurs.id_role
             WHERE roles.libelle = :role
               AND utilisateurs.actif = true'
        );
        $statement->execute(['role' => $role]);
        $row = $statement->fetch();
        return (int) ($row['nb'] ?? 0);
    }

    /**
     * Désactive un utilisateur (soft delete — actif = false).
     * Un utilisateur désactivé ne peut plus se connecter.
     * La ligne est conservée pour l'historique des commandes.
     */
    public function desactiver(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE utilisateurs SET actif = false WHERE id_utilisateur = :id'
        );
        $statement->execute(['id' => $id]);
    }

    // =========================================================================

    /**
     * Normalise les types des champs retournés par PDO (string → types natifs PHP).
     *
     * @param array<string, mixed> $user
     * @return array{id_utilisateur: int, identifiant: string, nom: string, prenom: string, actif: bool, role: string}
     */
    private function normalizeUser(array $user): array
    {
        return [
            'id_utilisateur' => (int) $user['id_utilisateur'],
            'identifiant'    => (string) $user['identifiant'],
            'nom'            => (string) $user['nom'],
            'prenom'         => (string) $user['prenom'],
            'actif'          => $user['actif'] === 't',
            'role'           => (string) $user['role'],
        ];
    }
}