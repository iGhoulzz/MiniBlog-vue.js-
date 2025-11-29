<?php

namespace App\Events;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public Conversation $conversation;
    public User $recipient;

    public function __construct(Conversation $conversation, User $recipient)
    {
        $this->conversation = $conversation->loadMissing('users', 'messages.user', 'messages.readByUsers');
        $this->recipient = $recipient;
    }

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('user.' . $this->recipient->id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'ConversationCreated';
    }

    public function broadcastWith(): array
    {
        return [
            'conversation' => $this->conversation->toArray(),
        ];
    }
}
