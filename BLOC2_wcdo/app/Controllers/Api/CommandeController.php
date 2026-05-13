<?php

declare(strict_types=1);

namespace App\Controllers\Api;

use App\Core\BaseController;

final class CommandeController extends BaseController
{
    public function store(array $args = []): void
    {
        $this->json(['todo' => 'Api\\CommandeController::store'], 201);
    }
}
