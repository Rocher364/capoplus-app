<?php

namespace App\Exceptions;

use Symfony\Component\HttpKernel\Exception\HttpException;

/**
 * Lancee lorsque la couche service detecte une tentative d'action non autorisee
 * (defense-in-depth, en complement des Policies Laravel).
 */
class UnauthorizedActionException extends HttpException
{
    public function __construct(string $message = 'Action non autorisee.')
    {
        parent::__construct(403, $message);
    }
}
