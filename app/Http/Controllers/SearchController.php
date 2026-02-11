<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Post;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    public function search(Request $request){
        $request->validate([
            'q' => 'required|string|max:255',
        ]);
        $query = $request->input('q', '');
        $users = User::with('media')
            ->where('name', 'like', "%$query%")
            ->take(20)
            ->get();
        $posts = Post::with('user','user.media','comments.user', 'comments.user.media', 'comments.media', 'media','reactions', 'reactions.user')
            ->withCount('comments','reactions')->where('content', 'like', "%$query%")
            ->latest()
            ->cursorPaginate(15);

            return response()->json([
                'users' => $users,
                'posts' => $posts,
            ]);
    }

    public function searchMessages(Request $request){
        $request->validate([
            'q' => 'required|string|max:255',
        ]);
        $query = $request->input('q', '');
        $userId = auth('sanctum')->id();

        $messages = Message::participant($userId)
            ->with('conversation', 'user')
            ->where('content', 'like', "%$query%")
            ->latest()->cursorPaginate(20);

            return response()->json([
                'messages' => $messages,
            ]);
    }
}
