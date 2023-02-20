<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Enums\TransactionType;
use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\CurrencyConverterService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class BalanceTest extends TestCase
{
    use RefreshDatabase;

    private $user1;
    private $user2;
    private $user2Balance;

    protected function setUp(): void
    {
        parent::setUp();

        DB::beginTransaction();

        $this->user1 = User::factory()->create();
        $this->user2 = User::factory()->create();

        $this->user2Balance = Balance::factory()
            ->for($this->user2)
            ->create(['balance' => 100]);

        DB::commit();
    }

    public function testAddForUser1(): void
    {
        $route = route('balance.add', $this->user1);

        $body = [
            'user_id' => $this->user1->id,
            'count' => "130.05",
        ];

        $response = $this->postJson($route, $body);

        $response->assertCreated();

        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user1->id,
            'balance' => 130.05,
        ]);

        $balance = Balance::whereUserId($this->user1->id)->first();
        $balance['balance'] = (float)$balance['balance'];

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::Add,
            'count' => $body['count'],
            'info' => $balance->toJson(),
            'created_at' => now(),
        ]);

        $this->assertDatabaseHas('transaction_user', [
            'user_id' => $this->user1->id,
        ]);
    }

    public function testAddForUser2(): void
    {
        $route = route('balance.add', $this->user2);

        $count = 130.05;

        $body = [
            'user_id' => $this->user2->id,
            'count' => $count,
        ];

        $response = $this->postJson($route, $body);

        $response->assertOk();

        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user2->id,
            'balance' => $count + $this->user2Balance->balance,
        ]);
    }

    public function testWriteOffForUser1(): void
    {
        $route = route('balance.write_off', $this->user1);

        $body = [
            'user_id' => $this->user1->id,
            'count' => 130.05,
        ];

        $response = $this->postJson($route, $body);

        $response->assertStatus(400);
    }

    public function writeOffUser2Provider(): array
    {
        return [
            [50.0, 200],
            [100.0, 200],
            [150.0, 400],
        ];
    }

    /**
     * @dataProvider writeOffUser2Provider
     */
    public function testWriteOffForUser2(float $count, int $statusCode): void
    {
        $route = route('balance.write_off', $this->user2);

        $body = [
            'user_id' => $this->user2->id,
            'count' => $count,
        ];

        $response = $this->postJson($route, $body);

        $response->assertStatus($statusCode);

        $response->dump();

        if ($statusCode !== 200) {
            return;
        }
        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user2->id,
            'balance' => $this->user2Balance->balance - $count,
        ]);

        $balance = Balance::whereUserId($this->user2->id)->first();
        $balance['balance'] = (float)$balance['balance'];

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::WriteOff,
            'count' => $body['count'],
            'info' => $balance->toJson(),
            'created_at' => now(),
        ]);
    }

    public function testShowForUser1(): void
    {
        $route = route('balance.show', $this->user1);

        $response = $this->getJson($route);

        $response->assertOk();

        $response->assertJsonFragment([
            'user_id' => $this->user1->id,
            'balance' => 0,
        ]);
    }

    public function testShowForUser2(): void
    {
        $route = route('balance.show', $this->user2);

        $response = $this->getJson($route);

        $response->assertOk();

        $response->assertJsonFragment([
            'user_id' => $this->user2->id,
            'balance' => (string)$this->user2Balance->balance,
        ]);
    }

    public function sendToUserProvider(): array
    {
        return [
            [50.0, 200],
            [100.0, 200],
            [150.0, 400],
        ];
    }

    /**
     * @dataProvider sendToUserProvider
     */
    public function testSendToUser($count, $statusCode): void
    {
        $route = route('balance.send_to', [
            'sender' => $this->user2,
            'recipient' => $this->user1,
        ]);

        $body = [
            'count' => $count,
        ];

        $response = $this->postJson($route, $body);

        $response->assertStatus($statusCode);

        $response->dump();
        if ($statusCode !== 200) {
            return;
        }
        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user1->id,
            'balance' => $count,
        ]);

        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user2->id,
            'balance' => $this->user2Balance->balance - $count,
        ]);

        $this->assertDatabaseHas('transactions', [
            'type' => TransactionType::SendTo,
            'count' => $count, // $body['count']
            'info->sender_balance->user_id' => $this->user2->id,
            'info->recipient_balance->user_id' => $this->user1->id,
            'created_at' => now(),
        ]);

        $transactionId = Transaction::first()->id;
        $this->assertDatabaseHas('transaction_user', [
            'transaction_id' => $transactionId,
            'user_id' => $this->user1->id,
        ]);

        $this->assertDatabaseHas('transaction_user', [
            'transaction_id' => $transactionId,
            'user_id' => $this->user2->id,
        ]);
    }

    public
    function testShowCurrency(): void
    {
        $currency = 'USD';
        $rate = 75.0;

        $this->instance(
            CurrencyConverterService::class,
            Mockery::mock(
                CurrencyConverterService::class,
                function (MockInterface $mock) use ($currency) {
                    $rate = 75.0;
                    $mock->shouldReceive('convert')
                        ->once()
                        ->andReturn([
                            'from_currency' => BalanceService::APP_CURRENCY,
                            'to_currency' => $currency,
                            'from_amount' => $this->user2Balance->balance,
                            'to_amount' => $this->user2Balance->balance / $rate,
                            'rate' => $rate,
                        ]);
                })
        );

        $route = route('balance.show', [
            'user' => $this->user2,
            'currency' => $currency,
        ]);

        $response = $this->getJson($route);
        $response->assertOk();

        $response->assertJsonFragment([
            'user_id' => $this->user2->id,
            'balance' => $this->user2Balance->balance / $rate,
            'currency' => $currency,
        ]);
    }
}
