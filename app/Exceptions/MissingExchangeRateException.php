<?php

namespace App\Exceptions;

use Exception;

class MissingExchangeRateException extends Exception
{
    public int $fromCurrencyId;
    public int $toCurrencyId;
    public string $date;
    public string $fromCurrencyCode;
    public string $toCurrencyCode;

    public function __construct(
        int $fromCurrencyId,
        int $toCurrencyId,
        string $date,
        string $fromCurrencyCode,
        string $toCurrencyCode
    ) {
        $this->fromCurrencyId = $fromCurrencyId;
        $this->toCurrencyId = $toCurrencyId;
        $this->date = $date;
        $this->fromCurrencyCode = $fromCurrencyCode;
        $this->toCurrencyCode = $toCurrencyCode;

        $message = "Missing exchange rate for conversion from {$fromCurrencyCode} to {$toCurrencyCode} on {$date}. " .
                   "Please register the exchange rate before creating this quote.";

        parent::__construct($message);
    }

    public function getFromCurrencyId(): int
    {
        return $this->fromCurrencyId;
    }

    public function getToCurrencyId(): int
    {
        return $this->toCurrencyId;
    }

    public function getDate(): string
    {
        return $this->date;
    }

    public function getFromCurrencyCode(): string
    {
        return $this->fromCurrencyCode;
    }

    public function getToCurrencyCode(): string
    {
        return $this->toCurrencyCode;
    }
}
