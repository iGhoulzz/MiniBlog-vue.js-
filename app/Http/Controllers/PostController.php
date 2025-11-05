<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PostController extends Controller
{
    use AuthorizesRequests;
    public function index()
    {
        $posts = Post::with('user')->withCount('comments')->latest()->get();
        // $posts = Post::query()->get(); (old version i then changed it to eager load user relationship)

        return response()->json($posts);
    }

    public function apiPost(Request $request)
    {
        $attributes = $request->validate([

            'content' => 'required|min:5|max:255',
        ]);

        $attributes['user_id'] = $request->user()->id;

        Post::create($attributes);

        return response()->json([
            'message' => 'Post created successfully.'
        ], 201);
    }

    ///

    public function apiPostDelete(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return response()->json([
            'message' => 'Post deleted successfully.'
        ], 200);
    }

    ///

    public function apiPostShow(Post $post)
    {
        $post->load('user', 'comments.user');
        return response()->json($post);
    }

    public function apiPostEdit(Post $post)
    {
        $this->authorize('update', $post);
        return response()->json($post);
    }
    public function apiPostUpdate(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $attributes = $request->validate([
            'content' => 'required|min:5|max:255',
        ]);

        $post->update($attributes);

        return response()->json([
            'message' => 'Post updated successfully.'
        ], 200);
    }
}
