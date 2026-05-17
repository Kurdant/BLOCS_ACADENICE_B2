<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\BaseController;
use App\Core\Validator;
use App\Repositories\CategorieRepository;
use App\Repositories\ProduitRepository;
use App\Services\TraceService;
use App\Services\UploadService;

final class ProduitController extends BaseController
{
    // Affiche la liste de tous les produits (actifs et inactifs)
    public function index(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $repo = new ProduitRepository();
        $this->view('produits/index', [
            'title'     => 'Produits',
            'flash'     => $this->getFlash(),
            'produits'  => $repo->findAll(),
            'csrfToken' => $this->csrfToken(),
        ]);
    }

    // Affiche le formulaire de création d'un produit
    public function create(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $catRepo = new CategorieRepository();
        $this->view('produits/create', [
            'title'      => 'Créer un produit',
            'flash'      => $this->getFlash(),
            'categories' => $catRepo->findAllActive(),
            'csrfToken'  => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire de création
    public function store(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $nom         = trim((string) ($_POST['nom']          ?? ''));
        $description = trim((string) ($_POST['description']  ?? ''));
        $prixRaw     = trim((string) ($_POST['prix']         ?? ''));
        $idCategorie = (int) ($_POST['id_categorie']         ?? 0);

        $v = new Validator();
        $v->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 150, 'Nom')
          ->required('description', $description, 'Description')
          ->required('prix', $prixRaw, 'Prix')
          ->positiveNumber('prix', $prixRaw, 'Prix');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect('/produits/creer');
            return;
        }

        // Vérifier que la catégorie soumise existe et est active
        $catRepo    = new CategorieRepository();
        $categories = $catRepo->findAllActive();

        if (!in_array($idCategorie, array_map('intval', array_column($categories, 'id_categorie')), true)) {
            $this->flash('error', 'Catégorie invalide ou inactive.');
            $this->redirect('/produits/creer');
            return;
        }

        // Upload de l'image (obligatoire à la création)
        $imageFile = $_FILES['image'] ?? [];

        if (!isset($imageFile['error']) || $imageFile['error'] === UPLOAD_ERR_NO_FILE) {
            $this->flash('error', 'Une image est obligatoire.');
            $this->redirect('/produits/creer');
            return;
        }

        try {
            $imagePath = (new UploadService())->stocker($imageFile, 'produits');
        } catch (\RuntimeException $e) {
            $this->flash('error', $e->getMessage());
            $this->redirect('/produits/creer');
            return;
        }

        $repo = new ProduitRepository();
        $id   = $repo->create([
            'id_categorie' => $idCategorie,
            'nom'          => $nom,
            'description'  => $description,
            'prix'         => $prixRaw,
            'image'        => $imagePath,
        ]);

        (new TraceService())->log('creation', 'produits', $id, "nom={$nom}");

        $this->flash('success', "Produit « {$nom} » créé.");
        $this->redirect('/produits');
    }

    // Affiche le formulaire d'édition d'un produit
    public function edit(array $args = []): void
    {
        $this->requireRole(['Administration']);

        $id      = (int) ($args['id'] ?? 0);
        $repo    = new ProduitRepository();
        $produit = $repo->findById($id);

        if ($produit === null) {
            $this->abort(404);
            return;
        }

        $catRepo = new CategorieRepository();
        $this->view('produits/edit', [
            'title'      => 'Modifier un produit',
            'flash'      => $this->getFlash(),
            'produit'    => $produit,
            'categories' => $catRepo->findAllActive(),
            'csrfToken'  => $this->csrfToken(),
        ]);
    }

    // Traite la soumission du formulaire d'édition
    public function update(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id      = (int) ($args['id'] ?? 0);
        $repo    = new ProduitRepository();
        $produit = $repo->findById($id);

        if ($produit === null) {
            $this->abort(404);
            return;
        }

        $nom         = trim((string) ($_POST['nom']         ?? ''));
        $description = trim((string) ($_POST['description'] ?? ''));
        $prixRaw     = trim((string) ($_POST['prix']        ?? ''));
        $idCategorie = (int) ($_POST['id_categorie']        ?? 0);
        $disponible  = isset($_POST['disponible']);

        $v = new Validator();
        $v->required('nom', $nom, 'Nom')
          ->maxLength('nom', $nom, 150, 'Nom')
          ->required('description', $description, 'Description')
          ->required('prix', $prixRaw, 'Prix')
          ->positiveNumber('prix', $prixRaw, 'Prix');

        if ($v->fails()) {
            $this->flash('error', $v->firstError());
            $this->redirect("/produits/{$id}/editer");
            return;
        }

        // Vérifier que la catégorie soumise existe et est active
        $catRepo    = new CategorieRepository();
        $categories = $catRepo->findAllActive();

        if (!in_array($idCategorie, array_map('intval', array_column($categories, 'id_categorie')), true)) {
            $this->flash('error', 'Catégorie invalide ou inactive.');
            $this->redirect("/produits/{$id}/editer");
            return;
        }

        // Upload d'image optionnel — si absent, conserver l'image actuelle
        $data = [
            'id_categorie' => $idCategorie,
            'nom'          => $nom,
            'description'  => $description,
            'prix'         => $prixRaw,
            'disponible'   => $disponible,
        ];

        $imageFile = $_FILES['image'] ?? [];

        if (isset($imageFile['error']) && $imageFile['error'] !== UPLOAD_ERR_NO_FILE) {
            try {
                $newImagePath = (new UploadService())->stocker($imageFile, 'produits');
            } catch (\RuntimeException $e) {
                $this->flash('error', $e->getMessage());
                $this->redirect("/produits/{$id}/editer");
                return;
            }

            // Supprimer l'ancienne image après upload réussi
            if (!empty($produit['image'])) {
                (new UploadService())->supprimer($produit['image']);
            }

            $data['image'] = $newImagePath;
        }

        $repo->update($id, $data);

        (new TraceService())->log('modification', 'produits', $id, "nom={$nom}");

        $this->flash('success', "Produit « {$nom} » mis à jour.");
        $this->redirect('/produits');
    }

    // Désactive un produit (soft delete)
    public function desactiver(array $args = []): void
    {
        $this->requireRole(['Administration']);
        $this->requireCsrf();

        $id      = (int) ($args['id'] ?? 0);
        $repo    = new ProduitRepository();
        $produit = $repo->findById($id);

        if ($produit === null) {
            $this->abort(404);
            return;
        }

        $repo->desactiver($id);

        (new TraceService())->log('desactivation', 'produits', $id, "nom={$produit['nom']}");

        $this->flash('success', "Produit « {$produit['nom']} » désactivé.");
        $this->redirect('/produits');
    }
}
