<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BalanceController;

Route::get('/user', function (Request $request) {
    return $request->user();
});

Route::get('/balance/{userId}', [BalanceController::class, 'show']);
Route::post('/deposit', [BalanceController::class, 'deposit']);