<?php

namespace App\Http\Controllers;

use App\Http\Requests\BalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Models\Balance;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\CurrencyConverterService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class BalanceController extends Controller
{
    private BalanceService $balanceService;
    private CurrencyConverterService $converterService;

    public function __construct(BalanceService $balanceService, CurrencyConverterService $converterService)
    {
        $this->balanceService = $balanceService;
        $this->converterService = $converterService;
    }

    public function add(BalanceRequest $request, User $user): BalanceResource
    {
        $balance = $this->balanceService->add($user, $request->get('count'));

        return new BalanceResource($balance);
    }

    public function writeOff(BalanceRequest $request, User $user): BalanceResource
    {
        $balance = $this->balanceService->writeOff($user, $request->get('count'));

        return new BalanceResource($balance);
    }

    /**
     * @throws \App\Exceptions\CurrencyConverterServiceException
     */
    public function show(Request $request, User $user)
    {
        $request->validate([
            'currency' => 'string',
        ]);
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);

        $resource = new BalanceResource($balance);

        if ($request->has('currency')) {

            $convertedBalance = $this->converterService->convert(
                BalanceService::APP_CURRENCY,
                $request->get('currency'),
                $balance->balance,
            );

            $resource->setConvertedBalance($convertedBalance['to_amount']);
            $resource->setCurrency($convertedBalance['to_currency']);
        }

        return $resource;
    }

    public function sendTo(BalanceRequest $request, User $sender, User $recipient)
    {
        $result = $this->balanceService->sendTo($sender, $recipient, $request->get('count'));

        return BalanceResource::collection($result);
    }
}
