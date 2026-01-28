<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Http\Requests\UpdateUserRequest;
use App\Models\Post;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    use AuthorizesRequests;

    public function apiShowUser(User $user)
    {
        $posts = Post::with(['user:id,name', 'comments.user:id,name'])
            ->where('user_id', $user->id)
            ->get();
        return response()->json([
            'user' => $user->makeHidden(['email']),
            'posts' => $posts,
        ]);
    }


    public function apiUpdateUser(UpdateUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $attributes = $request->validated();

        if ($request->hasFile('avatar')){
             $user->updateSingleMedia($request->file('avatar'), 'avatar', 'avatars');
        } else if ($request->input('remove_avatar')) {
             $media = $user->media()->where('collection', 'avatar')->first();
             if ($media) {
                 $user->removeMedia($media);
             }
        }

        unset($attributes['avatar']);
        unset($attributes['remove_avatar']);

        $user->update($attributes);


        $user->refresh();

        return response()->json([
            'message' => 'User updated successfully',
            'user' => $user,
        ]);
    }
}
