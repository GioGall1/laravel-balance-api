<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransferTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function transfers_between_users_and_logs_both_sides()
    {
        [$a, $b] = [User::factory()->create(), User::factory()->create()];
        $this->postJson('/api/deposit', ['user_id'=>$a->id,'amount'=>200.00])->assertOk();

        $this->postJson('/api/transfer', [
            'from_user_id'=>$a->id, 'to_user_id'=>$b->id, 'amount'=>150.00, 'comment'=>'Test'
        ])->assertOk();

        $this->assertDatabaseHas('balances', ['user_id'=>$a->id,'balance'=>'50.00']);
        $this->assertDatabaseHas('balances', ['user_id'=>$b->id,'balance'=>'150.00']);

        $this->assertDatabaseHas('transactions', [
            'user_id'=>$a->id,'type'=>'transfer_out','amount'=>'150.00','related_user_id'=>$b->id
        ]);
        $this->assertDatabaseHas('transactions', [
            'user_id'=>$b->id,'type'=>'transfer_in','amount'=>'150.00','related_user_id'=>$a->id
        ]);
    }
}