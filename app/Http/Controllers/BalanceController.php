<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\DepositRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use Throwable;

class BalanceController extends Controller
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Получить баланс пользователя по его ID.
     *
     * @param int $userId
     * @return JsonResponse
     */
    public function show(int $userId): JsonResponse
    {
        try {
            $balance = $this->balanceService->getBalance($userId);

            return response()->json([
                'user_id' => $userId,
                'balance' => (float)$balance
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'error' => $e->getMessage(),
            ], 404);
        }
    }

    /**
     * Начисление средств пользователю
     */
    public function deposit(DepositRequest $request): JsonResponse
    {
        try {
            $userId  = (int) $request->input('user_id');
            $amount  = (float) $request->input('amount');
            $comment = $request->input('comment');

            $newBalance = $this->balanceService->deposit($userId, $amount, $comment);

            return response()->json([
                'user_id' => $userId,
                'balance' => (float) $newBalance,
            ], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => 'User not found'], 404);
        } catch (InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}