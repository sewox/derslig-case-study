<?php

namespace App\Service;

class BaseService
{
    public function model()
    {
        return $this->model;
    }
    
    public function repository()
    {
        return $this->repository;
    }

    public function create($data)
    {
        return $this->repository->create($data);
    }
    
    public function update($id, $data)
    {
        return $this->repository->update($id, $data);
    }
    
    public function delete($id)
    {
        return $this->repository->delete($id);
    }
    
    public function getAll()
    {
        return $this->repository->getAll();
    }
    
    public function get($id)
    {
        return $this->repository->get($id);
    }
    
}   