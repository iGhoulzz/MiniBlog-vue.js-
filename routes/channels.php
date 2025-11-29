<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('conversation.{conversation}', function (User $user, Conversation $conversation) {
    return $conversation
        ->users()
        ->whereKey($user->id)
        ->exists();
});

Broadcast::channel('user.{user}', function (User $current, User $target) {
    return $current->id === $target->id;
});

