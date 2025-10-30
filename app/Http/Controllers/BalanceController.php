<?php

namespace App\Http\Controllers;

use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;
use App\Http\Requests\DepositRequest;
use App\Http\Requests\WithdrawRequest;
use App\Http\Requests\TransferRequest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use InvalidArgumentException;
use Throwable;
use DomainException;

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

    /**
     * Списание средств пользователя
     */
    public function withdraw(WithdrawRequest $request): JsonResponse
    {
        try {
        $userId  = (int) $request->integer('user_id');
        $amount  = (string) $request->input('amount');
        $comment = (string) $request->input('comment', '');

        $balance = $this->balanceService->withdraw($userId, $amount, $comment);

        return response()->json([
            'user_id' => $userId,
            'balance' => (float) $balance,
        ], 200);

        } catch (DomainException $e) {
            return response()->json(['error' => $e->getMessage()], 409); // недостаточно средств
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }

     /**
     * Перевод средств пользователю
     */
    public function transfer(TransferRequest $request): JsonResponse
    {
        try {
            $fromId  = (int) $request->integer('from_user_id');
            $toId    = (int) $request->integer('to_user_id');
            $amount  = (string) $request->input('amount');
            $comment = (string) $request->input('comment', '');

            $newFromBalance = $this->balanceService->transfer($fromId, $toId, $amount, $comment);

            return response()->json([
                'from_user_id' => $fromId,
                'to_user_id'   => $toId,
                'amount'       => (float) $amount,
                'balance'      => (float) $newFromBalance, // новый баланс отправителя
            ], 200);

        } catch (NotFoundHttpException $e) {
            return response()->json(['message' => $e->getMessage()], 404);

        } catch (DomainException $e) {
            // недостаточно средств или бизнес-конфликт
            return response()->json(['message' => $e->getMessage()], 409);

        } catch (\Throwable $e) {
            return response()->json(['message' => 'Internal server error'], 500);
        }
    }
}