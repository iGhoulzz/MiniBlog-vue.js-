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
        $posts = Post::with('user')->get();
        // $posts = Post::query()->get();

        return view('posts.index', compact('posts'));
    }

    public function store(Request $request)
    {
        $attributes = $request->validate([

            'content' => 'required|min:5|max:255',
        ]);

        $attributes['user_id'] = $request->user()->id;

        Post::create($attributes);

        return redirect('/')->with('success', 'Post created successfully.');
    }

    ///

    public function destroy(Post $post)
    {
        $this->authorize('delete', $post);
        $post->delete();
        return redirect('/')->with('success', 'Post deleted successfully.');
    }

    ///

    public function show(Post $post)
    {
        $post->load('comments.user');
        return view('posts.show', compact('post'));
    }

    public function edit(Post $post)
    {
        $this->authorize('update', $post);
        return view('posts.edit', compact('post'));
    }
    public function update(Request $request, Post $post)
    {
        $this->authorize('update', $post);

        $attributes = $request->validate([
            'content' => 'required|min:5|max:255',
        ]);

        $post->update($attributes);

        return redirect('/')->with('success', 'Post updated successfully.');
    }
}
