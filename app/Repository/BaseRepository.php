<?php

declare(strict_types=1);

namespace App\Repository;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

abstract class BaseRepository implements BaseRepositoryInterface
{
    protected Model $model;

    /**
     * BaseRepository constructor.
     */
    public function __construct()
    {
        $this->model = app($this->model());
    }

    /**
     * Get the model class name.
     */
    abstract public function model(): string;

    /**
     * Create a new record.
     */
    public function create(array $data): Model
    {
        return $this->model->create($data);
    }

    /**
     * Update an existing record.
     *
     * @param  int|string  $id
     */
    public function update($id, array $data): bool
    {
        $record = $this->get($id);
        if ($record) {
            return $record->update($data);
        }

        return false;
    }

    /**
     * Delete a record.
     *
     * @param  int|string  $id
     */
    public function delete($id): bool
    {
        $record = $this->get($id);
        if ($record) {
            return (bool) $record->delete();
        }

        return false;
    }

    /**
     * Get all records.
     */
    public function getAll(): Collection
    {
        return $this->model->all();
    }

    /**
     * Get a single record by ID.
     *
     * @param  int|string  $id
     */
    public function get($id): ?Model
    {
        return $this->model->find($id);
    }
}
