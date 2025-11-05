<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Post;
use App\Models\Comment;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function apiComment(Request $request, Post $post)
    {
        $attributes = $request->validate([
            'content' => 'required|min:2|max:150',
        ]);
        $attributes['user_id'] = $request->user()->id;

        $attributes['post_id'] = $post->id;

        Comment::create($attributes);
        return response()->json([
            'message' => 'Comment added successfully.'
        ], 201);

    }

    ///

    public function apiCommentDelete(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }

   ///

    public function apiCommentEdit(Comment $comment)
    {
        $this->authorize('update', $comment);
        return response()->json($comment);
    }

    public function apiCommentUpdate(Request $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $attributes = $request->validate([
            'content' => 'required|min:2|max:150',
        ]);

        $comment->update($attributes);

        return response()->json([
            'message' => 'Comment updated successfully.'
        ], 200);
    }
}
