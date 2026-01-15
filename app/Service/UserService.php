<?php

namespace App\Service;

use App\Models\User;
use App\Repository\UserRepository;

class UserService extends BaseService
{
    public function model()
    {
        return User::class;
    }
    
    public function repository()
    {
        return UserRepository::class;
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