<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BroadcastAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_member_can_authorize_private_conversation_channel(): void
    {
        $user = User::factory()->create();
        $conversation = new Conversation();
        $conversation->save();
        $conversation->users()->attach($user->id);

        $token = $user->createToken('test')->plainTextToken;

        $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.' . $conversation->id,
                'socket_id' => '1234.5678',
            ])
            ->assertOk()
            ->assertJsonStructure(['auth']);
    }

    public function test_non_member_is_rejected_from_private_conversation_channel(): void
    {
        $conversation = new Conversation();
        $conversation->save();

        $member = User::factory()->create();
        $conversation->users()->attach($member->id);

        $stranger = User::factory()->create();
        $token = $stranger->createToken('test')->plainTextToken;

        $this
            ->withHeader('Authorization', 'Bearer ' . $token)
            ->postJson('/broadcasting/auth', [
                'channel_name' => 'private-conversation.' . $conversation->id,
                'socket_id' => '9999.0000',
            ])
            ->assertForbidden();
    }
}
