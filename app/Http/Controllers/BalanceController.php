<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreBalanceRequest;
use App\Http\Requests\UpdateBalanceRequest;
use App\Models\Balance;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class BalanceController extends Controller
{
    public function add(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
            'count' => 'required|numeric|gt:0',
        ]);

        $user = User::findOrFail($request->get('user_id'));
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        $balance->balance += $request->get('count');

        $balance->save();

        return response()->json($balance);
    }
}
