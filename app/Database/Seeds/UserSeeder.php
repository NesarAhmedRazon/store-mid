<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use App\Models\UserModel;
use App\Entities\User;
use App\Enums\UserRole;

class UserSeeder extends Seeder
{
    public function run()
    {
        $model = new UserModel();

        $user = new User();
        $user->fill([
            'name'   => env('ADMIN_NAME', 'Super Admin'),
            'email'  => env('ADMIN_EMAIL', 'admin@example.com'),
            'role'   => UserRole::ADMIN->value,
            'status' => 1,
        ]);
        $user->setPassword(env('ADMIN_PASSWORD', 'changeme123'));

        $model->save($user);
    }
}