<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreCommentRequest;
use App\Http\Requests\UpdateCommentRequest;
use App\Models\Post;
use App\Models\Comment;
use App\Notifications\CommentNotification;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class CommentController extends Controller
{
    use AuthorizesRequests;

    public function apiComment(StoreCommentRequest $request, Post $post)
    {
        $attributes = $request->validated();
        $attributes['user_id'] = $request->user()->id;
        $attributes['post_id'] = $post->id;

        $comment = Comment::create($attributes);

        if ($request->hasFile('image')) {
            $comment->addMedia($request->file('image'), 'comments');
        }

        // Notify post owner (if not commenting on own post)
        if ($post->user_id !== auth()->id()) {
            $post->user->notify(new CommentNotification(
                auth()->user(),
                $post,
                $attributes['content']
            ));
        }

        return response()->json([
            'message' => 'Comment added successfully.'
        ], 201);
    }

    public function apiCommentDelete(Comment $comment)
    {
        $this->authorize('delete', $comment);
        $comment->delete();
        return response()->json([
            'message' => 'Comment deleted successfully.'
        ], 200);
    }

    public function apiCommentEdit(Comment $comment)
    {
        $this->authorize('update', $comment);
        return response()->json($comment);
    }

    public function apiCommentUpdate(UpdateCommentRequest $request, Comment $comment)
    {
        $this->authorize('update', $comment);

        $attributes = $request->validated();

        $comment->update($attributes);

        return response()->json([
            'message' => 'Comment updated successfully.'
        ], 200);
    }
}
