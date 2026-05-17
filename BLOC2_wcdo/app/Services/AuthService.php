<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\UtilisateurRepository;

/**
 * Service d'authentification.
 *
 * Vérifie les credentials (identifiant + mot de passe bcrypt).
 * Ne gère pas la session — c'est la responsabilité du contrôleur.
 * Ne gère pas la force brute — c'est la responsabilité de LoginAttemptService.
 */
final class AuthService
{
    // Hash bcrypt coût 12 pré-calculé pour la mitigation timing (compte inconnu).
    // Généré avec : password_hash('_dummy_wacdo_', PASSWORD_BCRYPT, ['cost' => 12])
    private const DUMMY_HASH = '$2y$12$MpDkRYBQMIvltOMaE6jJ2OJAMs/x80dGM2yBZ3XZ27l.w5FinH1Yq';

    private UtilisateurRepository $repository;

    public function __construct()
    {
        $this->repository = new UtilisateurRepository();
    }

    /**
     * Tente une authentification.
     *
     * Retourne le tableau session-ready si succès, null sinon.
     * Ne divulgue jamais la raison de l'échec (identifiant inconnu vs mot de passe erroné).
     *
     * @return array{id: int, identifiant: string, nom: string, prenom: string, role: string}|null
     */
    public function attempt(string $identifiant, string $password): ?array
    {
        $user = $this->repository->findForAuth($identifiant);

        if ($user === null) {
            // Calcul bcrypt factice pour homogénéiser le temps de réponse
            // (évite de distinguer "compte inconnu" vs "mauvais mot de passe" par timing)
            password_verify($password, self::DUMMY_HASH);
            return null;
        }

        if (!password_verify($password, $user['mot_de_passe_hash'])) {
            return null;
        }

        return [
            'id'          => $user['id_utilisateur'],
            'identifiant' => $user['identifiant'],
            'nom'         => $user['nom'],
            'prenom'      => $user['prenom'],
            'role'        => $user['role'],
        ];
    }
}
