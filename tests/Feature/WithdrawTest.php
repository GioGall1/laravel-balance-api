<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use PHPUnit\Framework\Attributes\Test;

class WithdrawTest extends TestCase
{
    use RefreshDatabase;

    #[Test]
    public function withdraws_money_when_balance_is_enough()
    {
        $u = User::factory()->create();

        $this->postJson('/api/deposit', ['user_id'=>$u->id,'amount'=>100.00])->assertOk();
        $this->postJson('/api/withdraw', ['user_id'=>$u->id,'amount'=>40.00])->assertOk();

        $this->assertDatabaseHas('balances', ['user_id'=>$u->id,'balance'=>'60.00']);
        $this->assertDatabaseHas('transactions', ['user_id'=>$u->id,'type'=>'withdraw','amount'=>'40.00']);
    }

    #[Test]
    public function returns_409_if_insufficient_funds()
    {
        $u = User::factory()->create();
        $this->postJson('/api/withdraw', ['user_id'=>$u->id,'amount'=>1.00])
             ->assertStatus(409);
    }
}
