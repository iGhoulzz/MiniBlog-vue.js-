<?php

namespace App\Services;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Events\ConversationCreated;
use App\Events\MessagesRead;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class ConversationService
{
    /**
     * Get all conversations for a user, filtering out deleted messages.
     */
    public function getConversationsForUser(User $user): Collection
    {
        $conversations = $user
            ->conversations()
            ->where(function ($q) {
                $q->whereNull('conversation_user.deleted_at')
                  ->orWhereColumn('conversations.updated_at', '>', 'conversation_user.deleted_at');
            })
            ->with(['users', 'messages' => function ($query) use ($user) {
                $query->visibleTo($user)->with('user', 'readByUsers');
            }])
            ->get();

        // Filter out messages sent before the user soft-deleted the conversation
        $conversations->each(function ($conversation) {
            $deletedAt = $conversation->pivot->deleted_at;

            if ($deletedAt) {
                $conversation->setRelation(
                    'messages',
                    $conversation->messages
                        ->filter(fn ($message) => $message->created_at > $deletedAt)
                        ->values()
                );
            }
        });

        return $conversations;
    }

    /**
     * Find an existing conversation between exactly the given participants,
     * or create a new one. Either way, attach the first message.
     */
    public function findOrCreateConversation(User $sender, array $userIds, string $content): Conversation
    {
        $participantIds = collect([$sender->id, ...$userIds])->unique()->sort()->values()->all();

        $existing = $this->findExactConversation($participantIds);

        if ($existing) {
            return $this->addMessageToExisting($existing, $sender, $content);
        }

        return $this->createNewConversation($sender, $participantIds, $content);
    }

    /**
     * Load a single conversation with messages visible to the given user.
     */
    public function loadConversationForUser(Conversation $conversation, User $user): Conversation
    {
        $deletedAt = $conversation->users()
            ->where('user_id', $user->id)
            ->first()
            ->pivot
            ->deleted_at;

        $conversation->load(['users', 'messages' => function ($query) use ($user, $deletedAt) {
            $query->visibleTo($user);

            if ($deletedAt) {
                $query->where('created_at', '>', $deletedAt);
            }

            $query->with('user', 'readByUsers');
        }]);

        return $conversation;
    }

    /**
     * Soft-delete (clear history) a conversation for a specific user.
     */
    public function clearHistoryForUser(Conversation $conversation, User $user): void
    {
        $conversation->users()->updateExistingPivot(
            $user->id,
            ['deleted_at' => now()]
        );
    }

    /**
     * Mark all unread messages in a conversation as read by the given user.
     *
     * @return array{message_ids: array, read_at: string|null}
     */
    public function markAsRead(Conversation $conversation, User $user): array
    {
        $allMessageIds = $conversation->messages()->pluck('id');

        if ($allMessageIds->isEmpty()) {
            return ['message_ids' => [], 'read_at' => null];
        }

        $alreadyReadIds = $user->readMessages()
            ->whereIn('message_id', $allMessageIds)
            ->pluck('message_id');

        $unreadIds = $allMessageIds->diff($alreadyReadIds);
        $now = now();

        if ($unreadIds->isNotEmpty()) {
            $user->readMessages()->attach($unreadIds);
            MessagesRead::dispatch($conversation, $user, $unreadIds->toArray(), $now);
        }

        return [
            'message_ids' => $unreadIds->values()->all(),
            'read_at'     => $now->toISOString(),
        ];
    }

    // ─── Private helpers ─────────────────────────────────────────────

    /**
     * Find a conversation that has exactly the given set of participant IDs.
     */
    private function findExactConversation(array $participantIds): ?Conversation
    {
        return Conversation::whereHas('users', function ($q) use ($participantIds) {
                $q->whereIn('users.id', $participantIds);
            })
            ->withCount('users')
            ->get()
            ->filter(function ($conversation) use ($participantIds) {
                return $conversation->users_count === count($participantIds)
                    && $conversation->users->pluck('id')->sort()->values()->all() === $participantIds;
            })
            ->first();
    }

    /**
     * Re-activate an existing conversation and append a new message.
     */
    private function addMessageToExisting(Conversation $conversation, User $sender, string $content): Conversation
    {
        // Un-delete the conversation for the sender
        $conversation->users()->updateExistingPivot($sender->id, ['deleted_at' => null]);
        $conversation->touch();

        Message::create([
            'content'         => $content,
            'conversation_id' => $conversation->id,
            'user_id'         => $sender->id,
        ]);

        return $conversation->load('users', 'messages.user', 'messages.readByUsers');
    }

    /**
     * Create a brand-new conversation with participants and an initial message,
     * then broadcast the event to every participant.
     */
    private function createNewConversation(User $sender, array $participantIds, string $content): Conversation
    {
        $conversation = DB::transaction(function () use ($sender, $participantIds, $content) {
            $newConversation = Conversation::create();
            $newConversation->users()->attach($participantIds);

            Message::create([
                'content'         => $content,
                'conversation_id' => $newConversation->id,
                'user_id'         => $sender->id,
            ]);

            return $newConversation->load('users', 'messages.user', 'messages.readByUsers');
        });

        // Broadcast to every participant
        foreach ($conversation->users as $participant) {
            event(new ConversationCreated($conversation, $participant));
        }

        return $conversation;
    }
}
