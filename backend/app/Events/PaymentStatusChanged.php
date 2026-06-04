<?php

namespace App\Events;

use App\Models\Payment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PaymentStatusChanged implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public Payment $payment,
        public string $previousStatus,
        public string $newStatus,
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('payment.user.' . $this->payment->user_id),
        ];
    }

    public function broadcastAs(): string
    {
        return 'payment.status.changed';
    }

    public function broadcastWith(): array
    {
        return [
            'payment_id' => $this->payment->id,
            'user_id' => $this->payment->user_id,
            'amount' => $this->payment->amount,
            'previous_status' => $this->previousStatus,
            'new_status' => $this->newStatus,
            'timestamp' => now()->toIso8601String(),
            'payment_method' => $this->payment->payment_method,
            'reference_id' => $this->payment->reference_id,
        ];
    }
}
