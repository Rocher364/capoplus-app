<?php

namespace App\Exceptions;

use Exception;

class CompteBloqueException extends Exception
{
    public function __construct(string $message = "Ce compte est bloque ou inactif, l'operation est refusee.")
    {
        parent::__construct($message);
    }
}
