<?php

namespace App\Entities;

use CodeIgniter\Entity\Entity;
use App\Enums\UserRole;

class User extends Entity
{
    protected $attributes = [
        'id'         => null,
        'name'       => null,
        'email'      => null,
        'password'   => null,
        'role'       => null,
        'status'     => null,
        'created_at' => null,
        'updated_at' => null,
    ];

    protected $casts = [
        'id'     => 'integer',
        'status' => 'boolean',
    ];

    /**
     * Automatically hash the password whenever it is set.
     */
    public function setPassword(string $password): static
    {
        $this->attributes['password'] = password_hash($password, PASSWORD_BCRYPT);

        return $this;
    }

    /**
     * Return the role as a UserRole enum instance.
     */
    public function getRoleEnum(): UserRole
    {
        return UserRole::from($this->attributes['role']);
    }

    /**
     * Convenience checkers.
     */
    public function isAdmin(): bool
    {
        return $this->attributes['role'] === UserRole::ADMIN->value;
    }

    public function isStaff(): bool
    {
        return $this->attributes['role'] === UserRole::STAFF->value;
    }

    public function isViewer(): bool
    {
        return $this->attributes['role'] === UserRole::VIEWER->value;
    }

    public function isActive(): bool
    {
        return (bool) $this->attributes['status'];
    }
}