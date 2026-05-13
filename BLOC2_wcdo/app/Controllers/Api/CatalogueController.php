<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\BaseController;

final class CatalogueController extends BaseController
{
    public function index(array $args = []): void
    {
        $this->json(['todo' => 'Api\\CatalogueController::index']);
    }
}
