<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoginController extends Controller
{
    public function create()
    {
        return view('login');
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($attributes)) {
            return redirect('/')->with('success', 'Login successful.');
        } else {
            return back()->withErrors(['email' => 'Invalid credentials'])->onlyInput('email');
        }
    }

    public function destroy()
    {
        Auth::logout();

        return redirect('/login')->with('success', 'Logged out successfully.');
    }
}
