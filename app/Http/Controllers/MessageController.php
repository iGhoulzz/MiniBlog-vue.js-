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
        $message->load('user', 'readByUsers');
        broadcast(new MessageSent($message))->toOthers();
        return response()->json($message, 201);

    }

}
