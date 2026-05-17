<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Core\BaseRepository;

/**
 * Accès aux données des tables `commandes`, `lignes_commande` et `choix_ligne_commande`.
 * La création d'une commande est atomique : commande + lignes + choix dans une seule transaction.
 */
final class CommandeRepository extends BaseRepository
{
    /**
     * Retourne toutes les commandes avec l'identifiant de l'auteur, triées par date décroissante.
     * Filtrage optionnel par statut ('a_preparer', 'preparee', 'livree').
     *
     * @return array<int, array<string, mixed>>
     */
    public function findAll(?string $statut = null): array
    {
        // Construire la clause WHERE conditionnelle selon le filtre de statut
        $where  = $statut !== null ? 'WHERE c.statut = :statut' : '';
        $params = $statut !== null ? ['statut' => $statut] : [];

        $statement = $this->pdo->prepare(
            "SELECT
                c.id_commande, c.numero_retrait, c.source, c.type_service,
                c.statut, c.total, c.date_commande, c.date_heure_retrait_prevue,
                c.date_preparation, c.date_livraison,
                u.identifiant AS auteur_identifiant
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur_auteur
             {$where}
             ORDER BY c.date_commande DESC"
        );
        $statement->execute($params);

        return array_map([$this, 'normalizeCommande'], $statement->fetchAll());
    }

    /**
     * Retourne une commande par son ID sans ses lignes, ou null si introuvable.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array
    {
        $statement = $this->pdo->prepare(
            'SELECT
                c.id_commande, c.numero_retrait, c.source, c.type_service,
                c.statut, c.total, c.date_commande, c.date_heure_retrait_prevue,
                c.date_preparation, c.date_livraison, c.id_utilisateur_auteur,
                u.identifiant AS auteur_identifiant
             FROM commandes c
             LEFT JOIN utilisateurs u ON u.id_utilisateur = c.id_utilisateur_auteur
             WHERE c.id_commande = :id
             LIMIT 1'
        );
        $statement->execute(['id' => $id]);
        $row = $statement->fetch();

        return is_array($row) ? $this->normalizeCommande($row) : null;
    }

    /**
     * Retourne une commande complète avec ses lignes et choix de personnalisation.
     *
     * Structure retournée :
     *   commande + lignes[] + lignes[n]['choix'][]
     *
     * @return array<string, mixed>|null
     */
    public function findByIdWithLignes(int $id): ?array
    {
        // 1. Récupérer la commande de base
        $commande = $this->findById($id);
        if ($commande === null) {
            return null;
        }

        // 2. Récupérer toutes les lignes de la commande
        $stmtLignes = $this->pdo->prepare(
            'SELECT
                id_ligne_commande, id_commande, type_ligne,
                id_produit, id_menu, libelle_article,
                quantite, prix_unitaire_applique, sous_total
             FROM lignes_commande
             WHERE id_commande = :id
             ORDER BY id_ligne_commande ASC'
        );
        $stmtLignes->execute(['id' => $id]);
        $lignes = $stmtLignes->fetchAll();

        // 3. Pour chaque ligne de type 'menu', récupérer les choix de personnalisation
        $lignesNormalisees = [];
        foreach ($lignes as $ligne) {
            $idLigne = (int) $ligne['id_ligne_commande'];

            $choix = [];
            if ($ligne['type_ligne'] === 'menu') {
                $stmtChoix = $this->pdo->prepare(
                    'SELECT
                        id_choix_ligne_commande, id_ligne_commande, id_produit,
                        nom_section, libelle_produit, prix_supplement_applique
                     FROM choix_ligne_commande
                     WHERE id_ligne_commande = :id_ligne
                     ORDER BY nom_section ASC'
                );
                $stmtChoix->execute(['id_ligne' => $idLigne]);
                $rawChoix = $stmtChoix->fetchAll();

                foreach ($rawChoix as $c) {
                    $choix[] = [
                        'id_choix_ligne_commande' => (int) $c['id_choix_ligne_commande'],
                        'id_ligne_commande'       => $idLigne,
                        'id_produit'              => (int) $c['id_produit'],
                        'nom_section'             => (string) $c['nom_section'],
                        'libelle_produit'         => (string) $c['libelle_produit'],
                        'prix_supplement_applique'=> (float) $c['prix_supplement_applique'],
                    ];
                }
            }

            $lignesNormalisees[] = [
                'id_ligne_commande'      => $idLigne,
                'id_commande'            => (int) $ligne['id_commande'],
                'type_ligne'             => (string) $ligne['type_ligne'],
                'id_produit'             => $ligne['id_produit'] !== null ? (int) $ligne['id_produit'] : null,
                'id_menu'                => $ligne['id_menu'] !== null ? (int) $ligne['id_menu'] : null,
                'libelle_article'        => (string) $ligne['libelle_article'],
                'quantite'               => (int) $ligne['quantite'],
                'prix_unitaire_applique' => (float) $ligne['prix_unitaire_applique'],
                'sous_total'             => (float) $ligne['sous_total'],
                'choix'                  => $choix,
            ];
        }

        $commande['lignes'] = $lignesNormalisees;

        return $commande;
    }

