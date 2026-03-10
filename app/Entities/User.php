<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;

class User extends Entity
{
    protected $dates = ['created_at','updated_at'];

    protected $casts = [
        'id' => 'integer'
    ];

    public function setPassword(string $password)
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_DEFAULT);
        return $this;
    }

    public function verifyPassword(string $password): bool
    {
        return password_verify($password, $this->password);
    }
}