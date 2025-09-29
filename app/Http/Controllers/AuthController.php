<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AuthController extends Controller
{
    public function showSignUp()
    {
        return view('pages.auth.sign-up');
    }

    public function logout()
    {
        Auth::logout();

        return redirect()->route('index');
    }
}
