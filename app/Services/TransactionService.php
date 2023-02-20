<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Models\Transaction;

class TransactionService
{
    public function commit(TransactionType $type, float $count, $info)
    {
        return Transaction::create([
            'type' => $type,
            'count' => $count,
            'info' => json_encode($info),
        ]);
    }
}
