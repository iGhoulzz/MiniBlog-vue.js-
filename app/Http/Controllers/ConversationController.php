<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Events\ConversationCreated;
use App\Events\MessagesRead;


class ConversationController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user
            ->conversations()
            ->with('users', 'messages.user', 'messages.readByUsers')
            ->get();

        return response()->json($conversations);
    }


    public function store(Request $request)
    {
        $user = $request->user();
        $attributes = $request->validate([
            'user_ids' => 'required|array|min:1',
            'user_ids.*' => 'exists:users,id|distinct',
            'content' => 'required|string|min:1|max:1000',
        ]);
            $conversation = DB::transaction(function () use ($request, $attributes) {
                $user = $request->user();

                $newConversation = Conversation::create();
                $userIds = array_unique(array_merge([$request->user()->id], $attributes['user_ids']));
                $newConversation->users()->attach($userIds);

                Message::create([
                    'content' => $attributes['content'],
                    'conversation_id' => $newConversation->id,
                    'user_id' => $request->user()->id,
                ]);
                return $newConversation->load('users', 'messages.user', 'messages.readByUsers');
            });
            foreach ($conversation->users as $participant) {
                event(new ConversationCreated($conversation, $participant));
            }

                return response()->json($conversation , 201);

    }


    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);
        $conversation->load('users', 'messages.user', 'messages.readByUsers');
        return response()->json($conversation);
    }


    public function destroy(Conversation $conversation)
    {
        $this->authorize('delete', $conversation);
        $conversation->delete();
        return response()->json(null, 204);
    }



    public function markAsRead(Conversation $conversation, Request $request)
    {
        $user = $request->user();
        $this->authorize('view', $conversation);

        $allMessageIds = $conversation->messages()->pluck('id');

        if ($allMessageIds->isEmpty()) {
            return response()->json([
                'message_ids' => [],
                'read_at' => null,
            ]);
        }

        $alreadyReadMessageIds = $user->readMessages()
            ->whereIn('message_id', $allMessageIds)
            ->pluck('message_id');

        $unreadMessageIds = $allMessageIds->diff($alreadyReadMessageIds);

        $now = now();

        if ($unreadMessageIds->isNotEmpty()) {
            $user->readMessages()->attach(
                $unreadMessageIds);
                MessagesRead::dispatch($conversation, $user, $unreadMessageIds->toArray(), $now);
            }
        return response()->json([
            'message_ids' => $unreadMessageIds->values(),
            'read_at' => $now->toISOString(),
        ]);
    }
}
