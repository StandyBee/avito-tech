<?php

namespace App\Services;

//    {
//    "success": true,
//    "query": {
//        "from": "USD",
//        "to": "RUB",
//        "amount": 1
//    },
//    "info": {
//        "timestamp": 1676789943,
//        "rate": 74.000341
//    },
//    "date": "2023-02-19",
//    "result": 74.000341
//    }

//    https://api.apilayer.com/exchangerates_data/convert?to=RUB&from=USD&amount=1
//    header "apikey: 5Ke7DKDBrRWPeE2eJrkcoolP1iEmy9oI"
//        to from amount

use App\Exceptions\CurrencyConverterServiceException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class CurrencyConverterService
{
    private const HOST = 'https://api.apilayer.com';
    private const APIKEY = '5Ke7DKDBrRWPeE2eJrkcoolP1iEmy9oI';

    /**
     * array:5 [
     * "success" => true
     * "query" => array:3 [
     * "from" => "USD"
     * "to" => "RUB"
     * "amount" => 100
     * ]
     * "info" => array:2 [
     * "timestamp" => 1676791205
     * "rate" => 74.000341
     * ]
     * "date" => "2023-02-19"
     * "result" => 7400.0341
     * ]
     */

    public function convert(string $from, string $to, float $amount)
    {
        $method = "/exchangerates_data/convert";
        $queryString = "?to={$to}&from={$from}&amount={$amount}";

        $url = self::HOST . $method . $queryString;

        $response = Http::withHeaders(['apikey' => self::APIKEY])->get($url);

        if (!$response->ok()) {
            Log::error($response->json());
            throw new CurrencyConverterServiceException(__('Something went wrong'));
        }

        $data = $response->json();

        return [
            'from_currency' => $data['query']['from'],
            'to_currency' => $data['query']['to'],
            'from_amount' => $amount,
            'to_amount' => $data['result'],
            'rate' => $data['info']['rate'],
        ];
    }
}
