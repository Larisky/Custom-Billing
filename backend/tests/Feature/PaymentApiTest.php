<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Tests\TestCase;

class PaymentApiTest extends TestCase
{
    public function it_can_get_user_balance()
    {
        $user = User::factory()->create(['balance' => 100.00]);

        $this->getJson("/api/users/{$user->id}/balance")
             ->assertStatus(200)
             ->assertJson(['user_id' => $user->id, 'balance' => 100.00]);
    }

    public function it_can_initiate_a_payment()
    {
        $user = User::factory()->create();

        $this->postJson("/api/users/{$user->id}/payments", [
            'amount' => 50.00,
            'payment_method' => 'card',
        ])
        ->assertStatus(201)
        ->assertJsonStructure(['payment_id', 'status', 'amount', 'reference_id', 'message']);

        $this->assertDatabaseHas('payments', [
            'user_id' => $user->id,
            'amount' => 50.00,
            'status' => PaymentService::STATUS_PENDING,
        ]);
    }

    public function it_can_process_a_payment()
    {
        $user = User::factory()->create(['balance' => 0]);
        $payment = $user->payments()->create([
            'amount' => 75.00,
            'status' => PaymentService::STATUS_PENDING,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-005',
        ]);

        $this->postJson("/api/users/{$user->id}/payments/{$payment->id}/process")
             ->assertStatus(200)
             ->assertJsonStructure(['payment_id', 'status', 'amount', 'user_balance', 'timestamp']);

        $payment->refresh();
        $this->assertContains($payment->status, [PaymentService::STATUS_SUCCESS, PaymentService::STATUS_FAILED]);
    }

    public function it_can_get_payment_history()
    {
        $user = User::factory()->create();
        Payment::factory(5)->create(['user_id' => $user->id]);

        $this->getJson("/api/users/{$user->id}/payments")
             ->assertStatus(200)
             ->assertJsonStructure(['user_id', 'payments', 'total'])
             ->assertJson(['user_id' => $user->id, 'total' => 5]);
    }

    public function it_can_get_payment_details()
    {
        $user = User::factory()->create();
        $payment = $user->payments()->create([
            'amount' => 100.00,
            'status' => PaymentService::STATUS_SUCCESS,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-006',
        ]);

        $this->getJson("/api/users/{$user->id}/payments/{$payment->id}")
             ->assertStatus(200)
             ->assertJson(['id' => $payment->id, 'amount' => 100.00, 'status' => PaymentService::STATUS_SUCCESS]);
    }

    public function it_can_refund_a_successful_payment()
    {
        $user = User::factory()->create(['balance' => 100.00]);
        $payment = $user->payments()->create([
            'amount' => 50.00,
            'status' => PaymentService::STATUS_SUCCESS,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-007',
        ]);

        $this->postJson("/api/users/{$user->id}/payments/{$payment->id}/refund")
             ->assertStatus(200)
             ->assertJson(['status' => PaymentService::STATUS_REFUNDED]);

        $user->refresh();
        $this->assertEquals(50.00, $user->balance);
    }

    public function it_validates_payment_amount()
    {
        $user = User::factory()->create();

        $this->postJson("/api/users/{$user->id}/payments", ['amount' => -10.00])
             ->assertStatus(422);
    }

    public function it_gets_or_creates_test_user()
    {
        $this->getJson('/api/users/test')
             ->assertStatus(200)
             ->assertJsonStructure(['id', 'name', 'email', 'balance']);
    }
}
