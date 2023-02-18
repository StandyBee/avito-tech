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
    public function add(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer',
        ]);

        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        $balance->balance += $request->get('count');

        $balance->save();

        return response()->json($balance);
    }

    public function writeOff(Request $request, User $user): JsonResponse
    {
        $request->validate([
            'count' => 'required|numeric|gt:0',
        ]);

        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        $balance->balance -= $request->get('count');

        if ($balance->balance < 0) {
            return response()->json([
                'message' => __('Not enough funds'),
            ], 400);
        }
        $balance->save();

        return response()->json($balance);
    }

    public function show(User $user)
    {
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        return response()->json($balance);
    }
}
