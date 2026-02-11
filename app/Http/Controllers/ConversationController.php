<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Events\ConversationCreated;
use App\Events\MessagesRead;
use App\Http\Requests\StoreConversationRequest;


class ConversationController extends Controller
{
    use AuthorizesRequests;
    public function index(Request $request)
    {
        $user = $request->user();

        $conversations = $user
            ->conversations()
            // Only show conversations that haven't been deleted, OR have new messages since deletion
            ->where(function ($q) {
                $q->whereNull('conversation_user.deleted_at')
                  ->orWhereColumn('conversations.updated_at', '>', 'conversation_user.deleted_at');
            })
            ->with(['users', 'messages' => function ($query) use ($user) {
                $query->whereDoesntHave('hiddenByUsers', function ($q) use ($user) {
                    $q->where('user_id', $user->id);
                })->with('user', 'readByUsers');
            }])
            ->get();

        // Filter out messages that were sent before the user "deleted" the chat
        $conversations->each(function ($conversation) {
            if ($conversation->pivot->deleted_at) {
                $conversation->setRelation('messages',
                    $conversation->messages->filter(function ($message) use ($conversation) {
                        return $message->created_at > $conversation->pivot->deleted_at;
                    })->values()
                );
            }
        });

        return response()->json($conversations);
    }


    public function store(StoreConversationRequest $request)
    {
        $user = $request->user();
        $attributes = $request->validated();
            $conversation = DB::transaction(function () use ($request, $attributes) {
                $user = $request->user();

                $newConversation = Conversation::create();
                // Automatically include the conversation creator in the participant list
                // This ensures the creator is always part of their own conversations
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

        $user = request()->user();

        // Get the deletion timestamp for this user if it exists
        $userPivot = $conversation->users()
            ->where('user_id', $user->id)
            ->first()
            ->pivot;

        $deletedAt = $userPivot ? $userPivot->deleted_at : null;

        $conversation->load(['users', 'messages' => function ($query) use ($user, $deletedAt) {
            // Hide individual messages deleted by user
            $query->whereDoesntHave('hiddenByUsers', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });

            // Hide messages created before the chat was "cleared"
            if ($deletedAt) {
                $query->where('created_at', '>', $deletedAt);
            }

            $query->with('user', 'readByUsers');
        }]);

        return response()->json($conversation);
    }


    public function destroy(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        // Soft delete the conversation for THIS user only (Clear History)
        $conversation->users()->updateExistingPivot(
            request()->user()->id,
            ['deleted_at' => now()]
        );

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
