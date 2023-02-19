<?php

namespace App\Services;

use App\Exceptions\BalanceServiceException;
use App\Models\Balance;
use App\Models\User;
use Exception;
use Illuminate\Support\Facades\DB;

class BalanceService
{
    public const APP_CURRENCY = 'RUB';

    /**
     * @throws BalanceServiceException
     */
    public function writeOff(User $user, float $count): Balance
    {
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        $balance->balance -= $count;

        if ($balance->balance < 0) {
            throw new BalanceServiceException(__('Not enough funds'));
        }
        $balance->save();
        return $balance;
    }

    public function add(User $user, float $count): Balance
    {
        $balance = Balance::firstOrNew([
            'user_id' => $user->id,
        ]);
        $balance->balance += $count;

        $balance->save();

        return $balance;
    }

    /**
     * @throws BalanceServiceException
     */
    public function sendTo(User $sender, User $recipient, float $count): array
    {
        try {
            DB::beginTransaction();

            $senderBalance = $this->writeOff($sender, $count);
            $recipientBalance = $this->add($recipient, $count);

            DB::commit();
        } catch (Exception $exception) {
            DB::rollBack();

            throw $exception;
        }

        return [
            'sender_balance' => $senderBalance,
            'recipient_balance' => $recipientBalance,
        ];
    }
}
