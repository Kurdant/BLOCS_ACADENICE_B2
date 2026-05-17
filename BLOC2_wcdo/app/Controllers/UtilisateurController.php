<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Validator;
use App\Repositories\UtilisateurRepository;
use App\Services\LoginAttemptService;
use App\Services\TraceService;

final class UtilisateurController extends BaseController
{
    // =========================================================================
    // Gestion des utilisateurs — réservé au rôle Administration
    // =========================================================================

    // Affiche la liste de tous les utilisateurs
    public function index(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $repo  = new UtilisateurRepository();
        $users = $repo->findAll();

        $this->view('utilisateurs/index', [
            'title'         => 'Utilisateurs',
            'flash'         => $this->getFlash(),
            'users'         => $users,
            'currentUserId' => $this->currentUser()['id'],
            'csrfToken'     => $this->csrfToken(),
        ]);
    }

    // Affiche le formulaire de création d'un utilisateur
    public function create(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $repo = new UtilisateurRepository();

        $this->view('utilisateurs/create', [
            'title'     => 'Créer un utilisateur',
            'flash'     => $this->getFlash(),
            'roles'     => $repo->findAllRoles(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de création
    public function store(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $identifiant = trim((string) ($_POST['identifiant']   ?? ''));
        $nom         = trim((string) ($_POST['nom']           ?? ''));
        $prenom      = trim((string) ($_POST['prenom']        ?? ''));
        $idRole      = (int) ($_POST['id_role']               ?? 0);
        $mdp         = (string) ($_POST['mot_de_passe']       ?? '');
        $confirm     = (string) ($_POST['confirmation']       ?? '');

        // Validation des champs obligatoires
        $v = new Validator();
        $v->required('identifiant', $identifiant, 'Identifiant')
          ->maxLength('identifiant', $identifiant, 100, 'Identifiant')
          ->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 100, 'Nom')
          ->required('prenom', $prenom, 'Prénom')
          ->maxLength('prenom', $prenom, 100, 'Prénom')
          ->required('mot_de_passe', $mdp, 'Mot de passe')
          ->minLength('mot_de_passe', $mdp, 8, 'Mot de passe')
          ->maxLength('mot_de_passe', $mdp, 72, 'Mot de passe');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/utilisateurs/creer');
            return;
        }

        // Vérification de la confirmation de mot de passe
        if ($mdp !== $confirm) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect('/utilisateurs/creer');
            return;
        }

        $repo  = new UtilisateurRepository();
        $roles = $repo->findAllRoles();

        // Vérifier que le rôle soumis existe en base
        if (!in_array($idRole, array_map('intval', array_column($roles, 'id_role')), true)) {
            $this->flash('error', 'Rôle invalide.');
            $this->redirect('/utilisateurs/creer');
            return;
        }

        // Vérifier l'unicité de l'identifiant
        if ($repo->existsByIdentifiant($identifiant)) {
            $this->flash('error', "L'identifiant « {$identifiant} » est déjà utilisé.");
            $this->redirect('/utilisateurs/creer');
            return;
        }

        // Hachage du mot de passe et insertion
        $hash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);
        $id   = $repo->create($identifiant, $nom, $prenom, $hash, $idRole);

        // Traçabilité
        (new TraceService())->log('creation', 'utilisateurs', $id, "identifiant={$identifiant}");

        $this->flash('success', "Utilisateur « {$identifiant} » créé.");
        $this->redirect('/utilisateurs');
    }

