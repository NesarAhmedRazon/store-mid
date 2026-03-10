<?php

namespace App\Models;

use CodeIgniter\Model;
use App\Entities\User;

class UserModel extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'id';

    protected $returnType = User::class;

    protected $allowedFields = [
        'name',
        'email',
        'password',
        'role',
        'status'
    ];

    protected $useTimestamps = true;
}