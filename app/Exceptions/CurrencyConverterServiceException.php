<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Http\JsonResponse;

class CurrencyConverterServiceException extends Exception
{
    public function render(): JsonResponse
    {
        return response()->json($this->getMessage(), 400);
    }
}
