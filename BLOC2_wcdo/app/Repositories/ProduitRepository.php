<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données de la table `produits`.
 * Toutes les requêtes joignent la table `categories` pour retourner le nom de catégorie.
 */
final class ProduitRepository extends BaseRepository
{
    /**
     * Retourne tous les produits (actifs et inactifs) avec leur catégorie, triés par nom.
     * Utilisé par le back-office (page liste produits).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                p.id_produit, p.nom, p.description, p.prix, p.image,
                p.disponible, p.actif, p.date_creation, p.date_modification,
                p.id_categorie,
                c.nom AS categorie_nom
             FROM produits p
             INNER JOIN categories c ON c.id_categorie = p.id_categorie
             ORDER BY p.nom ASC'
        );

        return array_map([$this, 'normalizeProduit'], $statement->fetchAll());
    }

    /**
     * Retourne les produits actifs avec leur catégorie, triés par nom.
     * Utilisé dans les formulaires de composition de menus.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                p.id_produit, p.nom, p.description, p.prix, p.image,
                p.disponible, p.actif, p.date_creation, p.date_modification,
                p.id_categorie,
                c.nom AS categorie_nom
             FROM produits p
             INNER JOIN categories c ON c.id_categorie = p.id_categorie
             WHERE p.actif = true
             ORDER BY p.nom ASC'
        );

        return array_map([$this, 'normalizeProduit'], $statement->fetchAll());
    }

    /**
     * Retourne les produits actifs ET disponibles avec leur catégorie.
     * Utilisé pour la prise de commande (back-office et API).
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAllAvailableActive(): array
    {
        $statement = $this->pdo->query(
            'SELECT
                p.id_produit, p.nom, p.description, p.prix, p.image,
                p.disponible, p.actif, p.date_creation, p.date_modification,
                p.id_categorie,
                c.nom AS categorie_nom
             FROM produits p
             INNER JOIN categories c ON c.id_categorie = p.id_categorie
             WHERE p.actif = true AND p.disponible = true
             ORDER BY c.nom ASC, p.nom ASC'
        );

        return array_map([$this, 'normalizeProduit'], $statement->fetchAll());
    }

    /**
     * Retourne un produit par son ID avec sa catégorie, ou null si introuvable.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                p.id_produit, p.nom, p.description, p.prix, p.image,
                p.disponible, p.actif, p.date_creation, p.date_modification,
                p.id_categorie,
                c.nom AS categorie_nom
             FROM produits p
             INNER JOIN categories c ON c.id_categorie = p.id_categorie
             WHERE p.id_produit = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeProduit($row) : null;
    }

    /**
     * Retourne tous les produits actifs d'une catégorie donnée.
     * Utilisé pour filtrer le catalogue par catégorie.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByCategorieId(int $idCategorie): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                p.id_produit, p.nom, p.description, p.prix, p.image,
                p.disponible, p.actif, p.date_creation, p.date_modification,
                p.id_categorie,
                c.nom AS categorie_nom
             FROM produits p
             INNER JOIN categories c ON c.id_categorie = p.id_categorie
             WHERE p.id_categorie = :id_categorie
               AND p.actif = true
             ORDER BY p.nom ASC'
        );
        $statement->execute(['id_categorie' => $idCategorie]);

        return array_map([$this, 'normalizeProduit'], $statement->fetchAll());
    }

    /**
     * Insère un nouveau produit et retourne son ID généré.
     *
     * @param array{id_categorie: int, nom: string, description: string, prix: float|string, image: string} $data
     */
    public function create(array $data): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO produits (id_categorie, nom, description, prix, image)
             VALUES (:id_categorie, :nom, :description, :prix, :image)
             RETURNING id_produit'
        );
        $statement->execute([
            'id_categorie' => (int) $data['id_categorie'],
            'nom'          => (string) $data['nom'],
            'description'  => (string) $data['description'],
            'prix'         => (string) $data['prix'],
            'image'        => (string) $data['image'],
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Met à jour les informations d'un produit.
     * L'image n'est mise à jour que si le champ `image` est fourni dans $data.
     *
     * @param array{id_categorie: int, nom: string, description: string, prix: float|string, disponible: bool, image?: string} $data
     */
    public function update(int $id, array $data): void
    {
        // Construire la liste des colonnes à mettre à jour dynamiquement
        // selon que l'image a changé ou non (évite d'écraser l'image si non modifiée)
        $cols = 'id_categorie = :id_categorie,
                 nom          = :nom,
                 description  = :description,
                 prix         = :prix,
                 disponible   = :disponible';

        $params = [
            'id_categorie' => (int) $data['id_categorie'],
            'nom'          => (string) $data['nom'],
            'description'  => (string) $data['description'],
            'prix'         => (string) $data['prix'],
            'disponible'   => $data['disponible'] ? 'true' : 'false',
            'id'           => $id,
        ];

        if (isset($data['image'])) {
            $cols .= ', image = :image';
            $params['image'] = (string) $data['image'];
        }

        $statement = $this->pdo->prepare("UPDATE produits SET {$cols} WHERE id_produit = :id");
        $statement->execute($params);
    }

    /**
     * Désactive un produit (soft delete — actif = false).
     */
    public function desactiver(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE produits SET actif = false WHERE id_produit = :id'
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Normalise les types retournés par PDO.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeProduit(array $row): array
    {
        return [
            'id_produit'        => (int) $row['id_produit'],
            'id_categorie'      => (int) $row['id_categorie'],
            'categorie_nom'     => (string) $row['categorie_nom'],
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
