<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use App\Traits\HasReactionEmojis;

class ReactionNotification extends Notification
{
    use Queueable, HasReactionEmojis;

    protected $reactor;
    protected $reactionType;
    protected $reactionableType;
    protected $reactionableId;

    public function __construct($reactor, $reactionType, $reactionableType, $reactionableId)
    {
        $this->reactor = $reactor;
        $this->reactionType = $reactionType;
        $this->reactionableType = $reactionableType;
        $this->reactionableId = $reactionableId;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'reaction',
            'reactor_id' => $this->reactor->id,
            'reactor_name' => $this->reactor->name,
            'reactor_avatar' => $this->reactor->avatar_url,
            'reaction_type' => $this->reactionType,
            'reaction_emoji' => self::getReactionEmoji($this->reactionType),
            'reactionable_type' => $this->reactionableType,
            'reactionable_id' => $this->reactionableId,
            'message' => "{$this->reactor->name} reacted to your {$this->reactionableType}",
        ];
    }
}
