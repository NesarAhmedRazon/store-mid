<?php
// app/Controllers/Auth.php

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

        $ip = $this->request->getIPAddress();
        $email = $this->request->getPost('email');

        // Safe cache key (no invalid chars)
        $key = 'login_attempts_' . md5($email . '_' . $ip);

        $throttle = cache()->get($key) ?? 0;

        if ($throttle >= 5) {
            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Too many attempts. Try again in 5 minutes.');
        }

        // Validation
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

        $model = new UserModel();

        $user = $model->where('email', $email)->first();

        // ❌ Failed login (user not found OR wrong password)
        if (!$user || !password_verify($password = $this->request->getPost('password'), $user->password)) {

            // increment attempts
            cache()->save($key, $throttle + 1, rand(300, 1800)); // 5~30 minutes

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Invalid login credentials');
        }

        // ❌ Inactive account
        if (method_exists($user, 'isActive') && !$user->isActive()) {
            return redirect()
                ->back()
                ->with('error', 'Your account has been disabled.');
        }

        // ✅ SUCCESS LOGIN

        // Clear throttle
        cache()->delete($key);

        // Regenerate session (security)
        session()->regenerate();

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