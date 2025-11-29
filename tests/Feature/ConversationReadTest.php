<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationReadTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_mark_messages_as_read(): void
    {
        $reader = User::factory()->create();
        $sender = User::factory()->create();

        $conversation = Conversation::create();
        $conversation->users()->attach([$reader->id, $sender->id]);

        $message = Message::create([
            'content' => 'Hello there',
            'conversation_id' => $conversation->id,
            'user_id' => $sender->id,
        ]);

        Sanctum::actingAs($reader);

        $response = $this->postJson("/api/conversations/{$conversation->id}/read");

        $response
            ->assertOk()
            ->assertJsonFragment([
                'message_ids' => [$message->id],
            ]);

        $this->assertDatabaseHas('message_user', [
            'message_id' => $message->id,
            'user_id' => $reader->id,
        ]);
    }
}
