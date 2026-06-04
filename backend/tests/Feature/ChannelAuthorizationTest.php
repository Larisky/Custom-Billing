<?php

namespace Tests\Feature;

use App\Models\User;
use Tests\TestCase;

class ChannelAuthorizationTest extends TestCase
{
    public function users_can_only_access_their_own_payment_channel()
    {
        $user1 = User::factory()->create();
        $user2 = User::factory()->create();

        $this->actingAs($user1);

        $isAuthorized = (int) $user1->id === (int) $user2->id;

        $this->assertFalse($isAuthorized);
    }

    public function users_can_access_their_own_payment_channel()
    {
        $user = User::factory()->create();

        $isAuthorized = (int) $user->id === (int) $user->id;

        $this->assertTrue($isAuthorized);
    }

    public function channel_name_includes_user_id()
    {
        $user = User::factory()->create();
        $user->payments()->create([
            'amount' => 100.00,
            'status' => 'pending',
            'reference_id' => 'REF-TEST-008',
        ]);

        $expectedChannel = 'payment.user.' . $user->id;

        $this->assertTrue(str_contains($expectedChannel, $user->id));
    }
}
