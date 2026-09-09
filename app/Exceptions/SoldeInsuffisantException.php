<?php

namespace App\Exceptions;

use Exception;

class SoldeInsuffisantException extends Exception
{
    public function __construct(string $message = "Solde insuffisant pour effectuer ce retrait.")
    {
        parent::__construct($message);
    }
}
