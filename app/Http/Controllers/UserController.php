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
        $userId = auth('sanctum')->id();

        $user->load('media');
        
        $posts = Post::with(['media', 'user:id,name,email', 'user.media', 'comments.user:id,name', 'comments.user.media', 'comments.media', 'reactions'])
            ->withCount(['comments', 'reactions'])
            ->where('user_id', $user->id)
            ->latest()
            ->cursorPaginate(15);
        
        // Add reaction data to each post (same as PostController)
        $posts->getCollection()->each(function($post) use ($userId) {
            $post->user_reaction = $userId 
                ? $post->reactions->where('user_id', $userId)->first()?->type
                : null;
            
            $post->reactions_summary = $post->reactions
                ->groupBy('type')
                ->map(fn($group) => $group->count());
        });

        return response()->json([
            'user' => $user->makeHidden(['email']),
            'posts' => $posts,
            'posts_count' => Post::where('user_id', $user->id)->count(),
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
