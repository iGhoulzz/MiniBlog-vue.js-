<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Conversation;
use App\Services\ConversationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Http\Requests\StoreConversationRequest;

class ConversationController extends Controller
{
    use AuthorizesRequests;

    public function __construct(
        private readonly ConversationService $conversationService
    ) {}

    /**
     * List all conversations for the authenticated user.
     */
    public function index(Request $request)
    {
        $conversations = $this->conversationService
            ->getConversationsForUser($request->user());

        return response()->json($conversations);
    }

    /**
     * Create (or re-activate) a conversation and send the first message.
     */
    public function store(StoreConversationRequest $request)
    {
        $attributes = $request->validated();

        $conversation = $this->conversationService->findOrCreateConversation(
            $request->user(),
            $attributes['user_ids'],
            $attributes['content']
        );

        // 200 for re-activated existing conversation, 201 for brand-new
        $status = $conversation->wasRecentlyCreated ? 201 : 200;

        return response()->json($conversation, $status);
    }

    /**
     * Show a single conversation with its visible messages.
     */
    public function show(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $user = request()->user();

        $conversation = $this->conversationService
            ->loadConversationForUser($conversation, $user);

        return response()->json($conversation);
    }

    /**
     * Soft-delete (clear history) for the authenticated user only.
     */
    public function destroy(Conversation $conversation)
    {
        $this->authorize('view', $conversation);

        $this->conversationService
            ->clearHistoryForUser($conversation, request()->user());

        return response()->json(null, 204);
    }

    /**
     * Mark all messages in a conversation as read.
     */
    public function markAsRead(Conversation $conversation, Request $request)
    {
        $this->authorize('view', $conversation);

        $result = $this->conversationService
            ->markAsRead($conversation, $request->user());

        return response()->json($result);
    }
}
