<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * @throws \Exception
     */
    public function definition(): array
    {
        $count = (float)random_int(0, 100000);
        return [
            'type' => TransactionType::Add,
            'count' => $count,
            'info' => fn() => Balance::factory()->create([
                'balance' => $count,
            ])->toArray(),
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Transaction $transaction) {
            $transaction->users()->attach($transaction['info']['user_id']);
        });
    }
}
