<?php

namespace App\Controllers;

use App\Models\UserModel;

class Auth extends BaseController
{
    public function login()
    {
        if (session()->get('logged_in')) {
            return redirect()->to('/dashboard');
        }

        return view('auth/login');
    }

    public function attempt()
    {
        helper(['form']);

        $rules = [
            'email' => 'required|valid_email',
            'password' => 'required'
        ];

        if (!$this->validate($rules)) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Invalid login data');
        }

        $email = $this->request->getPost('email');
        $password = $this->request->getPost('password');

        $model = new UserModel();

        $user = $model->where('email', $email)->first();

        if (!$user) {
            return redirect()
                ->back()
                ->with('error', 'Invalid login credentials');
        }

        if (!password_verify($password, $user->password)) {
            return redirect()
                ->back()
                ->with('error', 'Invalid login credentials');
        }

        session()->regenerate(); // security

        session()->set([
            'user_id' => $user->id,
            'name' => $user->name,
            'role' => $user->role,
            'logged_in' => true
        ]);

        return redirect()->to('/dashboard');
    }

    public function logout()
    {
        session()->destroy();

        return redirect()->to('/');
    }
}