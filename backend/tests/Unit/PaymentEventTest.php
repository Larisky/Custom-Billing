<?php

namespace Tests\Unit;

use App\Events\PaymentStatusChanged;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Support\Facades\Event;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PaymentEventTest extends TestCase
{
    protected PaymentService $paymentService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->paymentService = app(PaymentService::class);
    }

    #[Test]
    public function it_dispatches_event_when_payment_status_changes_to_success()
    {
        Event::fake();

        $user = User::factory()->create();
        $payment = $user->payments()->create([
            'amount' => 100.00,
            'status' => PaymentService::STATUS_PENDING,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-001',
        ]);

        $this->paymentService->markPaymentSuccess($payment);

        Event::assertDispatched(PaymentStatusChanged::class, function ($event) use ($payment) {
            return $event->payment->id === $payment->id &&
                   $event->newStatus === PaymentService::STATUS_SUCCESS;
        });
    }

    #[Test]
    public function it_dispatches_event_when_payment_status_changes_to_failed()
    {
        Event::fake();

        $user = User::factory()->create();
        $payment = $user->payments()->create([
            'amount' => 100.00,
            'status' => PaymentService::STATUS_PROCESSING,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-002',
        ]);

        $this->paymentService->markPaymentFailed($payment);

        Event::assertDispatched(PaymentStatusChanged::class, function ($event) use ($payment) {
            return $event->payment->id === $payment->id &&
                   $event->newStatus === PaymentService::STATUS_FAILED;
        });
    }

    #[Test]
    public function it_includes_correct_data_in_event()
    {
        Event::fake();

        $user = User::factory()->create();
        $payment = $user->payments()->create([
            'amount' => 50.00,
            'status' => PaymentService::STATUS_PENDING,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-003',
        ]);

        $this->paymentService->markPaymentSuccess($payment);

        Event::assertDispatched(PaymentStatusChanged::class, function ($event) {
            $data = $event->broadcastWith();

            return $data['payment_id'] === $event->payment->id &&
                   $data['user_id'] === $event->payment->user_id &&
                   $data['amount'] == 50.00 &&
                   $data['new_status'] === PaymentService::STATUS_SUCCESS;
        });
    }

    #[Test]
    public function event_broadcasts_on_private_channel()
    {
        Event::fake();

        $user = User::factory()->create();
        $payment = $user->payments()->create([
            'amount' => 100.00,
            'status' => PaymentService::STATUS_PENDING,
            'payment_method' => 'card',
            'reference_id' => 'REF-TEST-004',
        ]);

        $this->paymentService->markPaymentSuccess($payment);

        Event::assertDispatched(PaymentStatusChanged::class, function ($event) use ($user) {
            $channels = $event->broadcastOn();
            $channelName = $channels[0]->name;

            return str_contains($channelName, 'payment.user.' . $user->id);
        });
    }
}
