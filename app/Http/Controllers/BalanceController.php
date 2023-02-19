<?php

namespace App\Http\Controllers;

use App\Http\Requests\BalanceRequest;
use App\Models\Balance;
use App\Models\User;
use App\Services\BalanceService;
use Illuminate\Http\JsonResponse;

class BalanceController extends Controller
{
    private BalanceService $balanceService;

    public function __construct(BalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    public function add(BalanceRequest $request, User $user): JsonResponse
    {
        $balance = $this->balanceService->add($user, $request->get('count'));

        return response()->json($balance);
    }

    public function writeOff(BalanceRequest $request, User $user): JsonResponse
    {
        $balance = $this->balanceService->writeOff($user, $request->get('count'));

        return response()->json($balance);
    }

    public function show(User $user)
    {
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        return response()->json($balance);
    }

    public function sendTo(BalanceRequest $request, User $sender, User $recipient)
    {
        $result = $this->balanceService->sendTo($sender, $recipient, $request->get('count'));

        return response()->json($result);
    }
}
