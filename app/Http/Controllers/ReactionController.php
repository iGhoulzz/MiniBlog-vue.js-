<?php

namespace App\Http\Controllers;

use App\Http\Requests\ReactRequest;
use App\Models\Reaction;
use App\Models\Post;
use App\Models\Comment;
use App\Notifications\ReactionNotification;
use Illuminate\Http\Request;

class ReactionController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'reactionable_type' => 'required|string|in:post,comment',
            'reactionable_id' => 'required|integer',
        ]);

        $modelClass = $request->reactionable_type === 'post' ? Post::class : Comment::class;

        // Fetch ALL reactions for this item in one query (for summary + count)
        $allReactions = Reaction::with(['user:id,name', 'user.media'])
            ->where('reactionable_type', $modelClass)
            ->where('reactionable_id', $request->reactionable_id)
            ->get();

        $reactions_summary = $allReactions->countBy('type');
        $reactions_count = $allReactions->count();

        // If filtering by type, filter the collection (no extra query)
        $reactions = $request->type
            ? $allReactions->where('type', $request->type)->values()
            : $allReactions;

        return response()->json([
            'reactions' => $reactions,
            'reactions_summary' => $reactions_summary,
            'reactions_count' => $reactions_count,
        ]);
    }

    public function react(ReactRequest $request)
    {
        $modelClass = $request->getModelClass();
        $reactionable = $modelClass::find($request->reactionable_id);
        
        if (!$reactionable) {
            return response()->json(['message' => 'Resource not found'], 404);
        }

        $existingReaction = Reaction::where('user_id', auth()->id())
            ->where('reactionable_type', $modelClass)
            ->where('reactionable_id', $request->reactionable_id)
            ->first();

        $action = 'added';
        $userReaction = $request->type;

        if ($existingReaction) {
            if ($existingReaction->type === $request->type) {
                $existingReaction->delete();
                $action = 'removed';
                $userReaction = null;
            } else {
                $existingReaction->update(['type' => $request->type]);
                $action = 'updated';
            }
        } else {
            Reaction::create([
                'user_id' => auth()->id(),
                'reactionable_type' => $modelClass,
                'reactionable_id' => $request->reactionable_id,
                'type' => $request->type,
            ]);

            // Send notification to post/comment owner (only on NEW reactions)
            $this->notifyOwner($reactionable, $request->type, $request->reactionable_type);
        }

        return response()->json([
            'message' => "Reaction {$action}",
            'user_reaction' => $userReaction,
            'reactions_count' => $reactionable->reactions()->count(),
            'reactions_summary' => $reactionable->reactions()
                ->selectRaw('type, COUNT(*) as count')
                ->groupBy('type')
                ->pluck('count', 'type'),
        ], $action === 'added' ? 201 : 200);
    }

    private function notifyOwner($reactionable, $reactionType, $reactionableType)
    {
        $owner = $reactionable->user;
        
        // Skip notification if owner doesn't exist
        if (!$owner) {
            return;
        }

        // Don't notify if reacting to your own content
        if ($owner->id === auth()->id()) {
            return;
        }

        $owner->notify(new ReactionNotification(
            auth()->user(),
            $reactionType,
            $reactionableType,
            $reactionable->id
        ));
    }
}
