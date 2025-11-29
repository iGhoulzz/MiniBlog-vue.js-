<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function apiShowUser(User $user)
    {
        $posts = Post::with('user', 'comments.user')->where('user_id', $user->id)->get();
        return response()->json([
            'user' => $user,
            'posts' => $posts,
        ]);
    }


    public function apiUpdateUser(Request $request, User $user)
    {
        $this->authorize('update', $user);
        $attributes = $request->validate([
            'name' => 'required|string|min:3|max:10',
            'avatar' => 'nullable|image',
            'remove_avatar' => 'nullable|boolean',
        ]);
        if ($request->hasFile('avatar')){

            if ($user->avatar){
                Storage::disk('public')->delete($user->avatar);
                }
            $path = $request->file('avatar')->store('avatars', 'public');
            $attributes['avatar'] = $path;

        } else if ($request->input('remove_avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }
            $attributes['avatar'] = null;
        }
        $user->update($attributes);


        $user->refresh(); // This reloads the model from the DB and runs all accessors

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }

    public function destroy(User $user)
    {

    }
}
