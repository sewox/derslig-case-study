<?php

declare(strict_types=1);

namespace App\Services;

use App\Repository\BaseRepository;

class BaseService implements BaseInterface
{
    protected BaseRepository $repository;

    /**
     * BaseService constructor.
     */
    public function __construct(BaseRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * Get the model instance.
     *
     * @return mixed
     */
    public function model()
    {
        return $this->repository->model();
    }

    /**
     * Get the repository instance.
     */
    public function repository(): BaseRepository
    {
        return $this->repository;
    }

    /**
     * Create a new record.
     *
     * @return mixed
     */
    public function create(array $data)
    {
        return $this->repository->create($data);
    }

    /**
     * Update an existing record.
     *
     * @param  int|string  $id
     * @return mixed
     */
    public function update($id, array $data)
    {
        return $this->repository->update($id, $data);
    }

    /**
     * Delete a record.
     *
     * @param  int|string  $id
     * @return mixed
     */
    public function delete($id)
    {
        return $this->repository->delete($id);
    }

    /**
     * Get all records.
     *
     * @return mixed
     */
    public function getAll()
    {
        return $this->repository->getAll();
    }

    /**
     * Get a single record by ID.
     *
     * @param  int|string  $id
     * @return mixed
     */
    public function get($id)
    {
        return $this->repository->get($id);
    }
}
