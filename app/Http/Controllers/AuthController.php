<?php

namespace App\Http\Controllers;

class AuthController extends Controller
{
    public function login()
    {
        if (auth()->user()) {
            return redirect('home');
        }

        return view('auth.login');
    }

    public function logout()
    {
        auth()->logout();

        return redirect()->route('login');
    }
}
