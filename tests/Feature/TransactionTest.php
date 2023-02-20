<?php

namespace Tests\Feature;

use App\Models\Balance;
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Arr;
use Illuminate\Testing\Fluent\AssertableJson;
use Tests\TestCase;

class TransactionTest extends TestCase
{
    use RefreshDatabase;

    private $transactions;
    private $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $balance = Balance::factory()->for($this->user)->create();
        $this->transactions = Transaction::factory(['info' => $balance->toArray()])
            ->count(20)
            ->create();
    }

    public function testIndex(): void
    {
        $route = route('transactions.index', $this->user->id);
        $response = $this->get($route);

        $response->assertOk();

        $perPage = 15;
        $response->assertJsonCount($perPage, 'data');
        $response->assertJsonFragment(['total' => $this->transactions->count()]);
    }

    public function sortProvider(): array
    {
        return [
            ['count', 'count', 'asc'],
            ['-count', 'count', 'desc'],
            ['created_at', 'created_at', 'asc'],
            ['-created_at', 'created_at', 'desc'],
        ];
    }

    /**
     * @dataProvider sortProvider
     */
    public function testIndexWithSortByCount(string $sortQueryValue, string $column, string $direction): void
    {
        $route = route('transactions.index', [
            'user' => $this->user->id,
            'sort' => $sortQueryValue,
        ]);
        $response = $this->get($route);

        $response->assertOk();

        $perPage = 15;
        $response->assertJsonCount($perPage, 'data');
        $response->assertJson(function (AssertableJson $json) use ($column, $direction) {
           $data = $json->toArray();

           $sortedCounts = $this->transactions->pluck($column);
           $sortedCounts = $direction === 'asc' ? $sortedCounts->sort() : $sortedCounts->sortDesc();

           if ($column === 'created_at') {
               $sortedCounts = $sortedCounts->map(fn(Carbon $item) => $item->toISOString());
           }

           $sortedCounts = $sortedCounts
               ->values()
               ->slice(0, 15);

           $items = array_values(Arr::pluck($data['data'], $column));

           $this->assertEquals($sortedCounts->toArray(), $items);

           $json->etc();
        });
    }
}
