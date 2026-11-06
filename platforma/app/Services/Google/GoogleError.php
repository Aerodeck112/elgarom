<?php

namespace App\Services\Google;

class GoogleError extends \RuntimeException
{
    /** @param string $state expirat | acces | eroare */
    public function __construct(public string $state, string $message)
    {
        parent::__construct($message);
    }
}
