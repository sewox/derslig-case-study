<?php

declare(strict_types=1);

namespace App\Service;

use App\Repository\UserRepository;

class UserService extends BaseService
{
    /**
     * UserService constructor.
     */
    public function __construct(UserRepository $repository)
    {
        parent::__construct($repository);
    }
}
