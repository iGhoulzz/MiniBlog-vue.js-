<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function create()
    {
        return view('register');
    }

    public function store(Request $request)
    {

        $attributes = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ],
        //     [
        //         'name.required' => 'Name is required',
        //         'email.required' => 'Email is required',
        //         'email.email' => 'Email must be a valid email address',
        //         'password.required' => 'Password is required',
        //         'password.min' => 'Password must be at least 8 characters',
        //         'password.confirmed' => 'Password confirmation does not match',
        //     ]
        );
        $attributes['password'] = Hash::make($attributes['password']);

        User::create($attributes);

        return redirect('/login')->with('success', 'Registration successful. Please login.');

    }
}