    /**
     * Crée une commande complète en transaction atomique.
     *
     * Structure attendue pour $commande :
     *   ['numero_retrait', 'source', 'type_service', 'total',
     *    'id_utilisateur_auteur' (nullable), 'date_heure_retrait_prevue' (nullable)]
     *
     * Structure attendue pour chaque entrée de $lignes :
     *   ['type_ligne', 'id_produit' (nullable), 'id_menu' (nullable),
     *    'libelle_article', 'quantite', 'prix_unitaire_applique', 'sous_total',
     *    'choix' => [['id_produit', 'nom_section', 'libelle_produit', 'prix_supplement_applique'], ...]]
     *
     * @param array<string, mixed>       $commande Données de la commande
     * @param array<int, array<string, mixed>> $lignes   Lignes avec leurs choix éventuels
     * @return int ID de la commande créée
     */
    public function create(array $commande, array $lignes): int
    {
        $this->pdo->beginTransaction();

        try {
            // Insérer la commande
            $stmtCommande = $this->pdo->prepare(
                'INSERT INTO commandes
                    (numero_retrait, source, type_service, total,
                     id_utilisateur_auteur, date_heure_retrait_prevue)
                 VALUES
                    (:numero_retrait, :source, :type_service, :total,
                     :id_utilisateur_auteur, :date_heure_retrait_prevue)
                 RETURNING id_commande'
            );
            $stmtCommande->execute([
                'numero_retrait'           => (string) $commande['numero_retrait'],
                'source'                   => (string) $commande['source'],
                'type_service'             => (string) $commande['type_service'],
                'total'                    => (string) $commande['total'],
                'id_utilisateur_auteur'    => $commande['id_utilisateur_auteur'] ?? null,
                'date_heure_retrait_prevue'=> $commande['date_heure_retrait_prevue'] ?? null,
            ]);
            $idCommande = (int) $stmtCommande->fetchColumn();

            // Préparer les requêtes d'insertion des lignes et des choix une seule fois
            $stmtLigne = $this->pdo->prepare(
                'INSERT INTO lignes_commande
                    (id_commande, type_ligne, id_produit, id_menu,
                     libelle_article, quantite, prix_unitaire_applique, sous_total)
                 VALUES
                    (:id_commande, :type_ligne, :id_produit, :id_menu,
                     :libelle_article, :quantite, :prix_unitaire_applique, :sous_total)
                 RETURNING id_ligne_commande'
            );

            $stmtChoix = $this->pdo->prepare(
                'INSERT INTO choix_ligne_commande
                    (id_ligne_commande, id_produit, nom_section, libelle_produit, prix_supplement_applique)
                 VALUES
                    (:id_ligne_commande, :id_produit, :nom_section, :libelle_produit, :prix_supplement_applique)'
            );

            foreach ($lignes as $ligne) {
                // Insérer la ligne
                $stmtLigne->execute([
                    'id_commande'            => $idCommande,
                    'type_ligne'             => (string) $ligne['type_ligne'],
                    'id_produit'             => $ligne['id_produit'] ?? null,
                    'id_menu'                => $ligne['id_menu'] ?? null,
                    'libelle_article'        => (string) $ligne['libelle_article'],
                    'quantite'               => (int) $ligne['quantite'],
                    'prix_unitaire_applique' => (string) $ligne['prix_unitaire_applique'],
                    'sous_total'             => (string) $ligne['sous_total'],
                ]);
                $idLigne = (int) $stmtLigne->fetchColumn();

                // Insérer les choix de personnalisation (seulement pour les menus)
                foreach ($ligne['choix'] ?? [] as $choix) {
                    $stmtChoix->execute([
                        'id_ligne_commande'        => $idLigne,
                        'id_produit'               => (int) $choix['id_produit'],
                        'nom_section'              => (string) $choix['nom_section'],
                        'libelle_produit'          => (string) $choix['libelle_produit'],
                        'prix_supplement_applique' => (string) $choix['prix_supplement_applique'],
                    ]);
                }
            }

            $this->pdo->commit();

            return $idCommande;

        } catch (\Throwable $e) {
            // Annuler toute la transaction si une erreur survient
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Marque une commande comme préparée (statut → 'preparee', date_preparation = NOW()).
     */
    public function marquerPreparee(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE commandes
             SET statut = 'preparee', date_preparation = NOW()
             WHERE id_commande = :id
               AND statut = 'a_preparer'"
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Marque une commande comme livrée (statut → 'livree', date_livraison = NOW()).
     */
    public function marquerLivree(int $id): void
    {
        $statement = $this->pdo->prepare(
            "UPDATE commandes
             SET statut = 'livree', date_livraison = NOW()
             WHERE id_commande = :id
               AND statut = 'preparee'"
        );
        $statement->execute(['id' => $id]);
    }

    /**
     * Normalise les types d'une commande retournée par PDO.
     *
     * @param array<string, mixed> $row
     * @return array<string, mixed>
     */
    private function normalizeCommande(array $row): array
    {
        return [
            'id_commande'                => (int) $row['id_commande'],
            'numero_retrait'             => (string) $row['numero_retrait'],
            'source'                     => (string) $row['source'],
            'type_service'               => (string) $row['type_service'],
            'statut'                     => (string) $row['statut'],
            'total'                      => (float) $row['total'],
            'date_commande'              => (string) $row['date_commande'],
            'date_heure_retrait_prevue'  => isset($row['date_heure_retrait_prevue'])
                                            ? (string) $row['date_heure_retrait_prevue']
                                            : null,
            'id_utilisateur_auteur'      => isset($row['id_utilisateur_auteur'])
                                            ? (int) $row['id_utilisateur_auteur']
                                            : null,
            'auteur_identifiant'         => isset($row['auteur_identifiant'])
                                            ? (string) $row['auteur_identifiant']
                                            : null,
            'date_preparation'           => isset($row['date_preparation'])
                                            ? (string) $row['date_preparation']
                                            : null,
            'date_livraison'             => isset($row['date_livraison'])
                                            ? (string) $row['date_livraison']
                                            : null,
        ];
    }
}
