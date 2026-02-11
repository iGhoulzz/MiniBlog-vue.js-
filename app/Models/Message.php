<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

class Message extends Model
{
    protected $fillable = ['content', 'conversation_id', 'user_id'];

    protected $touches = ['conversation'];

    protected $appends = ['read_by'];

    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function hiddenByUsers()
    {
        return $this->belongsToMany(User::class, 'hidden_messages')
                    ->withTimestamps();
    }

    public function readByUsers()
    {
        return $this->belongsToMany(User::class, 'message_user')

                    ->withTimestamps();
    }

    public function scopeParticipant($query, $userId)
    {
        return $query->whereHas('conversation.users', function ($q) use ($userId) {
            $q->where('users.id', $userId);
        });
    }

    public function getReadByAttribute(): array
    {
        $this->loadMissing('readByUsers');

        return $this->readByUsers
            ->map(function ($user) {
                $pivot = $user->pivot;
                $readAtValue = $pivot->created_at ?? $pivot->updated_at;

                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'read_at' => $readAtValue
                        ? Carbon::parse($readAtValue)->toISOString()
                        : null,
                ];
            })
            ->values()
            ->toArray();
    }
}
