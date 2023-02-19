<?php

namespace App\Http\Resources;

use App\Services\BalanceService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BalanceResource extends JsonResource
{
    private string $currency = BalanceService::APP_CURRENCY;
    private ?float $convertedBalance = null;

    public function setCurrency(string $value): void
    {
        $this->currency = $value;
    }

    public function setConvertedBalance(float $balance)
    {
        $this->convertedBalance = $balance;
    }

    public function toArray(Request $request): array
    {
        return [
            'user_id' => $this->user_id,
            'balance' => $this->convertedBalance ?? $this->balance,
            'currency' => $this->currency,
        ];
    }
}
