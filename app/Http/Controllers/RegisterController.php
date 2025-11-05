<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class RegisterController extends Controller
{
    public function apiRegister(Request $request)
    {
        $attributes = $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8|confirmed',
        ]);

        $attributes['password'] = Hash::make($attributes['password']);

        User::create($attributes);

        return response()->json(['message' => 'Registration successful. Please login.'], 201);
    }
}