    // Affiche le formulaire de modification d'un utilisateur
    public function edit(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $id   = (int) ($args['id'] ?? 0);
        $repo = new UtilisateurRepository();
        $user = $repo->findById($id);

        if ($user === null) {
            $this->abort(404);
            return;
        }

        $this->view('utilisateurs/edit', [
            'title'     => 'Modifier un utilisateur',
            'flash'     => $this->getFlash(),
            'user'      => $user,
            'roles'     => $repo->findAllRoles(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de modification
    public function update(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id = (int) ($args['id'] ?? 0);

        $repo = new UtilisateurRepository();
        $user = $repo->findById($id);

        if ($user === null) {
            $this->abort(404);
            return;
        }

        $identifiant = trim((string) ($_POST['identifiant'] ?? ''));
        $nom         = trim((string) ($_POST['nom']         ?? ''));
        $prenom      = trim((string) ($_POST['prenom']      ?? ''));
        $idRole      = (int) ($_POST['id_role']             ?? 0);
        $mdp         = (string) ($_POST['mot_de_passe']     ?? '');
        $confirm     = (string) ($_POST['confirmation']     ?? '');

        // Validation des champs de base
        $v = new Validator();
        $v->required('identifiant', $identifiant, 'Identifiant')
          ->maxLength('identifiant', $identifiant, 100, 'Identifiant')
          ->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 100, 'Nom')
          ->required('prenom', $prenom, 'Prénom')
          ->maxLength('prenom', $prenom, 100, 'Prénom');

        // Validation du mot de passe seulement s'il est fourni (optionnel en édition)
        if ($mdp !== '') {
            $v->minLength('mot_de_passe', $mdp, 8, 'Mot de passe')
              ->maxLength('mot_de_passe', $mdp, 72, 'Mot de passe');
        }

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect("/utilisateurs/{$id}/editer");
            return;
        }

        // Vérification de la confirmation seulement si un nouveau mot de passe est fourni
        if ($mdp !== '' && $mdp !== $confirm) {
            $this->flash('error', 'Les mots de passe ne correspondent pas.');
            $this->redirect("/utilisateurs/{$id}/editer");
            return;
        }

        // Vérifier que le rôle soumis existe en base
        $roles = $repo->findAllRoles();
        if (!in_array($idRole, array_map('intval', array_column($roles, 'id_role')), true)) {
            $this->flash('error', 'Rôle invalide.');
            $this->redirect("/utilisateurs/{$id}/editer");
            return;
        }

        // Vérifier l'unicité de l'identifiant en excluant l'utilisateur courant
        if ($repo->existsByIdentifiant($identifiant, $id)) {
            $this->flash('error', "L'identifiant « {$identifiant} » est déjà utilisé.");
            $this->redirect("/utilisateurs/{$id}/editer");
            return;
        }

        // Mise à jour des informations de base
        $repo->update($id, $nom, $prenom, $identifiant, $idRole);

        // Mise à jour du mot de passe seulement si un nouveau a été fourni
        if ($mdp !== '') {
            $newHash = password_hash($mdp, PASSWORD_BCRYPT, ['cost' => 12]);
            $repo->updatePasswordById($id, $newHash);
        }

        // Traçabilité
        (new TraceService())->log('modification', 'utilisateurs', $id, "identifiant={$identifiant}");

        $this->flash('success', "Utilisateur « {$identifiant} » mis à jour.");
        $this->redirect('/utilisateurs');
    }

    // Désactive un utilisateur (soft delete)
    public function desactiver(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id          = (int) ($args['id'] ?? 0);
        $currentUser = $this->currentUser();

        // Interdire la désactivation de son propre compte
        if ($id === (int) ($currentUser['id'] ?? 0)) {
            $this->flash('error', 'Vous ne pouvez pas désactiver votre propre compte.');
            $this->redirect('/utilisateurs');
            return;
        }

        $repo = new UtilisateurRepository();
        $user = $repo->findById($id);

        if ($user === null) {
            $this->abort(404);
            return;
        }

        // Protéger le dernier compte Administration actif
        if ($user['role'] === 'Administration' && $repo->countActiveByRole('Administration') <= 1) {
            $this->flash('error', 'Impossible de désactiver le dernier administrateur actif.');
            $this->redirect('/utilisateurs');
            return;
        }

        $repo->desactiver($id);

        // Traçabilité
        (new TraceService())->log('desactivation', 'utilisateurs', $id, "identifiant={$user['identifiant']}");

        $this->flash('success', "Utilisateur « {$user['identifiant']} » désactivé.");
        $this->redirect('/utilisateurs');
    }

    // =========================================================================
    // Profil personnel — accessible à tous les rôles
    // =========================================================================

    // Affiche le formulaire de changement de mot de passe de l'utilisateur connecté
    public function editPassword(array $args = []): void
    {
        $this->requireAuth();

        $this->view('utilisateurs/edit-password', [
            'title'     => 'Changer mon mot de passe',
            'flash'     => $this->getFlash(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de changement de mot de passe
    public function updatePassword(array $args = []): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $user    = $this->currentUser();
        $current = (string) ($_POST['mot_de_passe_actuel']  ?? '');
        $new     = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        $confirm = (string) ($_POST['confirmation']         ?? '');

        // Validation des champs obligatoires
        if ($current === '' || $new === '' || $confirm === '') {
            $this->flash('error', 'Tous les champs sont obligatoires.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Contrainte longueur : 8 min, 72 max (limite bcrypt)
        if (strlen($new) < 8 || strlen($new) > 72) {
            $this->flash('error', 'Le nouveau mot de passe doit contenir entre 8 et 72 caractères.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Vérification de la confirmation
        if ($new !== $confirm) {
            $this->flash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        $repo     = new UtilisateurRepository();
        // Protège le changement de mot de passe contre la force brute
        $attempts = new LoginAttemptService((string) $user['identifiant'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($attempts->isLocked()) {
            $this->flash('error', 'Trop de tentatives. Réessayez dans 15 minutes.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Vérification du mot de passe actuel avant toute modification
        $currentHash = $repo->findHashById($user['id']);

        if ($currentHash === null || !password_verify($current, $currentHash)) {
            $attempts->recordFailure();
            $this->flash('error', 'Mot de passe actuel incorrect.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Succès : hachage et mise à jour en base, régénération de session
        $attempts->recordSuccess();
        $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $repo->updatePasswordById($user['id'], $newHash);
        session_regenerate_id(true);

        $this->flash('success', 'Mot de passe mis à jour.');
        $this->redirect('/mon-compte/mot-de-passe');
    }
}


    // Affiche le formulaire de changement de mot de passe de l'utilisateur connecté
    public function editPassword(array $args = []): void
    {
        $this->requireAuth();

        $this->view('utilisateurs/edit-password', [
            'title'     => 'Changer mon mot de passe',
            'flash'     => $this->getFlash(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de changement de mot de passe
    public function updatePassword(array $args = []): void
    {
        $this->requireAuth();
        $this->requireCsrf();

        $user    = $this->currentUser();
        $current = (string) ($_POST['mot_de_passe_actuel']  ?? '');
        $new     = (string) ($_POST['nouveau_mot_de_passe'] ?? '');
        $confirm = (string) ($_POST['confirmation']         ?? '');

        // Validation des champs obligatoires
        if ($current === '' || $new === '' || $confirm === '') {
            $this->flash('error', 'Tous les champs sont obligatoires.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Contrainte longueur : 8 min, 72 max (limite bcrypt)
        if (strlen($new) < 8 || strlen($new) > 72) {
            $this->flash('error', 'Le nouveau mot de passe doit contenir entre 8 et 72 caractères.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Vérification de la confirmation
        if ($new !== $confirm) {
            $this->flash('error', 'Les nouveaux mots de passe ne correspondent pas.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        $repo     = new UtilisateurRepository();
        // Protège le changement de mot de passe contre la force brute
        $attempts = new LoginAttemptService((string) $user['identifiant'], $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        if ($attempts->isLocked()) {
            $this->flash('error', 'Trop de tentatives. Réessayez dans 15 minutes.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Vérification du mot de passe actuel avant toute modification
        $currentHash = $repo->findHashById($user['id']);

        if ($currentHash === null || !password_verify($current, $currentHash)) {
            $attempts->recordFailure();
            $this->flash('error', 'Mot de passe actuel incorrect.');
            $this->redirect('/mon-compte/mot-de-passe');
            return;
        }

        // Succès : hachage et mise à jour en base, régénération de session
        $attempts->recordSuccess();
        $newHash = password_hash($new, PASSWORD_BCRYPT, ['cost' => 12]);
        $repo->updatePasswordById($user['id'], $newHash);
        session_regenerate_id(true);

        $this->flash('success', 'Mot de passe mis à jour.');
        $this->redirect('/mon-compte/mot-de-passe');
    }
}

