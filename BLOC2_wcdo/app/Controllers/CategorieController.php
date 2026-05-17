<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Validator;
use App\Repositories\CategorieRepository;
use App\Services\TraceService;

final class CategorieController extends BaseController
{
    // Affiche la liste de toutes les catégories (actives et inactives)
    public function index(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $repo = new CategorieRepository();
        $this->view('categories/index', [
            'title'      => 'Catégories',
            'flash'      => $this->getFlash(),
            'categories' => $repo->findAll(),
            'csrfToken'  => $this->csrfToken(),
        ]);
    }

    // Affiche le formulaire de création d'une catégorie
    public function create(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $this->view('categories/create', [
            'title'     => 'Créer une catégorie',
            'flash'     => $this->getFlash(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de création
    public function store(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $nom         = trim((string) ($_POST['nom']         ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $description = $description === '' ? null : $description;

        $v = new Validator();
        $v->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 100, 'Nom');

        if ($description !== null) {
            $v->maxLength('description', $description, 500, 'Description');
        }

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/categories/creer');
            return;
        }

        $repo = new CategorieRepository();

        if ($repo->existsByNom($nom)) {
            $this->flash('error', "Une catégorie « {$nom} » existe déjà.");
            $this->redirect('/categories/creer');
            return;
        }

        $id = $repo->create($nom, $description);

        (new TraceService())->log('creation', 'categories', $id, "nom={$nom}");

        $this->flash('success', "Catégorie « {$nom} » créée.");
        $this->redirect('/categories');
    }

    // Affiche le formulaire d'édition d'une catégorie
    public function edit(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $id        = (int) ($args['id'] ?? 0);
        $repo      = new CategorieRepository();
        $categorie = $repo->findById($id);

        if ($categorie === null) {
            $this->abort(404);
            return;
        }

        $this->view('categories/edit', [
            'title'     => 'Modifier une catégorie',
            'flash'     => $this->getFlash(),
            'categorie' => $categorie,
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire d'édition
    public function update(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id        = (int) ($args['id'] ?? 0);
        $repo      = new CategorieRepository();
        $categorie = $repo->findById($id);

        if ($categorie === null) {
            $this->abort(404);
            return;
        }

        $nom         = trim((string) ($_POST['nom']         ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $description = $description === '' ? null : $description;

        $v = new Validator();
        $v->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 100, 'Nom');

        if ($description !== null) {
            $v->maxLength('description', $description, 500, 'Description');
        }

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect("/categories/{$id}/editer");
            return;
        }

        if ($repo->existsByNom($nom, $id)) {
            $this->flash('error', "Une catégorie « {$nom} » existe déjà.");
            $this->redirect("/categories/{$id}/editer");
            return;
        }

        $repo->update($id, $nom, $description);

        (new TraceService())->log('modification', 'categories', $id, "nom={$nom}");

        $this->flash('success', "Catégorie « {$nom} » mise à jour.");
        $this->redirect('/categories');
    }

    // Désactive une catégorie (soft delete)
    public function desactiver(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id        = (int) ($args['id'] ?? 0);
        $repo      = new CategorieRepository();
        $categorie = $repo->findById($id);

        if ($categorie === null) {
            $this->abort(404);
            return;
        }

        $repo->desactiver($id);

        (new TraceService())->log('desactivation', 'categories', $id, "nom={$categorie['nom']}");

        $this->flash('success', "Catégorie « {$categorie['nom']} » désactivée.");
        $this->redirect('/categories');
    }
}
