<?php

namespace Database\Factories;

use App\Models\Payment;
use Illuminate\Database\Eloquent\Factories\Factory;

class PaymentFactory extends Factory
{
    protected $model = Payment::class;

    public function definition(): array
    {
        return [
            'amount' => $this->faker->numberBetween(10, 1000),
            'status' => $this->faker->randomElement(['pending', 'processing', 'success', 'failed']),
            'payment_method' => $this->faker->randomElement(['card', 'bank_transfer', 'crypto']),
            'reference_id' => 'REF-' . $this->faker->unique()->uuid(),
        ];
    }
}
