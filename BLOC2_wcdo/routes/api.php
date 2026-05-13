<?php

declare(strict_types=1);

/** @var \App\Core\Router $router */

$router->get('/api/catalogue',  'App\Controllers\Api\CatalogueController::index');
$router->post('/api/commandes', 'App\Controllers\Api\CommandeController::store');
