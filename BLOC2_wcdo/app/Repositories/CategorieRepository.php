<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données de la table `categories`.
 * Toutes les méthodes utilisent des requêtes préparées.
 */
final class CategorieRepository extends BaseRepository
{
    /**
     * Retourne toutes les catégories (actives et inactives), triées par nom.
     * Utilisé par le back-office (page liste catégories).
     *
     * @return array<int, array{id_categorie: int, nom: string, description: string|null, actif: bool}>
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_categorie, nom, description, actif
             FROM categories
             ORDER BY nom ASC'
        );

        return array_map([$this, 'normalizeCategorie'], $statement->fetchAll());
    }

    /**
     * Retourne uniquement les catégories actives, triées par nom.
     * Utilisé dans les formulaires de produits (liste déroulante).
     *
     * @return array<int, array{id_categorie: int, nom: string, description: string|null, actif: bool}>
     */
    public function findAllActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT id_categorie, nom, description, actif
             FROM categories
             WHERE actif = true
             ORDER BY nom ASC'
        );

        return array_map([$this, 'normalizeCategorie'], $statement->fetchAll());
    }

    /**
     * Retourne une catégorie par son ID, ou null si introuvable.
     *
     * @return array{id_categorie: int, nom: string, description: string|null, actif: bool}|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_categorie, nom, description, actif
             FROM categories
             WHERE id_categorie = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeCategorie($row) : null;
    }

    /**
     * Insère une nouvelle catégorie et retourne son ID généré.
     */
    public function create(string $nom, ?string $description): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO categories (nom, description)
             VALUES (:nom, :description)
             RETURNING id_categorie'
        );
        $statement->execute([
            'nom'         => $nom,
            'description' => $description,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Met à jour le nom et la description d'une catégorie.
     */
    public function update(int $id, string $nom, ?string $description): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE categories
             SET nom = :nom, description = :description
             WHERE id_categorie = :id'
        );
        $statement->execute([
            'nom'         => $nom,
            'description' => $description,
            'id'          => $id,
        ]);
    }

    /**
     * Désactive une catégorie (soft delete — actif = false).
     * La ligne n'est pas supprimée pour préserver l'historique des produits.
     */
    public function desactiver(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE categories SET actif = false WHERE id_categorie = :id'
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Vérifie si une catégorie avec ce nom existe déjà (contrainte UNIQUE sur categories.nom).
     * $excludeId permet d'exclure la catégorie elle-même lors d'un update.
     */
    public function existsByNom(string $nom, ?int $excludeId = null): bool
    {
        if ($excludeId !== null) {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM categories WHERE nom = :nom AND id_categorie != :exclude LIMIT 1'
            );
            $statement->execute(['nom' => $nom, 'exclude' => $excludeId]);
        } else {
            $statement = $this->pdo->prepare(
                'SELECT 1 FROM categories WHERE nom = :nom LIMIT 1'
            );
            $statement->execute(['nom' => $nom]);
        }

        return (bool) $statement->fetchColumn();
    }

    /**
     * Normalise les types retournés par PDO (tout est string en PostgreSQL PDO).
     *
     * @param array<string, mixed> $row
     * @return array{id_categorie: int, nom: string, description: string|null, actif: bool}
     */
    private function normalizeCategorie(array $row): array
    {
        return [
            'id_categorie' => (int) $row['id_categorie'],
            'nom'          => (string) $row['nom'],
            'description'  => isset($row['description']) ? (string) $row['description'] : null,
            'actif'        => $row['actif'] === 't',
        ];
    }
}
