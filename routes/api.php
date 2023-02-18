<?php

use App\Http\Controllers\BalanceController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::post('/users/{user}/balance/add', [BalanceController::class, 'add'])
    ->name('balance.add');
Route::post('/users/{user}/balance/write_off', [BalanceController::class, 'writeOff'])
    ->name('balance.write_off');
Route::get('/users/{user}/balance', [BalanceController::class, 'show'])
    ->name('balance.show');
