<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Services\AuthService;
use App\Services\LoginAttemptService;

final class AuthController extends BaseController
{
    // Affiche le formulaire de connexion (redirige si déjà connecté)
    public function showLogin(array $args = []): void
    {
        // Déjà connecté → dashboard directement
        if ($this->currentUser() !== null) {
            $this->redirect('/dashboard');
            return;
        }

        $this->view('auth/login', [
            'layout'      => false,
            'flash'       => $this->getFlash(),
            'csrfToken'   => $this->csrfToken(),
            'identifiant' => (string) ($_SESSION['last_identifiant'] ?? ''),
        ]);
        unset($_SESSION['last_identifiant']);
    }

    // Traite la soumission du formulaire de connexion
    public function login(array $args = []): void
    {
        // CSRF vérifié même sur le formulaire de login (Login CSRF attack)
        $this->requireCsrf();

        // Déjà connecté → dashboard directement
        if ($this->currentUser() !== null) {
            $this->redirect('/dashboard');
            return;
        }

        $identifiant = trim((string) $this->input('identifiant', ''));
        $password    = (string) $this->input('mot_de_passe', '');
        $ip          = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        // Vérification brute force AVANT toute interrogation BDD
        $attempts = new LoginAttemptService($identifiant, $ip);
        if ($attempts->isLocked()) {
            $this->flash('error', 'Trop de tentatives. Réessayez dans 15 minutes.');
            $this->redirect('/login');
            return;
        }

        // Validation basique des champs
        if ($identifiant === '' || $password === '') {
            $this->flash('error', 'Identifiants invalides.');
            $this->redirect('/login');
            return;
        }

        // Tentative d'authentification
        $auth = new AuthService();
        $user = $auth->attempt($identifiant, $password);

        if ($user === null) {
            // recordFailure() applique le délai de 3s (CDC 6.3.1)
            $attempts->recordFailure();
            $_SESSION['last_identifiant'] = $identifiant;
            $this->flash('error', 'Identifiants invalides.');
            $this->redirect('/login');
            return;
        }

        // Succès — réinitialiser le compteur, regénérer l'ID de session
        $attempts->recordSuccess();
        session_regenerate_id(true);

        $_SESSION['user']          = $user;
        $_SESSION['last_activity'] = time();

        $this->redirect('/dashboard');
    }

    // Déconnecte l'utilisateur et redirige vers le formulaire de connexion
    public function logout(array $args = []): void
    {
        $this->destroySession();
        $this->flash('success', 'Vous avez été déconnecté.');
        $this->redirect('/login');
    }
}

