<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\BalanceRequest;
use App\Http\Resources\BalanceResource;
use App\Models\Balance;
use App\Models\User;
use App\Services\BalanceService;
use App\Services\CurrencyConverterService;
use App\Services\TransactionService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BalanceController extends Controller
{
    private BalanceService $balanceService;
    private CurrencyConverterService $converterService;
    private TransactionService $transactionService;

    public function __construct(
        BalanceService $balanceService,
        CurrencyConverterService $converterService,
        TransactionService $transactionService
    )
    {
        $this->balanceService = $balanceService;
        $this->converterService = $converterService;
        $this->transactionService = $transactionService;
    }

    public function add(BalanceRequest $request, User $user): BalanceResource
    {
        $count = $request->get('count');
        $balance = $this->balanceService->add($user, $count);

        $this->transactionService->commit(TransactionType::Add, $count, $balance);

        return new BalanceResource($balance);
    }

    public function writeOff(BalanceRequest $request, User $user): BalanceResource
    {
        $count = $request->get('count');
        $balance = $this->balanceService->writeOff($user, $count);

        $this->transactionService->commit(TransactionType::WriteOff, $count, $balance);

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

    public function sendTo(BalanceRequest $request, User $sender, User $recipient): AnonymousResourceCollection
    {
        $count = $request->get('count');
        $result = $this->balanceService->sendTo($sender, $recipient, $count);

        $this->transactionService->commit(TransactionType::SendTo, $count, $result);

        return BalanceResource::collection($result);
    }
}
