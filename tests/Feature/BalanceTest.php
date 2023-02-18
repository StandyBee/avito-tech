<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
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
        $route = route('balance.add');

        $body = [
            'user_id' => $this->user1->id,
            'count' => 130.05,
        ];

        $response = $this->postJson($route, $body);

        $response->assertOk();

        $this->assertDatabaseHas('balances', [
            'user_id' => $this->user1->id,
            'balance' => 130.05,
        ]);
    }

    public function testAddForUser2(): void
    {
        $route = route('balance.add');

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

}
