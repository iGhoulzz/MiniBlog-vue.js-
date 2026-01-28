<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Http\Requests\StorePostRequest;
use App\Http\Requests\UpdatePostRequest;
use App\Models\Media;
use Illuminate\Support\Facades\DB;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

class PostController extends Controller
{
    use AuthorizesRequests;

    public function index(){

    $userId = auth('sanctum')->id();

    $posts = Post::with(['media' , 'user:id,name', 'comments.user:id,name', 'reactions'])
    ->withCount(['comments', 'reactions'])->latest()->get();

    $posts->each(function($post) use ($userId) {
        $post->user_reaction = $post->reactions
            ->where('user_id', $userId)
            ->first()
            ?->type;
    });

    return response()->json($posts);

}




    public function apiPost(StorePostRequest $request)
    {
        $attributes = $request->validated();

        $attributes['user_id'] = $request->user()->id;

        return DB::transaction(function () use ($attributes, $request) {

        $post = Post::create($attributes);

        if ($request->hasFile('image')) {
            $post->addMedia($request->file('image'), 'posts');
        }

        return response()->json([
            'message' => 'Post created successfully.',
            'post' => $post,
        ], 201);
    });
    }



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
        $userId = auth('sanctum')->id();

        $post->load(['media', 'user:id,name', 'comments.user:id,name']);

        // Add post reaction data
        $userReaction = $post->reactions()->where('user_id', $userId)->first();
        $post->user_reaction = $userReaction?->type;
        $post->reactions_summary = $post->reactions()
            ->selectRaw('type, COUNT(*) as count')
            ->groupBy('type')
            ->pluck('count', 'type');
        $post->reactions_count = $post->reactions()->count();

        // Add comment reaction data
        $post->comments->transform(function ($comment) use ($userId) {
            $commentReaction = $comment->reactions()->where('user_id', $userId)->first();
            $comment->user_reaction = $commentReaction?->type;
            $comment->reactions_summary = $comment->reactions()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type');
            $comment->reactions_count = $comment->reactions()->count();
            return $comment;
        });

        return response()->json($post);
    }

    public function apiPostEdit(Post $post)
    {
        $this->authorize('update', $post);
        return response()->json($post);
    }
    public function apiPostUpdate(UpdatePostRequest $request, Post $post)
    {
        $this->authorize('update', $post);

        $attributes = $request->validated();

        if ($request->hasFile('image')){
             $post->updateSingleMedia($request->file('image'), 'default', 'posts');
        } else if ($request->input('remove_image')) {
             $media = $post->media()->where('collection', 'default')->first();
             if ($media) {
                 $post->removeMedia($media);
             }
        }

        unset($attributes['image']);
        unset($attributes['remove_image']);

        $post->update($attributes);

        return response()->json([
            'message' => 'Post updated successfully.'
        ], 200);
    }
}

