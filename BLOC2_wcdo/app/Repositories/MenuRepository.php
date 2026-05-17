<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données de la table `menus`.
 * Les sections et options sont gérées par SectionMenuRepository et OptionMenuRepository.
 */
final class MenuRepository extends BaseRepository
{
    /**
     * Retourne tous les menus (actifs et inactifs), triés par nom.
     * Utilisé par le back-office (page liste menus).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_menu, nom, description, prix, image, disponible, actif,
                    date_creation, date_modification
             FROM menus
             ORDER BY nom ASC'
        );

        return array_map([$this, 'normalizeMenu'], $statement->fetchAll());
    }

    /**
     * Retourne les menus actifs, triés par nom.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_menu, nom, description, prix, image, disponible, actif,
                    date_creation, date_modification
             FROM menus
             WHERE actif = true
             ORDER BY nom ASC'
        );

        return array_map([$this, 'normalizeMenu'], $statement->fetchAll());
    }

    /**
     * Retourne les menus actifs ET disponibles.
     * Utilisé pour la prise de commande (back-office et API).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllAvailableActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_menu, nom, description, prix, image, disponible, actif,
                    date_creation, date_modification
             FROM menus
             WHERE actif = true AND disponible = true
             ORDER BY nom ASC'
        );

        return array_map([$this, 'normalizeMenu'], $statement->fetchAll());
    }

    /**
     * Retourne un menu par son ID, ou null si introuvable.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_menu, nom, description, prix, image, disponible, actif,
                    date_creation, date_modification
             FROM menus
             WHERE id_menu = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeMenu($row) : null;
    }

    /**
     * Insère un nouveau menu et retourne son ID généré.
     *
     * @param array{nom: string, description: string, prix: float|string, image: string} $data
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO menus (nom, description, prix, image)
             VALUES (:nom, :description, :prix, :image)
             RETURNING id_menu'
        );
        $statement->execute([
            'nom'         => (string) $data['nom'],
            'description' => (string) $data['description'],
            'prix'        => (string) $data['prix'],
            'image'       => (string) $data['image'],
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Met à jour les informations d'un menu.
     * L'image n'est mise à jour que si le champ `image` est fourni dans $data.
     *
     * @param array{nom: string, description: string, prix: float|string, disponible: bool, image?: string} $data
     */
    public function update(int $id, array $data): void
    {
        $cols = 'nom         = :nom,
                 description = :description,
                 prix        = :prix,
                 disponible  = :disponible';

        $params = [
            'nom'         => (string) $data['nom'],
            'description' => (string) $data['description'],
            'prix'        => (string) $data['prix'],
            'disponible'  => $data['disponible'] ? 'true' : 'false',
            'id'          => $id,
        ];

        if (isset($data['image'])) {
            $cols .= ', image = :image';
            $params['image'] = (string) $data['image'];
        }

        $statement = $this->pdo->prepare("UPDATE menus SET {$cols} WHERE id_menu = :id");
        $statement->execute($params);
    }

    /**
     * Désactive un menu (soft delete — actif = false).
     */
    public function desactiver(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE menus SET actif = false WHERE id_menu = :id'
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Normalise les types retournés par PDO.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeMenu(array $row): array
    {
        return [
            'id_menu'           => (int) $row['id_menu'],
            'nom'               => (string) $row['nom'],
            'description'       => (string) $row['description'],
            'prix'              => (float) $row['prix'],
            'image'             => (string) $row['image'],
            'disponible'        => $row['disponible'] === 't',
            'actif'             => $row['actif'] === 't',
            'date_creation'     => (string) $row['date_creation'],
            'date_modification' => isset($row['date_modification']) ? (string) $row['date_modification'] : null,
        ];
    }
}
