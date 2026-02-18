<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreMessageRequest;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Events\MessageSent;

class MessageController extends Controller
{
    use AuthorizesRequests;

    public function store (StoreMessageRequest $request, Conversation $conversation)
    {
        $this->authorize ('view', $conversation);
        $attributes = $request->validated();
        $message = Message::create ([
            'content' => $attributes['content'],
            'conversation_id' => $conversation->id,
            'user_id' => $request->user()->id,
        ]);
        $message->load(['user.media', 'readByUsers']);
        broadcast(new MessageSent($message))->toOthers();
        return response()->json($message, 201);

    }

    public function destroy(Message $message)
    {
        $this->authorize('view', $message->conversation);

        // Don't actually delete the record, just hide it for this user
        // We use 'syncWithoutDetaching' to add it to the hidden list
        // which prevents duplicates if they try to delete it twice
        request()->user()->hiddenMessages()->syncWithoutDetaching([$message->id]);

        return response()->json(null, 204);
    }

}
