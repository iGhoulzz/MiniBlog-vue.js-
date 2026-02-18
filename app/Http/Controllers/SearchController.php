<?php

namespace App\Http\Controllers;
use App\Models\User;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\Post;

use Illuminate\Http\Request;

class SearchController extends Controller
{
    /**
     * Dropdown search – returns up to 10 users & 10 posts + total counts.
     */
    public function search(Request $request){
        $request->validate([
            'q' => 'required|string|max:255',
        ]);
        $query = $request->input('q', '');

        $usersQuery = User::with('media')
            ->where('name', 'like', "%$query%");
        $usersTotal = $usersQuery->count();
        $users = $usersQuery->take(10)->get();

        $postsQuery = Post::with('user','user.media','media')
            ->withCount('comments','reactions')
            ->where('content', 'like', "%$query%")
            ->latest();
        $postsTotal = $postsQuery->count();
        $posts = $postsQuery->take(10)->get();

        return response()->json([
            'users'       => $users,
            'users_total' => $usersTotal,
            'posts'       => $posts,
            'posts_total' => $postsTotal,
        ]);
    }

    /**
     * Full search results page – cursor-paginated users & posts.
     */
    public function fullSearch(Request $request){
        $request->validate([
            'q'    => 'required|string|max:255',
            'type' => 'sometimes|string|in:users,posts,messages',
        ]);
        $query = $request->input('q', '');
        $type  = $request->input('type', '');

        $data = [];

        if (!$type || $type === 'users') {
            $data['users'] = User::with('media')
                ->where('name', 'like', "%$query%")
                ->cursorPaginate(10)->withQueryString();
        }

        if (!$type || $type === 'posts') {
            $data['posts'] = Post::with('user','user.media','comments.user','comments.user.media','comments.media','media','reactions','reactions.user')
                ->withCount('comments','reactions')
                ->where('content', 'like', "%$query%")
                ->latest()
                ->cursorPaginate(10)->withQueryString();
        }

        if ($type === 'messages') {
            $userId = auth('sanctum')->id();
            $data['messages'] = Message::participant($userId)
                ->with('conversation', 'user')
                ->where('content', 'like', "%$query%")
                ->latest()
                ->cursorPaginate(10)->withQueryString();
        }

        return response()->json($data);
    }

    /**
     * Dropdown message search – returns up to 10 messages + total count.
     */
    public function searchMessages(Request $request){
        $request->validate([
            'q' => 'required|string|max:255',
        ]);
        $query = $request->input('q', '');
        $userId = auth('sanctum')->id();

        $messagesQuery = Message::participant($userId)
            ->with('conversation', 'user')
            ->where('content', 'like', "%$query%")
            ->latest();
        $messagesTotal = $messagesQuery->count();
        $messages = $messagesQuery->take(10)->get();

        return response()->json([
            'messages'       => $messages,
            'messages_total' => $messagesTotal,
        ]);
    }
}
