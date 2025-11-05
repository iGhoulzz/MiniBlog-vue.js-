<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class UserController extends Controller
{

    use AuthorizesRequests;
    public function apiShowUser(User $user)
    {
        $post = Post::with('user', 'comments.user')->where('user_id', $user->id)->get();
        return response()->json([
            'user' => $user,
            'posts' => $post,
        ]);
    }




    public function apiUpdateUser(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $attributes = $request->validate([
            'name' => 'required|string|min:3|max:10',
            'avatar' => 'nullable|image',
        ]);
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $attributes['avatar'] = $path;
        }

        $user->update($attributes);
        return response()->json($user);
    }




    public function destroy(User $user)
    {

    }
}
