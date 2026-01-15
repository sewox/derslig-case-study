<?php

namespace App\Repository;

use App\Models\User;

class UserRepository extends BaseRepository
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
        return $this->model->create($data);
    }
    
    public function update($id, $data)
    {
        return $this->model->update($id, $data);
    }
    
    public function delete($id)
    {
        return $this->model->delete($id);
    }
    
    public function getAll()
    {
        return $this->model->all();
    }
    
    public function get($id)
    {
        return $this->model->find($id);
    }
        
}
