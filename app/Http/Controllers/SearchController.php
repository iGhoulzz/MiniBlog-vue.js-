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
        $users = User::where('name', 'like', "%$query%")->get();
        $posts = Post::with('user','comments.user', 'media','reactions', 'reactions.user')
            ->withCount('comments','reactions')->where('content', 'like', "%$query%")
            ->get();

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
            ->latest()->get();

            return response()->json([
                'messages' => $messages,
            ]);
    }
}
