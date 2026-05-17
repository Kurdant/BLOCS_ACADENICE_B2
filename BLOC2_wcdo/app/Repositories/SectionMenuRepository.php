<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données de la table `sections_menu`.
 * Une section appartient à un menu et contient des options (produits).
 */
final class SectionMenuRepository extends BaseRepository
{
    /**
     * Retourne toutes les sections d'un menu avec leurs options et produits associés.
     *
     * La requête fait une jointure LEFT JOIN pour inclure les sections vides.
     * Le résultat est reconstruit en tableau imbriqué : sections → options.
     *
     * @return array<int, array<string, mixed>>
     */
    public function findByMenuId(int $idMenu): array
    {
        // Une seule requête JOIN pour récupérer sections + options + produits associés
        $statement = $this->pdo->prepare(
            'SELECT
                s.id_section_menu, s.nom AS section_nom,
                s.obligatoire, s.quantite_min, s.quantite_max,
                o.id_option_menu, o.supplement_prix, o.actif AS option_actif,
                p.id_produit, p.nom AS produit_nom, p.prix AS produit_prix, p.disponible
             FROM sections_menu s
             LEFT JOIN options_menu o
                    ON o.id_section_menu = s.id_section_menu
                   AND o.actif = true
             LEFT JOIN produits p
                    ON p.id_produit = o.id_produit
             WHERE s.id_menu = :id_menu
             ORDER BY s.id_section_menu ASC, o.id_option_menu ASC'
        );
        $statement->execute(['id_menu' => $idMenu]);
        $rows = $statement->fetchAll();

        // Reconstruction du tableau imbriqué : une entrée par section, options dans 'options'
        $sections = [];
        foreach ($rows as $row) {
            $sid = (int) $row['id_section_menu'];

            if (!isset($sections[$sid])) {
                $sections[$sid] = [
                    'id_section_menu' => $sid,
                    'nom'             => (string) $row['section_nom'],
                    'obligatoire'     => $row['obligatoire'] === 't',
                    'quantite_min'    => (int) $row['quantite_min'],
                    'quantite_max'    => (int) $row['quantite_max'],
                    'options'         => [],
                ];
            }

            // Ajouter l'option seulement si elle existe (LEFT JOIN peut retourner null)
            if ($row['id_option_menu'] !== null) {
                $sections[$sid]['options'][] = [
                    'id_option_menu'  => (int) $row['id_option_menu'],
                    'id_produit'      => (int) $row['id_produit'],
                    'produit_nom'     => (string) $row['produit_nom'],
                    'produit_prix'    => (float) $row['produit_prix'],
                    'supplement_prix' => (float) $row['supplement_prix'],
                    'disponible'      => $row['disponible'] === 't',
                ];
            }
        }

        // Réindexer le tableau (array_values pour retourner un tableau indexé de 0 à n)
        return array_values($sections);
    }

    /**
     * Retourne une section par son ID, ou null si introuvable.
     *
     * @return array{id_section_menu: int, id_menu: int, nom: string, obligatoire: bool, quantite_min: int, quantite_max: int}|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT id_section_menu, id_menu, nom, obligatoire, quantite_min, quantite_max
             FROM sections_menu
             WHERE id_section_menu = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        if (!is_array($row)) {
            return null;
        }

        return [
            'id_section_menu' => (int) $row['id_section_menu'],
            'id_menu'         => (int) $row['id_menu'],
            'nom'             => (string) $row['nom'],
            'obligatoire'     => filter_var($row['obligatoire'], FILTER_VALIDATE_BOOLEAN),
            'quantite_min'    => (int) $row['quantite_min'],
            'quantite_max'    => (int) $row['quantite_max'],
        ];
    }

    /**
     * Insère une nouvelle section dans un menu et retourne son ID généré.
     */
    public function create(
        int    $idMenu,
        string $nom,
        bool   $obligatoire,
        int    $quantiteMin,
        int    $quantiteMax
    ): int {
        $statement = $this->pdo->prepare(
            'INSERT INTO sections_menu (id_menu, nom, obligatoire, quantite_min, quantite_max)
             VALUES (:id_menu, :nom, :obligatoire, :quantite_min, :quantite_max)
             RETURNING id_section_menu'
        );
        $statement->execute([
            'id_menu'      => $idMenu,
            'nom'          => $nom,
            'obligatoire'  => $obligatoire ? 'true' : 'false',
            'quantite_min' => $quantiteMin,
            'quantite_max' => $quantiteMax,
        ]);

        return (int) $statement->fetchColumn();
    }
}
