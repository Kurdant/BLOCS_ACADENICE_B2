<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

// Authentification
$router->get('/login',   'App\Controllers\AuthController::showLogin');
$router->post('/login',  'App\Controllers\AuthController::login');
$router->post('/logout', 'App\Controllers\AuthController::logout');

// Profil personnel
$router->get('/mon-compte/mot-de-passe',  'App\Controllers\UtilisateurController::editPassword');
$router->post('/mon-compte/mot-de-passe', 'App\Controllers\UtilisateurController::updatePassword');

// Tableau de bord
$router->get('/',          'App\Controllers\DashboardController::index');
$router->get('/dashboard', 'App\Controllers\DashboardController::index');

// Utilisateurs (Administration)
$router->get('/utilisateurs',                  'App\Controllers\UtilisateurController::index');
$router->get('/utilisateurs/creer',            'App\Controllers\UtilisateurController::create');
$router->post('/utilisateurs',                 'App\Controllers\UtilisateurController::store');
$router->get('/utilisateurs/{id}/editer',      'App\Controllers\UtilisateurController::edit');
$router->post('/utilisateurs/{id}',            'App\Controllers\UtilisateurController::update');
$router->post('/utilisateurs/{id}/desactiver', 'App\Controllers\UtilisateurController::desactiver');

// Catégories
$router->get('/categories',                  'App\Controllers\CategorieController::index');
$router->get('/categories/creer',            'App\Controllers\CategorieController::create');
$router->post('/categories',                 'App\Controllers\CategorieController::store');
$router->get('/categories/{id}/editer',      'App\Controllers\CategorieController::edit');
$router->post('/categories/{id}',            'App\Controllers\CategorieController::update');
$router->post('/categories/{id}/desactiver', 'App\Controllers\CategorieController::desactiver');

// Produits
$router->get('/produits',                  'App\Controllers\ProduitController::index');
$router->get('/produits/creer',            'App\Controllers\ProduitController::create');
$router->post('/produits',                 'App\Controllers\ProduitController::store');
$router->get('/produits/{id}/editer',      'App\Controllers\ProduitController::edit');
$router->post('/produits/{id}',            'App\Controllers\ProduitController::update');
$router->post('/produits/{id}/desactiver', 'App\Controllers\ProduitController::desactiver');

// Menus
$router->get('/menus',                  'App\Controllers\MenuController::index');
$router->get('/menus/creer',            'App\Controllers\MenuController::create');
$router->post('/menus',                 'App\Controllers\MenuController::store');
$router->get('/menus/{id}/editer',      'App\Controllers\MenuController::edit');
$router->post('/menus/{id}',            'App\Controllers\MenuController::update');
$router->post('/menus/{id}/desactiver', 'App\Controllers\MenuController::desactiver');

// Sections de menu
$router->get('/menus/{id}/sections',  'App\Controllers\SectionMenuController::index');
$router->post('/menus/{id}/sections', 'App\Controllers\SectionMenuController::store');

// Options de menu
$router->post('/sections/{id}/options',   'App\Controllers\OptionMenuController::store');
$router->post('/options/{id}/desactiver', 'App\Controllers\OptionMenuController::desactiver');

// Commandes (Accueil + Preparation)
$router->get('/commandes',                'App\Controllers\CommandeController::index');
$router->get('/commandes/creer',          'App\Controllers\CommandeController::create');
$router->post('/commandes',               'App\Controllers\CommandeController::store');
$router->get('/commandes/{id}',           'App\Controllers\CommandeController::show');
$router->post('/commandes/{id}/preparee', 'App\Controllers\CommandeController::marquerPreparee');
$router->post('/commandes/{id}/livree',   'App\Controllers\CommandeController::marquerLivree');
