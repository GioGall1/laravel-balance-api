<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
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
}