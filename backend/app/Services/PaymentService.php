<?php

namespace App\Services;

use App\Events\PaymentStatusChanged;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Str;

class PaymentService
{
    const STATUS_PENDING = 'pending';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';
    const STATUS_REFUNDED = 'refunded';

    public function initializePayment(User $user, float $amount, string $paymentMethod = 'card'): Payment
    {
        return Payment::create([
            'user_id' => $user->id,
            'amount' => $amount,
            'status' => self::STATUS_PENDING,
            'payment_method' => $paymentMethod,
            'reference_id' => 'REF-' . Str::uuid(),
        ]);
    }

    public function processPayment(Payment $payment): Payment
    {
        $payment->update(['status' => self::STATUS_PROCESSING]);

        rand(1, 100) > 30
            ? $this->markPaymentSuccess($payment)
            : $this->markPaymentFailed($payment);

        return $payment->fresh();
    }

    public function markPaymentSuccess(Payment $payment): Payment
    {
        $previousStatus = $payment->status;
        $payment->update(['status' => self::STATUS_SUCCESS]);
        $payment->user->increment('balance', $payment->amount);

        PaymentStatusChanged::dispatch($payment, $previousStatus, self::STATUS_SUCCESS);

        return $payment;
    }

    public function markPaymentFailed(Payment $payment): Payment
    {
        $previousStatus = $payment->status;
        $payment->update(['status' => self::STATUS_FAILED]);

        PaymentStatusChanged::dispatch($payment, $previousStatus, self::STATUS_FAILED);

        return $payment;
    }

    public function refundPayment(Payment $payment): Payment
    {
        if ($payment->status !== self::STATUS_SUCCESS) {
            throw new \Exception('Only successful payments can be refunded');
        }

        $previousStatus = $payment->status;
        $payment->update(['status' => self::STATUS_REFUNDED]);
        $payment->user->decrement('balance', $payment->amount);

        PaymentStatusChanged::dispatch($payment, $previousStatus, self::STATUS_REFUNDED);

        return $payment;
    }

    public function getPaymentStatus(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'amount' => $payment->amount,
            'status' => $payment->status,
            'payment_method' => $payment->payment_method,
            'reference_id' => $payment->reference_id,
            'created_at' => $payment->created_at,
            'updated_at' => $payment->updated_at,
        ];
    }
}
