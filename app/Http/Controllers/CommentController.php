<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Post $post)
    {
        $attributes = $request->validate([
            'content' => 'required|min:2|max:150',
        ]);
        $attributes['user_id'] = $request->user()->id;

        $attributes['post_id'] = $post->id;

        Comment::create($attributes);
        return redirect('/posts/'.$post->id)->with('success', 'Comment added successfully.');

    }

    ///

    public function destroy(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return redirect('/posts/'.$comment->post->id)->with('success', 'Comment deleted successfully.');
    }

   ///

    public function edit(Comment $comment)
    {
        $this->authorize('update', $comment);
        return view('comments.edit', compact('comment'));
    }

    public function update(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $attributes = $request->validate([
            'content' => 'required|min:2|max:150',
        ]);

        $comment->update($attributes);

        return redirect('/posts/'.$comment->post->id)->with('success', 'Comment updated successfully.');
    }
}
