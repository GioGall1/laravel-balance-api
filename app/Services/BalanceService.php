<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Models\Balance;
use App\Models\Transaction;
use Illuminate\Support\Facades\DB;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use DomainException;

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

    /** Списание средств пользователя */
    public function withdraw(int $userId, string $amount, ?string $comment = null): string
    {
        return DB::transaction(function () use ($userId, $amount, $comment) {
             // блокируем баланс пользователя
            $balanceRow = Balance::where('user_id', $userId)->lockForUpdate()->first();
            $currentStr = $balanceRow?->balance ?? '0.00';

            // Проверка «не уходим в минус»
            $current = (float) $currentStr;
            $delta   = (float) $amount;

            if ($current < $delta) {
                throw new DomainException('Недостаточно средств');
            }

            $new = number_format($current - $delta, 2, '.', '');

            if ($balanceRow) {
                $balanceRow->update(['balance' => $new]);
            } else {
                Balance::create(['user_id' => $userId, 'balance' => $new]);
            }

            Transaction::create([
                'user_id'        => $userId,
                'type'           => Transaction::TYPE_WITHDRAW,
                'amount'         => number_format((float)$amount, 2, '.', ''),
                'comment'        => $comment,
                'related_user_id'=> null,
            ]);

            return $new;
        });
    }

    public function transfer(int $fromUserId, int $toUserId, string $amount, ?string $comment = null): string
    {
        // нормализуем сумму до строки с 2 знаками
        $amount = number_format((float)$amount, 2, '.', '');

        if (bccomp($amount, '0.00', 2) <= 0) {
            throw new \InvalidArgumentException('Amount must be greater than 0');
        }

        return DB::transaction(function () use ($fromUserId, $toUserId, $amount, $comment): string {
            // детерминированно блокируем пользователей по возрастанию id (анти-дедлок)
            $firstId  = min($fromUserId, $toUserId);
            $secondId = max($fromUserId, $toUserId);

            $users = User::whereIn('id', [$firstId, $secondId])->lockForUpdate()->get()->keyBy('id');

            $fromUser = $users->get($fromUserId);
            $toUser   = $users->get($toUserId);

            if (!$fromUser || !$toUser) {
                throw new NotFoundHttpException('User not found');
            }

            // блокируем/берём записи балансов
            $fromBalance = Balance::where('user_id', $fromUserId)->lockForUpdate()->first();
            $toBalance   = Balance::where('user_id', $toUserId)->lockForUpdate()->first();

            // если у получателя нет записи — создадим (по ТЗ записей может не быть до первого депозита, но для transfer ok создать)
            if (!$toBalance) {
                $toBalance = Balance::create(['user_id' => $toUserId, 'balance' => '0.00']);
            }
            // у отправителя тоже может не быть записи — тогда баланс считаем 0.00
            $fromCurrent = $fromBalance?->balance ?? '0.00';

            // проверка достаточности средств (не уходим в минус)
            if (bccomp($fromCurrent, $amount, 2) < 0) {
                throw new DomainException('Недостаточно средств для перевода');
            }

            // считаем новые значения
            $newFrom = bcsub($fromCurrent, $amount, 2);
            $newTo   = bcadd($toBalance->balance, $amount, 2);

            // апдейты/создание записи баланса отправителя
            if ($fromBalance) {
                $fromBalance->update(['balance' => $newFrom]);
            } else {
                $fromBalance = Balance::create(['user_id' => $fromUserId, 'balance' => $newFrom]);
            }
            $toBalance->update(['balance' => $newTo]);

            // лог транзакций (две записи)
            Transaction::create([
                'user_id'         => $fromUserId,
                'type'            => Transaction::TYPE_TRANSFER_OUT,
                'amount'          => $amount,
                'comment'         => $comment,
                'related_user_id' => $toUserId,
            ]);

            Transaction::create([
                'user_id'         => $toUserId,
                'type'            => Transaction::TYPE_TRANSFER_IN,
                'amount'          => $amount,
                'comment'         => $comment,
                'related_user_id' => $fromUserId,
            ]);

            // возвращаем новый баланс отправителя (в едином стиле с /withdraw)
            return $newFrom;
        });
    }
}