<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\PaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function __construct(protected PaymentService $paymentService) {}

    public function getBalance(User $user): JsonResponse
    {
        return response()->json([
            'user_id' => $user->id,
            'balance' => $user->balance,
            'updated_at' => $user->updated_at,
        ]);
    }

    public function initiate(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'sometimes|string|in:card,bank_transfer,crypto',
        ]);

        $payment = $this->paymentService->initializePayment(
            $user,
            (float) $validated['amount'],
            $validated['payment_method'] ?? 'card'
        );

        return response()->json([
            'payment_id' => $payment->id,
            'status' => $payment->status,
            'amount' => $payment->amount,
            'reference_id' => $payment->reference_id,
            'message' => 'Payment initialized successfully',
        ], 201);
    }

    public function quickPay(Request $request, User $user): JsonResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'sometimes|string|in:card,bank_transfer,crypto',
        ]);

        $payment = $this->paymentService->initializePayment(
            $user,
            (float) $validated['amount'],
            $validated['payment_method'] ?? 'card'
        );

        $processed = $this->paymentService->processPayment($payment);

        return response()->json([
            'payment_id' => $processed->id,
            'status' => $processed->status,
            'amount' => $processed->amount,
            'reference_id' => $payment->reference_id,
            'user_balance' => $user->fresh()->balance,
            'timestamp' => now()->toIso8601String(),
        ], 201);
    }

    public function process(User $user, $paymentId): JsonResponse
    {
        $payment = $user->payments()->findOrFail($paymentId);
        $processed = $this->paymentService->processPayment($payment);

        return response()->json([
            'payment_id' => $processed->id,
            'status' => $processed->status,
            'amount' => $processed->amount,
            'user_balance' => $user->fresh()->balance,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    public function show(User $user, $paymentId): JsonResponse
    {
        $payment = $user->payments()->findOrFail($paymentId);

        return response()->json($this->paymentService->getPaymentStatus($payment));
    }

    public function history(User $user): JsonResponse
    {
        $payments = $user->payments()
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get()
            ->map(fn($p) => $this->paymentService->getPaymentStatus($p));

        return response()->json([
            'user_id' => $user->id,
            'payments' => $payments,
            'total' => $payments->count(),
        ]);
    }

    public function refund(User $user, $paymentId): JsonResponse
    {
        $payment = $user->payments()->findOrFail($paymentId);
        $refunded = $this->paymentService->refundPayment($payment);

        return response()->json([
            'payment_id' => $refunded->id,
            'status' => $refunded->status,
            'amount' => $refunded->amount,
            'user_balance' => $user->fresh()->balance,
            'message' => 'Payment refunded successfully',
        ]);
    }
}
