<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class CommentNotification extends Notification
{
    use Queueable;

    protected $commenter;
    protected $post;
    protected $commentPreview;

    public function __construct($commenter, $post, $commentContent)
    {
        $this->commenter = $commenter;
        $this->post = $post;
        $this->commentPreview = strlen($commentContent) > 50 
            ? substr($commentContent, 0, 50) . '...' 
            : $commentContent;
    }

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'comment',
            'commenter_id' => $this->commenter->id,
            'commenter_name' => $this->commenter->name,
            'commenter_avatar' => $this->commenter->avatar_url,
            'post_id' => $this->post->id,
            'comment_preview' => $this->commentPreview,
            'message' => "{$this->commenter->name} commented on your post",
        ];
    }
}
