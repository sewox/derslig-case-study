<?php

declare(strict_types=1);

namespace App\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

interface BaseRepositoryInterface
{
    public function create(array $data): Model;

    public function update($id, array $data): bool;

    public function delete($id): bool;

    public function getAll(): Collection;

    public function get($id): ?Model;
}