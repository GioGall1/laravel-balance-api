<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class BalanceService
{
    /**
     * Вернёт баланс пользователя в виде строки.
     * Бросит 404, если пользователь не найден.
     */
    public function getBalance(int $userId): string
    {
        $user = User::with('balance')->find($userId);

        if ($user === null) {
            throw new NotFoundHttpException('User not found');
        }

        $raw = $user->balance?->balance ?? '0.00';

        return number_format((float) $raw, 2, '.', '');
    }

    /** Начисление средств пользователю */
    public function deposit(int $userId, float $amount, ?string $comment = null): string
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Amount must be greater than 0');
        }

        $amount = number_format($amount, 2, '.', '');

        return DB::transaction(function () use ($userId, $amount, $comment): string {
            $user = User::lockForUpdate()->find($userId);

            if (!$user) {
                throw new NotFoundHttpException('User not found');
            }

            $balance = Balance::where('user_id', $userId)->lockForUpdate()->first();

            if (!$balance) {
                $balance = Balance::create([
                    'user_id' => $userId,
                    'balance' => '0.00',
                ]);
            }

            $newBalance = bcadd($balance->balance, $amount, 2);

            $balance->update(['balance' => $newBalance]);

            Transaction::create([
                'user_id' => $userId,
                'type' => 'deposit',
                'amount' => $amount,
                'comment' => $comment,
            ]);

            return $newBalance;
        });
    }
}