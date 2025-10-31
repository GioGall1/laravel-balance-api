<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class DepositTest extends TestCase
{
   use RefreshDatabase;

    #[Test]
    public function it_deposits_money_and_creates_balance_and_transaction()
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount'  => 100.00,
            'comment' => 'Пополнение через карту',
        ]);

        $response->assertStatus(200)
                 ->assertJson([
                     'user_id' => $user->id,
                     'balance' => 100.00,
                 ]);

        // баланс создан/обновлён
        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'balance' => '100.00',
        ]);

        $this->assertDatabaseHas('transactions', [
            'user_id' => $user->id,
            'type'    => 'deposit',
            'amount'  => '100.00',
            'comment' => 'Пополнение через карту',
        ]);
    }

    #[Test]
    public function it_accumulates_balance_on_multiple_deposits()
    {
        $user = User::factory()->create();

        $this->postJson('/api/deposit', [
            'user_id' => $user->id, 'amount' => 40.00,
        ])->assertStatus(200);

        $this->postJson('/api/deposit', [
            'user_id' => $user->id, 'amount' => 60.00,
        ])->assertStatus(200);

        $this->assertDatabaseHas('balances', [
            'user_id' => $user->id,
            'balance' => '100.00',
        ]);
    }

    #[Test]
    public function it_validates_amount_min_0_01()
    {
        $user = User::factory()->create();

        $this->postJson('/api/deposit', [
            'user_id' => $user->id,
            'amount'  => 0, // не проходит 'min:0.01'
        ])->assertStatus(422);
    }

    #[Test]
    public function it_validates_user_existence()
    {
        $this->postJson('/api/deposit', [
            'user_id' => 999999,
            'amount'  => 10.00,
        ])->assertStatus(422);
    }
}
