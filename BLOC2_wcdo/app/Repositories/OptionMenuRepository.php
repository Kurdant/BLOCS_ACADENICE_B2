<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données de la table `options_menu`.
 * Une option lie un produit à une section de menu, avec un éventuel supplément de prix.
 */
final class OptionMenuRepository extends BaseRepository
{
    /**
     * Retourne les options actives d'une section, avec les informations du produit associé.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findBySectionId(int $idSection): array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                o.id_option_menu, o.id_section_menu, o.id_produit,
                o.supplement_prix, o.actif,
                p.nom AS produit_nom, p.prix AS produit_prix, p.disponible
             FROM options_menu o
             INNER JOIN produits p ON p.id_produit = o.id_produit
             WHERE o.id_section_menu = :id_section
               AND o.actif = true
             ORDER BY p.nom ASC'
        );
        $statement->execute(['id_section' => $idSection]);

        return array_map([$this, 'normalizeOption'], $statement->fetchAll());
    }

    /**
     * Retourne une option par son ID, ou null si introuvable.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                o.id_option_menu, o.id_section_menu, o.id_produit,
                o.supplement_prix, o.actif,
                p.nom AS produit_nom, p.prix AS produit_prix, p.disponible
             FROM options_menu o
             INNER JOIN produits p ON p.id_produit = o.id_produit
             WHERE o.id_option_menu = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeOption($row) : null;
    }

    /**
     * Insère une nouvelle option dans une section et retourne son ID généré.
     *
     * @param float $supplementPrix Supplément en euros (0 par défaut, jamais négatif)
     */
    public function create(int $idSection, int $idProduit, float $supplementPrix): int
    {
        $statement = $this->pdo->prepare(
            'INSERT INTO options_menu (id_section_menu, id_produit, supplement_prix)
             VALUES (:id_section, :id_produit, :supplement)
             RETURNING id_option_menu'
        );
        $statement->execute([
            'id_section'  => $idSection,
            'id_produit'  => $idProduit,
            'supplement'  => (string) $supplementPrix,
        ]);

        return (int) $statement->fetchColumn();
    }

    /**
     * Désactive une option (soft delete — actif = false).
     * L'option reste en base pour préserver l'historique des commandes passées.
     */
    public function desactiver(int $id): void
    {
        $statement = $this->pdo->prepare(
            'UPDATE options_menu SET actif = false WHERE id_option_menu = :id'
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Normalise les types retournés par PDO.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeOption(array $row): array
    {
        return [
            'id_option_menu'  => (int) $row['id_option_menu'],
            'id_section_menu' => (int) $row['id_section_menu'],
            'id_produit'      => (int) $row['id_produit'],
            'produit_nom'     => (string) $row['produit_nom'],
            'produit_prix'    => (float) $row['produit_prix'],
            'supplement_prix' => (float) $row['supplement_prix'],
            'disponible'      => $row['disponible'] === 't',
            'actif'           => $row['actif'] === 't',
        ];
    }
}
