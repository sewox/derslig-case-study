<?php

declare(strict_types=1);

namespace App\Service;

interface BaseInterface
{
    public function getAll();

    public function get($id);

    public function create(array $data);

    public function update($id, array $data);

    public function delete($id);
}
